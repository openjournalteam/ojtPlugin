<?php

namespace Openjournalteam\OjtPlugin\Traits;

import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.site.VersionCheck');

trait PluginUpdater
{
    abstract protected function getPlugin();
    abstract protected function getBaseUrl();

    protected function getOjtPlugin()
    {
        return \PluginRegistry::getPlugin('generic', 'ojtPlugin');
    }

    public function check_update()
    {
        $plugin = $this->getPlugin();
        $pluginDetail = getPluginDetail($plugin);

        if (!$pluginDetail) {
            return showJson([
                'error' => 1,
                'update_available' => false,
                'msg' => "Failed to check update"
            ]);
        }
        $pluginVersion = \VersionCheck::parseVersionXML($plugin->getPluginPath() . '/version.xml');
        $updateAvailable = version_compare($pluginVersion['release'], $pluginDetail['version'], '<');
        return showJson([
            'error' => 0,
            'update_available' => $updateAvailable,
            'msg' => $updateAvailable ? 'Update available' : 'No Update available'
        ]);
    }

    public function update()
    {
        $plugin = $this->getPlugin();
        $ojtPlugin = $this->getOjtPlugin();
        $pluginDetail = getPluginDetail($plugin);

        if (!$pluginDetail) {
            return showJson([
                'error' => 1,
                'msg' => "Failed to fetch plugin's data"
            ]);
        }
        $currentLicense = $plugin->getSetting($plugin->getCurrentContextId(), 'license') ?? false;
        $downloadLink = $ojtPlugin->getPluginDownloadLink($pluginDetail['token'], $currentLicense, $this->getBaseUrl());

        if (!$downloadLink) {
            return showJson([
                'error' => 1,
                'msg' => 'Failed to get plugin update link'
            ]);
        }
        try {
            $ojtPlugin->installPlugin($downloadLink);
            return showJson([
                'error' => 0,
                'msg' => "Plugin updated successfully"
            ]);
        } catch (\Exception $e) {
            return showJson([
                'error' => 1,
                'msg' => "Failed to update plugin : " . $e->getMessage()
            ]);
        }
    }
}
