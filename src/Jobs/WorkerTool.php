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
        'timeout' => 60,
        'memory' => 128,
        'once' => false,
        'stopWhenEmpty' => false,
    ];

    /** @var array */
    protected $jobStartTimes = [];

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
        echo "  --timeout=SECONDS    Job timeout (default: 60)\n";
        echo "  --memory=MB          Worker memory limit (default: 128)\n";
        echo "  --once               Process only one job then exit\n";
        echo "  --stop-when-empty    Exit daemon when queue is empty\n";
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

        if ($this->options['once']) {
            try {
                $this->pollSchedules(true);
                $this->queueService->runNext($this->options['queue'], $this->options);
            } catch (\Throwable $e) {
                // Keep CLI output readable; details are persisted in failed jobs table.
                $time = date('Y-m-d H:i:s');
                fwrite(STDOUT, sprintf('[%s] Failed: Worker runtime error. Reason: %s', $time, $e->getMessage()) . PHP_EOL);
            }
            return;
        }

        try {
            $this->queueService->daemon($this->options['queue'], $this->options);
        } catch (\Throwable $e) {
            // Keep CLI output readable; details are persisted in failed jobs table.
            $time = date('Y-m-d H:i:s');
            fwrite(STDOUT, sprintf('[%s] Failed: Worker runtime error. Reason: %s', $time, $e->getMessage()) . PHP_EOL);
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
            if ($this->queueService) {
                $attempts = isset($event->job) && method_exists($event->job, 'attempts')
                    ? $event->job->attempts()
                    : null;
                $this->queueService->markProcessing($jobId, $attempts, $trackingToken);
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
                fwrite(STDOUT, sprintf(
                    "[%s] OJT schedule poll dispatched %d job(s).%s",
                    date('Y-m-d H:i:s'),
                    (int) $summary['dispatched'],
                    PHP_EOL
                ));
            }
            foreach ((array) ($summary['errors'] ?? []) as $error) {
                fwrite(STDERR, sprintf(
                    "[%s] OJT schedule error: %s%s",
                    date('Y-m-d H:i:s'),
                    (string) $error,
                    PHP_EOL
                ));
            }
        } catch (\Throwable $e) {
            fwrite(STDERR, sprintf(
                "[%s] OJT schedule poll failed: %s%s",
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                PHP_EOL
            ));
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
        $time = date('Y-m-d H:i:s');
        $prettyType = $jobType ?: 'Unknown job';

        if ($status === 'processing') {
            $line = sprintf('[%s] Starting: %s (Job #%s)', $time, $prettyType, $jobId);
        } elseif ($status === 'success') {
            $durationText = $duration !== null ? ' in ' . number_format((float) $duration * 1000, 0) . 'ms' : '';
            $line = sprintf('[%s] Done: %s (Job #%s)%s', $time, $prettyType, $jobId, $durationText);
        } else {
            $durationText = $duration !== null ? ' after ' . number_format((float) $duration * 1000, 0) . 'ms' : '';
            $errorText = $error ? ' Reason: ' . str_replace('"', "'", (string) $error) : '';
            $line = sprintf('[%s] Failed: %s (Job #%s)%s.%s', $time, $prettyType, $jobId, $durationText, $errorText);
        }

        fwrite(STDOUT, $line . PHP_EOL);
    }

    protected function parseOptions()
    {
        foreach ($this->argv as $arg) {
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
