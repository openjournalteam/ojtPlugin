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

    public function __construct($argv = [])
    {
        parent::__construct($argv);
        $this->parseOptions();
    }

    public function usage()
    {
        echo "Usage: php plugins/generic/ojtPlugin/modules/ojtWorkerBee/tools/worker.php [options]\n";
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

        $this->registerQueueLogging();

        $service = $plugin->jobQueueService();

        if ($this->options['once']) {
            try {
                $service->runNext($this->options['queue'], $this->options);
            } catch (\Throwable $e) {
                // Keep CLI output readable; details are persisted in failed jobs table.
                $time = date('Y-m-d H:i:s');
                fwrite(STDOUT, sprintf('[%s] Failed: Worker runtime error. Reason: %s', $time, $e->getMessage()) . PHP_EOL);
            }
            return;
        }

        try {
            $service->daemon($this->options['queue'], $this->options);
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

        \HookRegistry::register('OjtWorkerBee::jobFailed', [$this, 'onWorkerBeeJobFailed']);

        $events->listen('Illuminate\Queue\Events\JobProcessing', function ($event) {
            $jobId = $this->getEventJobId($event);
            $jobType = $this->getEventJobType($event);
            $this->jobStartTimes[$jobId] = microtime(true);
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
            $duration = $this->consumeDuration($jobId);
            $this->logLine('success', $jobId, $jobType, $duration);
        });

        $events->listen('Illuminate\Queue\Events\JobFailed', function ($event) {
            $jobId = $this->getEventJobId($event);
            if (isset($this->failedJobIds[$jobId])) {
                return;
            }
            $jobType = $this->getEventJobType($event);
            $duration = $this->consumeDuration($jobId);
            $error = isset($event->exception) ? $event->exception->getMessage() : 'unknown error';
            $this->failedJobIds[$jobId] = true;
            $this->persistFailedJob($event);
            $this->logLine('failed', $jobId, $jobType, $duration, $error);
        });
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

        $this->failedJobIds[$jobId] = true;
        $duration = $this->consumeDuration($jobId);

        $message = 'unknown error';
        $exceptionText = null;
        if ($error instanceof \Throwable) {
            $message = $error->getMessage();
            $exceptionText = (string) $error;
        } elseif (is_string($error) && $error !== '') {
            $message = $error;
            $exceptionText = $error;
        }

        $this->persistFailedJobFromData($job, $data, $payload, $exceptionText, $jobType);
        $this->logLine('failed', $jobId, $jobType, $duration, $message);

        return false;
    }

    /**
     * Persist failed job details to ojt_worker_bee_failed_jobs (Laravel-like).
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
            $this->persistFailedJobFromData($job, $payloadData, $payloadData, $exception);
        } catch (\Throwable $e) {
            error_log('OjtWorkerBee failed-job persistence error: ' . $e->getMessage());
        }
    }

    /**
     * Persist failed job row from normalized inputs.
     *
     * @param mixed $job
     * @param array $data
     * @param array $payloadData
     * @param string|null $exception
     * @param string|null $jobType
     * @return void
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

        Capsule::table('ojt_worker_bee_failed_jobs')->insert([
            'connection' => $connection,
            'queue' => $queue,
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
        if ($schema->hasTable('ojt_worker_bee_failed_jobs')) {
            return;
        }

        $schema->create('ojt_worker_bee_failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->unsignedInteger('context_id')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            $table->index(['context_id']);
        });
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