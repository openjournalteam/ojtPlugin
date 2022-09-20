<?php
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.site.VersionCheck');

trait OJTPluginUpdater
{   
    abstract protected function getPlugin();

    protected function getOjtPlugin()
    {
        return PluginRegistry::getPlugin('generic', 'ojtplugin');
    }

    public function check_update()
    {
        $plugin = $this->getPlugin();
        $pluginDetail = getPluginDetail($plugin);

        if(! $pluginDetail) {
            return showJson([
                'error' => 1,
                'update_available' => false,
                'msg' => "Failed to check update"
            ]);
        }
        $pluginVersion = VersionCheck::parseVersionXML($plugin->getPluginPath() . '/version.xml');
        $updateAvailable = version_compare($pluginVersion['release'], $pluginDetail['version'], '<');
        return showJson([
            'error' => 0,
            'update_available' => $updateAvailable,
            'msg' => $updateAvailable ? 'Update available' : 'No Update available'
        ]);
    }

    public function update()
    {
        
    }
}