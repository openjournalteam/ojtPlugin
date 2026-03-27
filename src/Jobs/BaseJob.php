<?php

namespace Openjournalteam\OjtPlugin\Jobs;

abstract class BaseJob implements JobInterface
{
    /** @var \LazyLoadPlugin */
    protected $plugin;

    /** @var \OjtPlugin|false|null */
    protected static $ojtPlugin = null;

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
