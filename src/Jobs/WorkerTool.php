<?php

namespace Openjournalteam\OjtPlugin\Jobs;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class WorkerTool extends \CommandLineTool
{
    /** @var array */
    protected $options = [
        'queue' => 'default',
        'sleep' => 3,
        'tries' => 3,
        'timeout' => 180,
        'memory' => 128,
        'once' => false,
        'stopWhenEmpty' => false,
        'verbose' => false,
        'json' => false,
    ];

    /** @var array */
    protected $jobStartTimes = [];

    protected $jobLogContexts = [];

    /** @var array */
    protected $failedJobIds = [];

    /** @var int Unix timestamp of the last OJT schedule poll. */
    protected $lastSchedulePollAt = 0;

    /** @var \Openjournalteam\OjtPlugin\Services\JobQueueService|null */
    protected $queueService;

    public function __construct($argv = [])
    {
        parent::__construct($argv);
        $this->parseOptions();
    }

    public function usage()
    {
        echo "Usage: php plugins/generic/ojtPlugin/tools/worker.php [options]\n";
        echo "Options:\n";
        echo "  --queue=NAME         Queue name (default: default)\n";
        echo "  --sleep=SECONDS      Poll sleep seconds (default: 3)\n";
        echo "  --tries=INT          Max tries per job (default: 3)\n";
        echo "  --timeout=SECONDS    Job timeout (default: 180)\n";
        echo "  --memory=MB          Worker memory limit (default: 128)\n";
        echo "  --once               Process only one job then exit\n";
        echo "  --stop-when-empty    Exit daemon when queue is empty\n";
        echo "  --verbose            Show heartbeat and extra job details\n";
        echo "  --json               Use full structured JSON logs\n";
    }

