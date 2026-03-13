<?php

namespace Openjournalteam\OjtPlugin\Jobs;

use OpenJournalteam\OjtPlugin\Jobs\JobInterface;

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

        import('plugins.generic.ojtPlugin.modules.ojtWorkerBee.OjtWorkerBeePlugin');
        $plugin = \OjtPlugin::get();
        if (!$plugin || !$plugin->isEnabledForRuntime()) {
            error_log('OjtBlazingCachePro: OjtWorkerBee plugin is not installed or enabled.');
            self::$ojtPlugin = false;
            return null;
        }

        self::$ojtPlugin = $plugin;
        return $plugin;
    }
}
