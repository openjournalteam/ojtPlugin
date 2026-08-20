<?php

/**
 * Shared job registry for OJT plugins.
 *
 * Usage:
 * import('plugins.generic.ojtPlugin.Jobs');
 * OjtJobs::register($plugin, [new MyJob($plugin)]);
 * OjtJobs::dispatch($plugin, 'myJob.type', $payload, $options);
 */
class OjtJobs
{
    /** @var bool */
    protected static $hookRegistered = false;

    /** @var array<string,object> */
    protected static $plugins = [];

    /** @var array<string,array<string,object>> */
    protected static $jobsByPlugin = [];

    /** @var array<string,object[]> */
    protected static $jobsByType = [];

    /**
     * Register jobs for a plugin instance.
     *
     * @param object $plugin
     * @param array $jobs
     * @return bool
     */
    public static function register($plugin, array $jobs = [])
    {
        $key = spl_object_hash($plugin);
        if (!isset(self::$plugins[$key])) {
            self::$plugins[$key] = $plugin;
            self::$jobsByPlugin[$key] = [];
        }

        foreach ($jobs as $job) {
            self::registerJob($plugin, $job);
        }

        if (!self::$hookRegistered) {
            HookRegistry::register('OjtPlugin::processJob', [__CLASS__, 'handleProcessJob']);
            self::$hookRegistered = true;
        }

        return true;
    }

    /**
     * Register a single job for a plugin instance.
     *
     * @param object $plugin
     * @param object $job
     * @return void
     */
    public static function registerJob($plugin, $job)
    {
        if (!$job || !method_exists($job, 'getType')) {
            return;
        }

        $key = spl_object_hash($plugin);
        if (!isset(self::$plugins[$key])) {
            self::$plugins[$key] = $plugin;
            self::$jobsByPlugin[$key] = [];
        }

        $type = (string) $job->getType();
        if ($type === '') {
            return;
        }

        self::$jobsByPlugin[$key][$type] = $job;
        if (!isset(self::$jobsByType[$type])) {
            self::$jobsByType[$type] = [];
        }
        self::$jobsByType[$type][] = $job;
    }

    /**
     * Dispatch one background job.
     *
     * @param object $plugin
     * @param string $jobType
     * @param array $payload
     * @param array $options
     * @return mixed|null
     */
    public static function dispatch($plugin, $jobType, array $payload = [], array $options = [])
    {
        $job = self::resolveJob($plugin, $jobType);
        if (!$job) {
            error_log('OjtJobs: no registered job handler for type "' . $jobType . '".');
            return null;
        }

        if (!method_exists($job, 'dispatch')) {
            error_log('OjtJobs: job handler missing dispatch() for type "' . $jobType . '".');
            return null;
        }

        return $job->dispatch($payload, $options);
    }

    /**
     * Dispatch a registered job by type. This is used by the generic schedule
     * runner, which stores the job type rather than a plugin object.
     */
    public static function dispatchByType($jobType, array $payload = [], array $options = [])
    {
        foreach ((array) (self::$jobsByType[(string) $jobType] ?? []) as $job) {
            if (method_exists($job, 'dispatch')) {
                return $job->dispatch($payload, $options);
            }
        }

        error_log('OjtJobs: no registered job handler for type "' . (string) $jobType . '".');
        return null;
    }

    /**
     * WorkerBee process callback.
     */
    public static function handleProcessJob($hookName, $args)
    {
        $jobType = &$args[0];
        $payload = &$args[1];
        $handled = &$args[2];
        $result = &$args[3];
        $queueJob = isset($args[4]) ? $args[4] : null;
        $data = isset($args[5]) && is_array($args[5]) ? $args[5] : [];

        if (empty(self::$jobsByType[$jobType])) {
            return false;
        }

        foreach (self::$jobsByType[$jobType] as $job) {
            if (!method_exists($job, 'handle')) {
                continue;
            }
            if (method_exists($job, 'setRuntimeJob')) {
                $job->setRuntimeJob($queueJob);
            }
            try {
                if (method_exists($job, 'markScheduledRunStarted')) {
                    $runStarted = $job->markScheduledRunStarted(is_array($payload) ? $payload : []);
                    if ($runStarted === false) {
                        throw new Exception('Scheduled run is no longer active.');
                    }
                }
                $result = $job->handle(is_array($payload) ? $payload : [], $data);
                if (method_exists($job, 'markScheduledRunCompleted')
                    && !(is_array($result) && !empty($result['scheduled_continuation']))) {
                    $job->markScheduledRunCompleted(is_array($payload) ? $payload : [], $result);
                }
                $handled = true;
            } catch (\Throwable $e) {
                if (method_exists($job, 'markScheduledRunFailed')) {
                    $job->markScheduledRunFailed(is_array($payload) ? $payload : [], $e->getMessage());
                }
                throw $e;
            } finally {
                if (method_exists($job, 'clearRuntimeJob')) {
                    $job->clearRuntimeJob();
                }
            }
            return false;
        }

        return false;
    }

    protected static function resolveJob($plugin, $jobType)
    {
        $key = spl_object_hash($plugin);
        if (!isset(self::$plugins[$key])) {
            // Plugin must call register() first with its jobs list.
            return null;
        }

        if (empty(self::$jobsByPlugin[$key])) {
            return null;
        }

        return self::$jobsByPlugin[$key][$jobType] ?? null;
    }
}
