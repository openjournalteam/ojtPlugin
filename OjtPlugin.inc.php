<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');

use Illuminate\Http\Client\PendingRequest as Http;

class OjtPlugin extends GenericPlugin
{
    public $registeredModule;

    const API = "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v1";

    public function apiUrl()
    {
        return static::API;
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                $this->flushCache();
                $this->createModulesFolder();
                $this->registerModules();
                // HookRegistry::register('Template::Settings::website', array($this, 'settingsWebsite'));
                HookRegistry::register('LoadHandler', [$this, 'setPageHandler']);
                HookRegistry::register('TemplateManager::setupBackendPage', [$this, 'setupBackendPage']);
            }
            return true;
        }
        return false;
    }

    public function flushCache()
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->clearTemplateCache();
        $templateMgr->clearCssCache();

        $cacheMgr = CacheManager::getManager();
        $cacheMgr->flush();
    }

    public function setupBackendPage($hookName, $args)
    {
        $request = $this->getRequest();
        $templateMgr = TemplateManager::getManager($this->getRequest());
        $dispatcher = $request->getDispatcher();
        $router = $request->getRouter();
        $userRoles = (array) $router->getHandler()->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
        // dd(count(array_intersect([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN], $userRoles)));
        if (!$request->getUser() || !count(array_intersect([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN], $userRoles))) return;



        $menu = $templateMgr->getState('menu');
        $menu['ojtPlugin'] = [
            'name' => 'OJT Control Panel',
            'url' => $router->url($request, null, 'ojt'),
            "isCurrent" => false
        ];

        $templateMgr->setState(['menu' => $menu]);
    }

    public function getModulesPath($path = '')
    {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $path;
    }

    public function registerModules()
    {
        $modulesFolder = getDirs($this->getModulesPath());

        import('lib.pkp.classes.site.VersionCheck');

        $plugins = [];

        foreach ($modulesFolder as $moduleFolder) {
            $fileManager = new FileManager();
            $versionFile = $this->getModulesPath($moduleFolder  . DIRECTORY_SEPARATOR . "version.xml");
            $indexFile = $this->getModulesPath(DIRECTORY_SEPARATOR . $moduleFolder . DIRECTORY_SEPARATOR . "index.php");
            if (
                !$fileManager->fileExists($versionFile) ||
                !$fileManager->fileExists($indexFile)
            ) {
                continue;
            }

            $plugin         = include($indexFile);
            if (!$plugin && $plugin instanceof Plugin) {
                continue;
            }

            $version        = VersionCheck::getValidPluginVersionInfo($versionFile);

            $categoryPlugin = explode('.', $version->getData('productType'))[1];
            $categoryDir    = $this->getModulesPath();
            $pluginDir      = $categoryDir . DIRECTORY_SEPARATOR . $moduleFolder;
            PluginRegistry::register($categoryPlugin, $plugin, $pluginDir);

            $data                = $version->getAllData();
            $data['version']     = $version->getVersionString();
            $data['name']        = $plugin->getDisplayName();
            $data['className']   = $plugin->getName();
            $data['description'] = $plugin->getDescription();
            $data['enabled']     = $plugin->getEnabled();
            $data['icon']        = method_exists($plugin, 'getPageIcon') ? $plugin->getPageIcon() : $this->getDefaultPluginIcon();
            if (method_exists($plugin, 'getPage')) {
                $data['page']        = $plugin->getPage();
            }

            $plugins[] = $data;
        }

        $this->registeredModule = $plugins;

        return $plugins;
    }

    public function getDefaultPluginIcon()
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());

        return $templateMgr->fetch($this->getTemplateResource('defaultIcon.tpl'));
    }

    public function createModulesFolder()
    {
        if (is_dir(getcwd() . DIRECTORY_SEPARATOR . $this->getModulesPath())) {
            return;
        }

        mkdir(getcwd() . DIRECTORY_SEPARATOR . $this->getModulesPath());
    }
    // Show available update on Setting -> Website
    function settingsWebsite($hookName, $args)
    {
        if (!$this->getSetting(CONTEXT_SITE, 'isNewVersionAvailable')) {
            return false;
        }

        $templateMgr = $args[1];
        $output = &$args[2];

        $output .= $templateMgr->fetch($this->getTemplateResource('backend/notif.tpl'));

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    public function updatePanel($url)
    {
        // Check ziparchive extension
        if (!class_exists('ZipArchive')) {
            throw new Exception('Please Install PHP Zip Extension');
        }

        // Download file
        $file_name = basename($url);

        // place file to root of ojs
        if (!file_put_contents($file_name, file_get_contents($url))) {
            throw new Exception('Failed to get Plugin');
        }

        $zip = new ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files');
        }

        $path    = 'plugins/generic';
        if (!$zip->extractTo($path)) {
            unlink($file_name);
            throw new Exception('Failed to Extract Plugin, because of folder permission.');
        }
        $zip->close();

        unlink($file_name);
    }

    /**
     * Install default settings on journal creation.
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    public function getPluginVersionFile()
    {
        return $this->getPluginPath() . '/version.xml';
    }

    /**
     * Get the display name of this plugin.
     * @return String
     */
    public function getDisplayName()
    {
        return 'OJT Control Panel';
    }

    /**
     * @copydoc Plugin::getName()
     */
    function getName()
    {
        return 'ojtPlugin';
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return 'Control Panel Service Plugin From OpenJournalTheme.com';
    }

    public function getPluginType()
    {
        import('lib.pkp.classes.site.VersionCheck');
        $info = VersionCheck::getValidPluginVersionInfo($this->getPluginVersionFile());

        return $info[1];
    }

    public function getPluginVersion()
    {
        import('lib.pkp.classes.site.VersionCheck');
        $version = VersionCheck::parseVersionXML($this->getPluginVersionFile());

        return $version['release'];
    }

    public function getPluginName(): String
    {
        return strtolower(__CLASS__);
    }

    public function setPageHandler($hookName, $params)
    {
        $page = $params[0];

        switch ($page) {
            case 'ojt':
                define('HANDLER_CLASS', 'OjtPageHandler');
                $this->import('OjtPageHandler');

                return true;
                break;
        }

        return false;
    }

    /**
     * Add a settings action to the plugin's entry in the
     * plugins list.
     *
     * @param Request $request
     * @param array $actionArgs
     * @return array
     */
    public function getActions($request, $actionArgs)
    {
        // Get the existing actions
        $actions = parent::getActions($request, $actionArgs);

        // Only add the settings action when the plugin is enabled
        if (!$this->getEnabled()) {
            return $actions;
        }

        import('lib.pkp.classes.linkAction.request.OpenWindowAction');
        $linkAction = new LinkAction(
            'ojt_control_panel',
            new OpenWindowAction($request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()) . '/ojt'),
            'Control Panel',
            null
        );

        // Add the LinkAction to the existing actions.
        // Make it the first action to be consistent with
        // other plugins.
        array_unshift($actions, $linkAction);

        return $actions;
    }

    /**
     * Check the folder this $folder is.
     * @return bool - true if folder exist
     */
    public function isPluginExist($folder)
    {
        if (!$folder) {
            return false;
        }

        return is_dir(getcwd() . DIRECTORY_SEPARATOR . $this->getModulesPath() . $folder);
    }

    /**
     * Removing plugin folder
     * @return bool - true if success.
     */
    public function uninstallPlugin($plugin)
    {
        $path    = $this->getModulesPath($plugin->folder);

        if (!is_dir($path)) {
            throw new InvalidArgumentException("$plugin->name not Found");
            return;
        }

        return $this->recursiveDelete($path);
    }

    public function recursiveDelete($dirPath, $deleteParent = true)
    {
        if (empty($dirPath) && !is_dir($dirPath)) {
            return false;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
        if ($deleteParent) {
            rmdir($dirPath);
        }

        return true;
    }

    public function getPluginDownloadLink($pluginToken, $license = false, $journalUrl)
    {
        $payload = [
            'token' => $pluginToken,
            'license' => $license,
            'journal_url' => $journalUrl
        ];

        $agents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.9) Gecko/20100508 SeaMonkey/2.0.4',
            'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1'
        ];

        $request = app(Http::class)
            ->withHeaders([
                'User-Agent' => $agents[rand(0, 3)],
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'x-csrf-uap-admin-token'
            ])
            ->asForm()
            ->post(
                static::API . '/product/get_download_link',
                $payload
            );


        if (!$request->failed()) {
            $response = $request->object();
            if (!$response->error) {
                return $response->data->download_link;
            }
        }
        return false;
    }

    /**
     * Installing plugin to targeted folder.
     * throw error if there is something wrong
     * @return bool - true if success.
     */

    public function installPlugin($url)
    {
        // Download file
        $file_name = __DIR__ . '/' . basename($url);

        // place file to root of ojs
        if (!file_put_contents($file_name, file_get_contents($url))) {
            throw new Exception('Failed to get Plugin');
        }

        // Extract file
        if (!class_exists('ZipArchive')) {
            unlink($file_name);
            throw new Exception('Please Install PHP Zip Extension');
        }

        $zip = new ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files');
        }

        $path    = $this->getModulesPath();
        if (!$zip->extractTo($path)) {
            unlink($file_name);
            throw new Exception('Failed to Extract Plugin, because of folder permission.');
        }
        $zip->close();

        unlink($file_name);
    }

    public function getJournalVersion()
    {
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $version    = $versionDao->getCurrentVersion();
        $data       = $version->_data;
        return $data['major'] . $data['minor'];
    }

    public function clearDataCache()
    {
        $pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO'); // As good as any
        $pluginSettingsDAO->flushCache();

        return true;
    }
}