    public function execute()
    {
        $plugin = \OjtPlugin::get();
        if (!$plugin || !$plugin->isEnabledForRuntime()) {
            fwrite(STDERR, "OJT Worker Bee plugin is not enabled.\n");
            exit(1);
        }

        if (!$plugin->areBackgroundJobsEnabled()) {
            fwrite(STDOUT, "OJT background jobs are disabled.\n");
            return;
        }

        $this->queueService = $plugin->jobQueueService();
        $this->registerQueueLogging();
        if (!$this->options['json']) {
            $this->writeQueueLog('worker_started', ['queue' => $this->options['queue']]);
        }

        if ($this->options['once']) {
            try {
                $this->pollSchedules(true);
                $this->queueService->runNext($this->options['queue'], $this->options);
            } catch (\Throwable $e) {
                // Keep CLI output readable; details are persisted in failed jobs table.
                $this->writeQueueLog('worker_failed', ['error' => $e->getMessage()]);
            }
            return;
        }

        try {
            $this->queueService->daemon($this->options['queue'], $this->options);
        } catch (\Throwable $e) {
            // Keep CLI output readable; details are persisted in failed jobs table.
            $this->writeQueueLog('worker_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Register CLI log output for processing/success/failure job events.
     */
    protected function registerQueueLogging()
    {
        $laravelContainer = \Registry::get('laravelContainer');
        if (!$laravelContainer || !isset($laravelContainer['events'])) {
            return;
        }

        $events = $laravelContainer['events'];

        \HookRegistry::register('OjtPlugin::jobFailed', [$this, 'onWorkerBeeJobFailed']);

        $events->listen('Illuminate\Queue\Events\JobProcessing', function ($event) {
            $jobId = $this->getEventJobId($event);
            $jobType = $this->getEventJobType($event);
            $trackingToken = $this->getEventTrackingToken($event);
            $this->jobStartTimes[$jobId] = microtime(true);
            $raw = $event->job->payload();
            $data = isset($raw['data']['data']) && is_array($raw['data']['data'])
                ? $raw['data']['data'] : (isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : []);
            $this->jobLogContexts[$jobId] = [
                'queue' => $event->job->getQueue(),
                'enveloper_queue_id' => $jobType === 'enveloper.sendQueuedMail' ? ($data['payload']['queueId'] ?? null) : null,
                'enqueued_at_epoch' => $data['enqueuedAt'] ?? null,
            ];
            if ($this->queueService) {
                $attempts = isset($event->job) && method_exists($event->job, 'attempts')
                    ? $event->job->attempts()
                    : null;
                $this->jobLogContexts[$jobId]['queue_wait_seconds'] = $this->queueService->markProcessing($jobId, $attempts, $trackingToken);
                $this->jobLogContexts[$jobId]['attempt'] = $attempts;
                $this->jobLogContexts[$jobId]['tracker_job_id'] = $this->queueService->getTrackerJobId($jobId, $trackingToken);
            }
            $this->logLine('processing', $jobId, $jobType);
        });

        $events->listen('Illuminate\Queue\Events\JobProcessed', function ($event) {
            $jobId = $this->getEventJobId($event);
            if (isset($event->job) && method_exists($event->job, 'hasFailed') && $event->job->hasFailed()) {
                return;
            }
            if (isset($this->failedJobIds[$jobId])) {
                unset($this->failedJobIds[$jobId]);
                return;
            }
            $jobType = $this->getEventJobType($event);
            $trackingToken = $this->getEventTrackingToken($event);
            $duration = $this->consumeDuration($jobId);
            if ($this->queueService) {
                $attempts = isset($event->job) && method_exists($event->job, 'attempts')
                    ? $event->job->attempts()
                    : null;
                $this->queueService->markCompleted($jobId, $attempts, $trackingToken);
            }
            $this->logLine('success', $jobId, $jobType, $duration);
        });

        $events->listen('Illuminate\Queue\Events\JobFailed', function ($event) {
            $jobId = $this->getEventJobId($event);
            if (isset($this->failedJobIds[$jobId])) {
                unset($this->failedJobIds[$jobId]);
                return;
            }
            $jobType = $this->getEventJobType($event);
            $trackingToken = $this->getEventTrackingToken($event);
            $duration = $this->consumeDuration($jobId);
            $error = isset($event->exception) ? $event->exception->getMessage() : 'unknown error';
            $this->rememberFailedJobId($jobId);
            $failedJobId = $this->persistFailedJob($event);
            if ($this->queueService) {
                $this->queueService->markFailed($jobId, $error, $failedJobId, false, $trackingToken);
            }
            $this->logLine('failed', $jobId, $jobType, $duration, $error);
        });

        // WorkerBee is the scheduler heartbeat. OJT schedules are checked
        // independently of OJS's legacy scheduled_tasks configuration.
        $events->listen('Illuminate\\Queue\\Events\\Looping', function () {
            $this->pollSchedules();
        });
    }

    /**
     * Dispatch due OJT schedules without requiring one OS cron entry per
     * plugin or schedule. ScheduleService atomically claims each due time,
     * so multiple workers cannot dispatch the same occurrence.
     */
    protected function pollSchedules($force = false)
    {
        $now = time();
        if (!$force && $this->lastSchedulePollAt > 0 && ($now - $this->lastSchedulePollAt) < 60) {
            return;
        }
        $this->lastSchedulePollAt = $now;

        if ($this->queueService) {
            try {
                $snapshot = $this->queueService->queueLagSnapshot($this->options['queue']);
                $this->writeQueueLog(
                    ($snapshot['oldest_due_wait_seconds'] ?? 0) >= 300 ? 'queue_lag_warning' : 'worker_heartbeat',
                    $snapshot
                );
            } catch (\Throwable $e) {
                $this->writeQueueLog('queue_health_check_failed', ['exception_class' => get_class($e)]);
            }
        }

        $plugin = \OjtPlugin::get();
        if (!$plugin || !method_exists($plugin, 'scheduleService')) {
            return;
        }

        if (method_exists($plugin, 'disableLegacyScheduledTasks')) {
            $plugin->disableLegacyScheduledTasks();
        }

        try {
            $summary = $plugin->scheduleService()->runDueSchedules();
            if (!empty($summary['dispatched'])) {
                $this->writeQueueLog('schedule_dispatched', ['count' => (int) $summary['dispatched']]);
            }
            foreach ((array) ($summary['errors'] ?? []) as $error) {
                $this->writeQueueLog('schedule_failed', ['error' => (string) $error]);
            }
        } catch (\Throwable $e) {
            $this->writeQueueLog('schedule_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * WorkerBee direct failure hook handler (reliable across runtime differences).
     *
     * @param string $hookName
     * @param array $args
     * @return bool
     */
    public function onWorkerBeeJobFailed($hookName, $args)
    {
        $jobType = isset($args[0]) ? (string) $args[0] : 'unknown';
        $payload = isset($args[1]) && is_array($args[1]) ? $args[1] : [];
        $error = isset($args[2]) ? $args[2] : null;
        $job = isset($args[3]) ? $args[3] : null;
        $data = isset($args[4]) && is_array($args[4]) ? $args[4] : [];

        $jobId = ($job && method_exists($job, 'getJobId')) ? (string) $job->getJobId() : 'n/a';
        if (isset($this->failedJobIds[$jobId])) {
            return false;
        }

        $message = 'unknown error';
        $exceptionText = null;
        if ($error instanceof \Throwable) {
            $message = $error->getMessage();
            $exceptionText = (string) $error;
        } elseif (is_string($error) && $error !== '') {
            $message = $error;
            $exceptionText = $error;
        }

        $attempts = ($job && method_exists($job, 'attempts')) ? (int) $job->attempts() : 0;
        $maxAttempts = isset($data['maxAttempts'])
            ? max(1, (int) $data['maxAttempts'])
            : max(1, (int) $this->options['tries']);

        // Laravel will release the job for another attempt. Do not expose an
        // intermediate retry as a final failure in the control panel.
        if ($attempts > 0 && $attempts < $maxAttempts) {
            return false;
        }

        $this->rememberFailedJobId($jobId);
        $duration = $this->consumeDuration($jobId);

        $failedJobId = $this->persistFailedJobFromData($job, $data, $payload, $exceptionText, $jobType);
        if ($this->queueService && $jobId !== 'n/a') {
            $trackingToken = isset($data['trackingToken']) ? (string) $data['trackingToken'] : null;
            $this->queueService->markFailed($jobId, $message, $failedJobId, false, $trackingToken);
        }
        $this->logLine('failed', $jobId, $jobType, $duration, $message);

        return false;
    }

    protected function rememberFailedJobId($jobId)
    {
        if ($jobId === 'n/a' || $jobId === '') {
            return;
        }

        $this->failedJobIds[(string) $jobId] = true;
        if (count($this->failedJobIds) > 1000) {
            $this->failedJobIds = array_slice($this->failedJobIds, -500, null, true);
        }
    }

    /**
     * Persist failed job details to failed jobs table (Laravel-like).
     *
     * @param mixed $event
     * @return void
     */
    protected function persistFailedJob($event)
    {
        try {
            $this->ensureFailedJobsTable();

            $job = isset($event->job) ? $event->job : null;
            if (!$job) {
                return;
            }

            $payloadData = method_exists($job, 'payload') ? $job->payload() : [];
            $exception = isset($event->exception) ? (string) $event->exception : 'Unknown exception';
            return $this->persistFailedJobFromData($job, $payloadData, $payloadData, $exception);
        } catch (\Throwable $e) {
            error_log('OjtWorkerBee failed-job persistence error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Persist failed job row from normalized inputs.
     *
     * @param mixed $job
     * @param array $data
     * @param array $payloadData
     * @param string|null $exception
     * @param string|null $jobType
     * @return int|null
     */
    protected function persistFailedJobFromData($job, array $data = [], array $payloadData = [], $exception = null, $jobType = null)
    {
        $this->ensureFailedJobsTable();

        $payloadRaw = ($job && method_exists($job, 'getRawBody'))
            ? $job->getRawBody()
            : json_encode($payloadData);
        $queue = ($job && method_exists($job, 'getQueue')) ? (string) $job->getQueue() : 'default';
        $connection = ($job && method_exists($job, 'getConnectionName')) ? (string) $job->getConnectionName() : 'default';
        $exceptionText = $exception ? (string) $exception : 'Unknown exception';

        $contextId = null;
        if (isset($data['contextId']) && (int) $data['contextId'] > 0) {
            $contextId = (int) $data['contextId'];
        } elseif (isset($payloadData['data']['data']['contextId']) && (int) $payloadData['data']['data']['contextId'] > 0) {
            $contextId = (int) $payloadData['data']['data']['contextId'];
        } elseif (isset($payloadData['data']['contextId']) && (int) $payloadData['data']['contextId'] > 0) {
            $contextId = (int) $payloadData['data']['contextId'];
        } elseif (isset($payloadData['contextId']) && (int) $payloadData['contextId'] > 0) {
            $contextId = (int) $payloadData['contextId'];
        }

        $queueJobId = ($job && method_exists($job, 'getJobId')) ? (int) $job->getJobId() : null;
        return Capsule::table($this->getFailedJobsTableName())->insertGetId([
            'connection' => $connection,
            'queue' => $queue,
            'queue_job_id' => $queueJobId ?: null,
            'payload' => (string) $payloadRaw,
            'exception' => $exceptionText,
            'context_id' => $contextId,
            'failed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ensure failed jobs table exists before insert.
     *
     * @return void
     */
    protected function ensureFailedJobsTable()
    {
        $schema = Capsule::schema();
        $table = $this->getFailedJobsTableName();
        if ($schema->hasTable($table)) {
            if (!$schema->hasColumn($table, 'queue_job_id')) {
                $schema->table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('queue_job_id')->nullable()->after('queue');
                    $table->index(['queue_job_id']);
                });
            }
            if (!$schema->hasColumn($table, 'retry_claimed_at')) {
                $schema->table($table, function (Blueprint $table) {
                    $table->timestamp('retry_claimed_at')->nullable()->after('failed_at');
                    $table->index(['retry_claimed_at']);
                });
            }
            return;
        }

        $schema->create($table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->unsignedBigInteger('queue_job_id')->nullable();
            $table->longText('payload');
            $table->longText('exception');
            $table->unsignedInteger('context_id')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamp('retry_claimed_at')->nullable();
            $table->index(['context_id']);
            $table->index(['queue_job_id']);
            $table->index(['retry_claimed_at']);
        });
    }

    /**
     * Get failed jobs table name.
     *
     * @return string
     */
    protected function getFailedJobsTableName()
    {
        return \Openjournalteam\OjtPlugin\Services\JobQueueService::FAILED_JOBS_TABLE;
    }

    /**
     * Build event job id safely.
     *
     * @param mixed $event
     * @return string
     */
    protected function getEventJobId($event)
    {
        if (isset($event->job) && method_exists($event->job, 'getJobId')) {
            $jobId = $event->job->getJobId();
            if ($jobId !== null && $jobId !== '') {
                return (string) $jobId;
            }
        }

        return 'n/a';
    }

    /**
     * Build event job type safely.
     *
     * @param mixed $event
     * @return string
     */
    protected function getEventJobType($event)
    {
        if (!isset($event->job) || !method_exists($event->job, 'payload')) {
            return 'unknown';
        }

        $payload = $event->job->payload();
        if (!is_array($payload)) {
            return 'unknown';
        }

        if (isset($payload['data']['data']['jobType']) && $payload['data']['data']['jobType']) {
            return (string) $payload['data']['data']['jobType'];
        }

        if (isset($payload['data']['jobType']) && $payload['data']['jobType']) {
            return (string) $payload['data']['jobType'];
        }

        if (isset($payload['job']) && $payload['job']) {
            return (string) $payload['job'];
        }

        return 'unknown';
    }

    /**
     * Extract the private tracking token from a queue payload.
     */
    protected function getEventTrackingToken($event)
    {
        if (!isset($event->job) || !method_exists($event->job, 'payload')) {
            return null;
        }

        $payload = $event->job->payload();
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['data']['trackingToken'])) {
            return (string) $payload['data']['trackingToken'];
        }
        if (isset($payload['data']['data']['trackingToken'])) {
            return (string) $payload['data']['data']['trackingToken'];
        }

        return null;
    }

    /**
     * Consume and calculate job elapsed time.
     *
     * @param string $jobId
     * @return float|null
     */
    protected function consumeDuration($jobId)
    {
        if (!isset($this->jobStartTimes[$jobId])) {
            return null;
        }

        $startedAt = $this->jobStartTimes[$jobId];
        unset($this->jobStartTimes[$jobId]);

        return microtime(true) - $startedAt;
    }

    /**
     * Print one formatted log line to CLI.
     *
     * @param string $status
     * @param string $jobId
     * @param string $jobType
     * @param float|null $duration
     * @param string|null $error
     */
    protected function logLine($status, $jobId, $jobType, $duration = null, $error = null)
    {
        if ($this->queueService && !isset($this->jobLogContexts[$jobId]['tracker_job_id'])) {
            $this->jobLogContexts[$jobId]['tracker_job_id'] = $this->queueService->getTrackerJobId($jobId);
        }
        $this->writeQueueLog('job_' . $status, array_merge($this->jobLogContexts[$jobId] ?? [], [
            'worker_job_id' => $jobId,
            'job_type' => $jobType ?: 'unknown',
            'execution_ms' => $duration !== null ? max(0, (int) round($duration * 1000)) : null,
            'error' => $error ? (string) $error : null,
        ]));
        if ($status !== 'processing') {
            unset($this->jobLogContexts[$jobId]);
        }
    }

    protected function writeQueueLog($event, array $context = [])
    {
        if (!$this->options['json']) {
            $this->writeConsoleLog($event, $context);
            return;
        }

        fwrite(STDOUT, '[OJTWorkerBee] ' . json_encode(array_merge([
            'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'epoch_ms' => (int) round(microtime(true) * 1000),
            'event' => $event,
            'host' => gethostname(),
            'pid' => getmypid(),
        ], $context), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL);
    }

    /** Print compact job statuses; routine heartbeats are opt-in. */
    protected function writeConsoleLog($event, array $context)
    {
        if ($event === 'worker_heartbeat' && !$this->options['verbose']) {
            return;
        }

        $statuses = [
            'job_processing' => 'RUNNING', 'job_success' => 'DONE', 'job_failed' => 'FAIL',
            'worker_started' => 'INFO', 'worker_heartbeat' => 'IDLE',
            'queue_lag_warning' => 'WARN', 'queue_health_check_failed' => 'WARN',
            'worker_failed' => 'FAIL', 'schedule_dispatched' => 'QUEUED', 'schedule_failed' => 'FAIL',
        ];
        $status = $statuses[$event] ?? 'INFO';
        $symbols = ['RUNNING' => '▶', 'DONE' => '✓', 'FAIL' => '✗', 'WARN' => '!', 'INFO' => '•', 'IDLE' => '○', 'QUEUED' => '+'];
        $width = $this->consoleWidth() - 1; // Leave the last column free to avoid terminal auto-wrap.
        $timestamp = '[' . date($width < 40 ? 'H:i' : 'H:i:s') . ']';
        $label = ($symbols[$status] ?? '•') . ($width < 40 ? '' : ' ' . str_pad($status, 7));
        $available = max(1, $width - $this->consoleTextWidth($timestamp . ' ' . $label . ' '));
        $details = [];
        $queue = $this->consoleText($context['queue'] ?? $this->options['queue'], 24);
        if (strpos($event, 'job_') === 0) {
            $id = isset($context['tracker_job_id'])
                ? 'ID:' . $this->consoleText($context['tracker_job_id'])
                : 'Q:' . $this->consoleText($context['worker_job_id'] ?? 'n/a');
            $duration = isset($context['execution_ms'])
                ? ' ' . (int) $context['execution_ms'] . 'ms'
                : ($event === 'job_processing' ? ' 0ms' : '');
            $nameWidth = max(0, $available - $this->consoleTextWidth($id . ' ' . $duration));
            $name = $this->consoleText($context['job_type'] ?? 'unknown', $nameWidth);
            $padding = max(0, $nameWidth - $this->consoleTextWidth($name));
            $message = $id . ' ' . $name . str_repeat('.', $padding) . $duration;
            if ($this->options['verbose']) {
                $detail = 'queue=' . $queue;
                $detail .= ' queue-id=' . $this->consoleText($context['worker_job_id'] ?? 'n/a');
                if (isset($context['attempt'])) {
                    $detail .= ' attempt=' . (int) $context['attempt'];
                }
                if (isset($context['queue_wait_seconds'])) {
                    $detail .= ' wait=' . (int) $context['queue_wait_seconds'] . 's';
                }
                $details[] = $detail;
            }
        } elseif ($event === 'worker_started') {
            $message = 'Processing queue [' . $queue . ']. Ctrl+C to stop.';
        } elseif ($event === 'worker_heartbeat' || $event === 'queue_lag_warning') {
            $message = sprintf('Queue [%s]: %d ready', $queue, $context['ready_count'] ?? 0);
            if ($event === 'queue_lag_warning') {
                $message .= sprintf(' | oldest #%s waiting %ds',
                    $this->consoleText($context['oldest_worker_job_id'] ?? 'n/a', 12),
                    $context['oldest_due_wait_seconds'] ?? 0);
            }
        } elseif ($event === 'schedule_dispatched') {
            $message = sprintf('Schedule dispatched %d job(s).', $context['count'] ?? 0);
        } elseif ($event === 'queue_health_check_failed') {
            $message = 'Queue health check failed: ' . $this->consoleText($context['exception_class'] ?? 'unknown');
        } elseif ($event === 'schedule_failed') {
            $message = 'Schedule error';
        } elseif ($event === 'worker_failed') {
            $message = 'Worker runtime error';
        } else {
            $message = $this->consoleText($event);
        }

        if (isset($context['error']) && $context['error'] !== '') {
            $details[] = 'Error: ' . $context['error'];
        }

        $message = $this->consoleText($message, $available);
        if (function_exists('stream_isatty') && stream_isatty(STDOUT) && getenv('NO_COLOR') === false) {
            $colors = ['RUNNING' => '33', 'DONE' => '32', 'FAIL' => '31', 'WARN' => '33', 'IDLE' => '90'];
            $label = "\033[" . ($colors[$status] ?? '36') . 'm' . $label . "\033[0m";
        }
        fwrite(STDOUT, $timestamp . ' ' . $label . ' ' . $message . PHP_EOL);
        foreach ($details as $detail) {
            fwrite(STDOUT, '    ↳ ' . $this->consoleText($detail, max(1, $width - 6)) . PHP_EOL);
        }
    }

    /** Read the current terminal size on each event, including after a resize. */
    protected function consoleWidth()
    {
        if (function_exists('stream_isatty') && stream_isatty(STDOUT) && function_exists('proc_open')) {
            $process = @proc_open('stty size', [0 => STDOUT, 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
            if (is_resource($process)) {
                $size = trim(stream_get_contents($pipes[1]));
                fclose($pipes[1]);
                proc_close($process);
                if (preg_match('/^\d+\s+([1-9]\d*)$/', $size, $matches)) {
                    return max(12, min(500, (int) $matches[1]));
                }
            }
        }
        $columns = getenv('COLUMNS');
        return $columns && ctype_digit($columns) ? max(12, min(500, (int) $columns)) : 80;
    }

    protected function consoleTextWidth($text)
    {
        return function_exists('mb_strwidth') ? mb_strwidth($text, 'UTF-8') : strlen($text);
    }

    /** Keep untrusted job names and errors on one bounded terminal line. */
    protected function consoleText($value, $limit = 180)
    {
        if ($limit <= 0) {
            return '';
        }
        $text = trim(preg_replace('/[\x00-\x20\x7f]+/', ' ', (string) $value));
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $limit, $limit >= 3 ? '...' : '', 'UTF-8');
        }
        return strlen($text) > $limit ? ($limit >= 3 ? substr($text, 0, $limit - 3) . '...' : substr($text, 0, $limit)) : $text;
    }

    protected function parseOptions()
    {
        foreach ($this->argv as $arg) {
            if ($arg === '--verbose' || $arg === '--json') {
                $this->options[substr($arg, 2)] = true;
                continue;
            }

            if ($arg === '--once') {
                $this->options['once'] = true;
                continue;
            }

            if ($arg === '--stop-when-empty') {
                $this->options['stopWhenEmpty'] = true;
                continue;
            }

            if (strpos($arg, '--queue=') === 0) {
                $this->options['queue'] = (string) substr($arg, 8);
                continue;
            }

            if (strpos($arg, '--sleep=') === 0) {
                $this->options['sleep'] = max(0, (int) substr($arg, 8));
                continue;
            }

            if (strpos($arg, '--tries=') === 0) {
                $this->options['tries'] = max(1, (int) substr($arg, 8));
                continue;
            }

            if (strpos($arg, '--timeout=') === 0) {
                $this->options['timeout'] = max(1, (int) substr($arg, 10));
                continue;
            }

            if (strpos($arg, '--memory=') === 0) {
                $this->options['memory'] = max(64, (int) substr($arg, 9));
                continue;
            }
        }
    }
}
