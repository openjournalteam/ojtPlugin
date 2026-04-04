<?php

namespace Openjournalteam\OjtPlugin\Services;

class ModulesService
{
    private \OjtPlugin $plugin;

    public function __construct(\OjtPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getModulesPath($path = '')
    {
        return $this->plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $path;
    }

    public function createModulesFolder()
    {
        if (is_dir(getcwd() . DIRECTORY_SEPARATOR . $this->getModulesPath())) {
            return;
        }

        mkdir(getcwd() . DIRECTORY_SEPARATOR . $this->getModulesPath());
    }

    public function registerModules()
    {
        $modulesFolder = $this->getDirs($this->getModulesPath());

        import('lib.pkp.classes.site.VersionCheck');

        $plugins = [];
        $fileManager = new \FileManager();
        foreach ($modulesFolder as $key => $moduleFolder) {
            $versionFile = $this->getModulesPath($moduleFolder  . DIRECTORY_SEPARATOR . "version.xml");
            $indexFile = $this->getModulesPath(DIRECTORY_SEPARATOR . $moduleFolder . DIRECTORY_SEPARATOR . "index.php");
            if (
                !$fileManager->fileExists($versionFile) ||
                !$fileManager->fileExists($indexFile)
            ) {
                continue;
            }

            $plugin         = include($indexFile);
            if (!$plugin && $plugin instanceof \Plugin) {
                continue;
            }

            $version        = \VersionCheck::getValidPluginVersionInfo($versionFile);

            $categoryPlugin = explode('.', $version->getData('productType'))[1];
            $categoryDir    = $this->getModulesPath();
            $pluginDir = str_replace('\\', '/', $categoryDir .  $moduleFolder);

            \PluginRegistry::register($categoryPlugin, $plugin, $pluginDir);

            if ($plugin instanceof \ThemePlugin) {
                $plugin->init();
            }

            $data                = $version->getAllData();
            $data['version']     = $version->getVersionString();
            $data['name']        = $plugin->getDisplayName();
            $data['className']   = $plugin->getName();
            $data['description'] = $plugin->getDescription();
            $data['enabled']     = $plugin->getEnabled();

            if (method_exists($plugin, 'getCanEnable') && !$plugin->getCanEnable()) {
                $data['isAuthorized']   = $plugin->getCanEnable();
            } else {
                $data['isAuthorized']   = $this->plugin->getCanEnable();
            }

            $data['open']        = false;
            $data['icon']        = method_exists($plugin, 'getPageIcon') ? $plugin->getPageIcon() : $this->plugin->getDefaultPluginIcon();
            $data['documentation'] = method_exists($plugin, 'getDocumentation') ? $plugin->getDocumentation() : null;
            $data['page']        = method_exists($plugin, 'getPage') ? $plugin->getPage() : null;
            $data['sitemapData'] = method_exists($plugin, 'getSitemapData') ? $plugin->getSitemapData() : null;
            $data['canDelete']   = method_exists($plugin, 'getCanDelete') ? $plugin->getCanDelete() : true;

            $plugins[] = $data;
        }

        $this->plugin->registeredModule = $plugins;

        return $plugins;
    }

    public function getRegisteredModules()
    {
        if (!$this->plugin->registeredModule) {
            return $this->registerModules();
        }

        return $this->plugin->registeredModule;
    }

    public function getDirs($path, $recursive = false, array $filtered = [])
    {
        $this->createModulesFolder();

        if (!is_dir($path)) {
            throw new \RuntimeException("$path does not exist.");
        }

        $filtered += ['.', '..', '.git', 'pluginTemplate'];

        $dirs = [];
        $d = dir($path);

        while (($entry = $d->read()) !== false) {
            if (is_dir("$path/$entry") && !in_array($entry, $filtered)) {
                $dirs[] = $entry;

                if ($recursive) {
                    $newDirs = $this->getDirs("$path/$entry");
                    foreach ($newDirs as $newDir) {
                        $dirs[] = "$entry/$newDir";
                    }
                }
            }
        }
        sort($dirs);

        return $dirs;
    }
}
