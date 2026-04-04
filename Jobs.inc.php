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
     * WorkerBee process callback.
     */
    public static function handleProcessJob($hookName, $args)
    {
        $jobType = &$args[0];
        $payload = &$args[1];
        $handled = &$args[2];
        $result = &$args[3];
        $data = isset($args[5]) && is_array($args[5]) ? $args[5] : [];

        if (empty(self::$jobsByType[$jobType])) {
            return false;
        }

        foreach (self::$jobsByType[$jobType] as $job) {
            if (!method_exists($job, 'handle')) {
                continue;
            }
            $result = $job->handle(is_array($payload) ? $payload : [], $data);
            $handled = true;
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
