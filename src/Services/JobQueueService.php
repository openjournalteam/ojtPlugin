<?php

namespace Openjournalteam\OjtPlugin\Services;

use Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Openjournalteam\OjtPlugin\Migrations\MigrationManager;
use Registry;
use Throwable;

class JobQueueService
{
    const CONNECTION = 'ojtPluginPersistent';
    const TABLE = 'ojt_plugin_jobs';

    /** @var bool */
    protected static $queueConnectionBooted = false;

    /** @var \OjtPlugin */
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Dispatch one job into Laravel database queue (connection: persistent).
     *
     * @param string $jobType
     * @param array $payload
     * @param array $options queue, delaySeconds, contextId
     * @return mixed Job ID from queue backend.
     */
    public function dispatch($jobType, $payload = [], $options = [])
    {
        if (!$jobType) {
            return null;
        }

        $this->bootQueueConnection();

        $queueName = (string) ($options['queue'] ?? 'default');
        $delaySeconds = (int) ($options['delaySeconds'] ?? 0);
        $contextId = $this->resolveContextId($options, $payload);
        $data = [
            'jobType' => (string) $jobType,
            'payload' => is_array($payload) ? $payload : [],
            'contextId' => $contextId ?: null,
            'maxAttempts' => isset($options['maxAttempts']) ? max(1, (int) $options['maxAttempts']) : null,
            'enqueuedAt' => time(),
        ];

        $job = 'OjtWorkerBeeJobHandler@fire';
        try {
            if ($delaySeconds > 0) {
                $jobId = Queue::later($delaySeconds, $job, $data, $queueName, self::CONNECTION);
                $this->persistContextId($jobId, $contextId);
                return $jobId;
            }

            $jobId = Queue::push($job, $data, $queueName, self::CONNECTION);
            $this->persistContextId($jobId, $contextId);
            return $jobId;
        } catch (Throwable $e) {
            error_log('OjtWorkerBee dispatch failed: ' . $e->getMessage());
            return null;
        }
    }

    public function isEnabledForRuntime()
    {
        $plugin = $this->plugin;

        if ($plugin->getEnabled()) {
            return true;
        }

        $pluginName = strtolower_codesafe($plugin->getName());
        $pluginSettingsDao = \DAORegistry::getDAO('PluginSettingsDAO');
        $result = $pluginSettingsDao->retrieve(
            'SELECT setting_value FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND setting_value IN (?, ?, ?, ?) LIMIT 1',
            [
                $pluginName,
                'enabled',
                '1',
                'true',
                'on',
                'yes',
            ]
        );

        return (bool) $result->current();
    }

    /**
     * Run exactly one queued job.
     *
     * @param string $queueName
     * @param array $options sleep, tries, timeout
     * @return int
     */
    public function runNext($queueName = 'default', $options = [])
    {
        $this->bootQueueConnection();
        $worker = $this->makeWorker();
        $workerOptions = $this->makeWorkerOptions($options, false);
        $worker->runNextJob(self::CONNECTION, $queueName, $workerOptions);
        return 1;
    }

    /**
     * Run a daemon worker loop.
     *
     * @param string $queueName
     * @param array $options sleep, tries, timeout, stopWhenEmpty
     * @return void
     */
    public function daemon($queueName = 'default', $options = [])
    {
        $this->bootQueueConnection();
        $worker = $this->makeWorker();
        $workerOptions = $this->makeWorkerOptions($options, (bool) ($options['stopWhenEmpty'] ?? false));
        $worker->daemon(self::CONNECTION, $queueName, $workerOptions);
    }

    /**
     * Get queue stats from jobs table.
     */
    public function stats($queueName = 'default')
    {
        MigrationManager::make($this)->runMigrations();
        $query = Capsule::table(self::TABLE)->where('queue', '=', (string) $queueName);

        return [
            'pending' => (int) (clone $query)->whereNull('reserved_at')->count(),
            'running' => (int) (clone $query)->whereNotNull('reserved_at')->count(),
            'total' => (int) (clone $query)->count(),
        ];
    }

    protected function makeWorker()
    {
        $laravelContainer = Registry::get('laravelContainer');

        return new Worker(
            $laravelContainer['queue'],
            $laravelContainer['events'],
            $laravelContainer['exception.handler'],
            function () {
                return false;
            }
        );
    }

    protected function bootQueueConnection()
    {
        if (self::$queueConnectionBooted) {
            return;
        }

        MigrationManager::make($this)->runMigrations();

        // Register runtime queue connection via container config.
        $laravelContainer = Registry::get('laravelContainer');
        if (isset($laravelContainer['config'])) {
            $laravelContainer['config']['queue.connections.' . self::CONNECTION] = [
                'driver' => 'database',
                'table' => self::TABLE,
                'connection' => 'default',
                'queue' => 'default',
            ];
        } else {
            $laravelContainer['config'] = [
                'queue.connections.' . self::CONNECTION => [
                    'driver' => 'database',
                    'table' => self::TABLE,
                    'connection' => 'default',
                    'queue' => 'default',
                ],
            ];
        }

        self::$queueConnectionBooted = true;
    }

    protected function makeWorkerOptions($options, $stopWhenEmpty)
    {
        $sleep = isset($options['sleep']) ? max(0, (int) $options['sleep']) : 3;
        $tries = isset($options['tries']) ? max(1, (int) $options['tries']) : 3;
        $timeout = isset($options['timeout']) ? max(1, (int) $options['timeout']) : 60;
        $memory = isset($options['memory']) ? max(64, (int) $options['memory']) : 128;

        return new WorkerOptions(
            0,
            $memory,
            $timeout,
            $sleep,
            $tries,
            false,
            $stopWhenEmpty
        );
    }

    /**
     * Resolve journal/context id from options or payload.
     *
     * @param array $options
     * @param array $payload
     * @return int|null
     */
    protected function resolveContextId($options, $payload)
    {
        if (isset($options['contextId']) && (int) $options['contextId'] > 0) {
            return (int) $options['contextId'];
        }

        if (isset($payload['contextId']) && (int) $payload['contextId'] > 0) {
            return (int) $payload['contextId'];
        }

        if (isset($payload['journalId']) && (int) $payload['journalId'] > 0) {
            return (int) $payload['journalId'];
        }

        $request = Application::get() ? Application::get()->getRequest() : null;
        $context = $request ? $request->getContext() : null;
        if ($context && (int) $context->getId() > 0) {
            return (int) $context->getId();
        }

        return null;
    }

    /**
     * Store context id in dedicated column for fast filtering.
     *
     * @param mixed $jobId
     * @param int|null $contextId
     * @return void
     */
    protected function persistContextId($jobId, $contextId)
    {
        if (!$jobId) {
            return;
        }

        if (!$contextId || (int) $contextId <= 0) {
            return;
        }

        try {
            Capsule::table(self::TABLE)
                ->where('id', '=', (int) $jobId)
                ->update(['context_id' => (int) $contextId]);
        } catch (Throwable $e) {
            error_log('OjtWorkerBee context_id update failed for job ' . (int) $jobId . ': ' . $e->getMessage());
        }
    }
}