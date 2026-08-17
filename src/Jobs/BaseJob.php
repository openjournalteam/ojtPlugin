<?php

namespace Openjournalteam\OjtPlugin\Jobs;

abstract class BaseJob implements JobInterface
{
    /** @var \LazyLoadPlugin */
    protected $plugin;

    /** @var \OjtPlugin|false|null */
    protected static $ojtPlugin = null;

    /** @var string|null Laravel queue job id while handling a job. */
    protected $runtimeQueueJobId;

    /** @var string|null Dispatch tracking token used during the binding race. */
    protected $runtimeTrackingToken;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @copydoc JobInterface::getQueue()
     */
    public function getQueue()
    {
        return 'default';
    }

    /**
     * @copydoc JobInterface::isRequireRuntimeBaseUrl()
     */
    public function isRequireRuntimeBaseUrl()
    {
        return false;
    }

    /**
     * Set by OjtJobs while a worker is executing this handler.
     *
     * @param mixed $job
     * @return void
     */
    public function setRuntimeJob($job)
    {
        $this->runtimeQueueJobId = ($job && method_exists($job, 'getJobId'))
            ? $job->getJobId()
            : null;
        $payload = ($job && method_exists($job, 'payload')) ? $job->payload() : [];
        if (isset($payload['data']['trackingToken'])) {
            $this->runtimeTrackingToken = (string) $payload['data']['trackingToken'];
        } elseif (isset($payload['data']['data']['trackingToken'])) {
            $this->runtimeTrackingToken = (string) $payload['data']['data']['trackingToken'];
        }
    }

    /**
     * Clear worker-only state after a handler returns.
     */
    public function clearRuntimeJob()
    {
        $this->runtimeQueueJobId = null;
        $this->runtimeTrackingToken = null;
    }

    /**
     * Report progress to the Background Jobs panel.
     */
    public function progress($percent)
    {
        if (!$this->runtimeQueueJobId) {
            return;
        }

        $ojtPlugin = $this->getOjtPlugin();
        if ($ojtPlugin) {
            $ojtPlugin->jobQueueService()->updateProgress($this->runtimeQueueJobId, $percent, $this->runtimeTrackingToken);
        }
    }

    /**
     * Allow long-running handlers to stop cooperatively after a Stop action.
     */
    public function shouldStop()
    {
        if (!$this->runtimeQueueJobId) {
            return false;
        }

        $ojtPlugin = $this->getOjtPlugin();
        return $ojtPlugin
            ? $ojtPlugin->jobQueueService()->isCancellationRequested($this->runtimeQueueJobId, $this->runtimeTrackingToken)
            : false;
    }

    /**
     * Keep the schedule history in sync with the WorkerBee lifecycle.
     */
    public function markScheduledRunStarted(array $payload = [])
    {
        $runId = $this->getScheduledRunId($payload);
        $ojtPlugin = $runId ? $this->getOjtPlugin() : null;
        if ($ojtPlugin) {
            $ojtPlugin->scheduleService()->markRunStarted($runId);
        }
    }

    public function markScheduledRunCompleted(array $payload = [], $result = [])
    {
        $runId = $this->getScheduledRunId($payload);
        $ojtPlugin = $runId ? $this->getOjtPlugin() : null;
        if ($ojtPlugin) {
            $ojtPlugin->scheduleService()->markRunCompleted($runId, $result);
        }
    }

    public function markScheduledRunFailed(array $payload = [], $message = '', $details = [])
    {
        $runId = $this->getScheduledRunId($payload);
        $ojtPlugin = $runId ? $this->getOjtPlugin() : null;
        if ($ojtPlugin) {
            $ojtPlugin->scheduleService()->markRunFailed($runId, $message, $details);
        }
    }

    protected function getScheduledRunId(array $payload = [])
    {
        return isset($payload['scheduleRunId']) ? (int) $payload['scheduleRunId'] : 0;
    }

    /**
     * @copydoc JobInterface::dispatch()
     */
    public function dispatch(array $payload = [], array $options = [])
    {
        $ojtPlugin = $this->getOjtPlugin();
        if (!$ojtPlugin) {
            return null;
        }

        if ($this->isRequireRuntimeBaseUrl()) {
            $payload = $this->ensureRuntimeBaseUrl($payload);
        }

        if (!isset($options['queue'])) {
            $options['queue'] = $this->getQueue();
        }

        return self::$ojtPlugin::dispatch($this->getType(), $payload, $options);
    }

    /**
     * Check OjtWorkerBee plugin availability.
     *
     * @return mixed|null
     */
    protected function getOjtPlugin()
    {
        if (self::$ojtPlugin !== null) {
            return self::$ojtPlugin ?: null;
        }

        $plugin = \OjtPlugin::get();
        if (!$plugin || !$plugin->jobQueueService()->isEnabledForRuntime()) {
            error_log('OjtPlugin: job queue service is not enabled for runtime.');
            self::$ojtPlugin = false;
            return null;
        }

        self::$ojtPlugin = $plugin;
        return $plugin;
    }

    /**
     * Attach runtime base URL when dispatched from web request.
     *
     * @param array $payload
     * @return array
     */
    protected function ensureRuntimeBaseUrl(array $payload)
    {
        if (!empty($payload['baseUrl']) || !empty($payload['runtimeBaseUrl'])) {
            return $payload;
        }

        try {
            $request = \Application::get()->getRequest();
            if ($request && method_exists($request, 'getBaseUrl')) {
                $baseUrl = trim((string) $request->getBaseUrl());
                if ($baseUrl !== '') {
                    $payload['runtimeBaseUrl'] = $baseUrl;
                }
            }
        } catch (\Throwable $e) {
            // Ignore and allow JobUrlResolver to fall back to config/env.
        }

        return $payload;
    }
}
