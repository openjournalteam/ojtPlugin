<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');

use GuzzleHttp\Exception\BadResponseException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Openjournalteam\OjtPlugin\Classes\ErrorHandler;
use Openjournalteam\OjtPlugin\Classes\ParamHandler;
use Openjournalteam\OjtPlugin\Classes\ServiceHandler;

class OjtPlugin extends GenericPlugin
{
    public $registeredModule;

    const API = "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v2";
    const SERVICE_API = "https://sp.openjournaltheme.com/";

    public function apiUrl()
    {
        return static::API;
    }

    public static function get()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'ojtPlugin');
        if (!$plugin) return new static();

        return $plugin;
    }

    public function isAllowSendLog($hour = 4)
    {
        $now = time();
        $lastSendLogTime = $this->getSetting(CONTEXT_SITE, 'lastSendLogTime');
        if ($lastSendLogTime === null) {
            return true;
        }

        $diff = $now - $lastSendLogTime;
        $diffInHour = round($diff / (60 * 60));
        return $diffInHour >= $hour;
    }

    public function getHttpClient($headers = [])
    {
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $version    = $versionDao->getCurrentVersion();
        $agents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.9) Gecko/20100508 SeaMonkey/2.0.4',
            'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1'
        ];

        $headers = array_merge($headers, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => $agents[rand(0, 3)],
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'x-csrf-uap-admin-token',
            'ojt_plugin_version' => $this->getPluginVersion(),
            'ojs_version' => $this->getJournalVersion(),
            'ojs_version_detail' => $version->getVersionString(),
            'php_version' => PHP_VERSION,
        ]);

        return new \GuzzleHttp\Client([
            'timeout' => 60,
            'headers' => $headers
        ]);
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getCurrentContextId() == 0) {
                return true;
            }

            if ($this->getEnabled()) {
                register_shutdown_function([$this, 'fatalHandler']);
                $this->init();
                $this->setLogger();
                $this->createModulesFolder();
                // $this->flushCache();
                $this->registerModules();
                // HookRegistry::register('Template::Settings::website', array($this, 'settingsWebsite'));
                HookRegistry::register('LoadHandler', [$this, 'setPageHandler']);
                HookRegistry::register('TemplateManager::setupBackendPage', [$this, 'setupBackendPage']);
                HookRegistry::register('TemplateManager::display', [$this, 'fixThemeNotLoadedOnFrontend']);
                HookRegistry::register('TemplateManager::display', [$this, 'addHeader']);
            }
            return true;
        }
        return false;
    }

    public function init()
    {
        $paramHandler = new ParamHandler($this);
        $paramHandler->handle();
    }

    function fatalHandler()
    {
        $error = error_get_last();
        // Fatal error, E_ERROR === 1
        if (!in_array($error['type'], [E_COMPILE_ERROR, E_ERROR])) return;
        if (!str_contains($error['file'], 'ojtPlugin')) {
            return;
        }

        $folders = explode('/', $error['file']);
        $key = array_search('modules', $folders);
        if (!is_int($key)) {
            return;
        }

        $errorPluginFolder = $folders[$key + 1];
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $errorPluginFolder;
        try {
            if (!is_dir($path)) {
                throw new \Exception("$path is not directory");
                return;
            }
            $this->recursiveDelete($path);
        } catch (\Throwable $th) {
        }
    }

    public static function getErrorLogFile()
    {
        return Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'ojtPlugin' . DIRECTORY_SEPARATOR . 'error.log';
    }

    public function setLogger()
    {
        $logger = new Logger('OJTLog');
        $logger->pushHandler(new ServiceHandler());
        $logger->pushHandler(new StreamHandler(static::getErrorLogFile()));
        ErrorHandler::register($logger);
    }

    public function fixThemeNotLoadedOnFrontend($hookName, $args)
    {
        $templateMgr            = $args[0];
        if ($this->getJournalVersion() != '31') {
            if ($templateMgr->getTemplateVars('activeTheme')) return;
        }

        $allThemes = PluginRegistry::loadCategory('themes', true);
        $activeTheme = null;
        $context = $this->getCurrentContextId() ? $this->getRequest()->getContext() : $this->getRequest()->getSite();
        $themePluginPath = $context->getData('themePluginPath');

        foreach ($allThemes as $theme) {
            if ($themePluginPath === basename($theme->pluginPath) && $theme->getEnabled()) {
                $activeTheme = $theme;
                break;
            }
        }

        $templateMgr->assign('activeTheme', $activeTheme);
    }

    function addHeader($hookName, $args)
    {
        $templateMgr            = &$args[0];

        $templateMgr->addHeader(
            'ojtcontrolpanel',
            '<meta name="ojtcontrolpanel" content="OJT Control Panel Version ' . $this->getPluginVersion() . ' by openjournaltheme.com">',
            [
                'contexts' => ['frontend'],
            ]
        );
    }

    public function flushCache()
    {
        $templateMgr = TemplateManager::getManager($this->getRequest());
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
        if (!$request->getUser() || !count(array_intersect([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN], $userRoles))) return;

        $menu = $templateMgr->getState('menu');
        $menu['ojtPlugin'] = [
            'name' => 'OJT Control Panel',
            'url' => $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath()) . '/ojt?PageSpeed=off',
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
        $modulesFolder = $this->getDirs($this->getModulesPath());

        import('lib.pkp.classes.site.VersionCheck');

        $plugins = [];
        $fileManager = new FileManager();
        foreach ($modulesFolder as $moduleFolder) {
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
            $pluginDir      = $categoryDir .  $moduleFolder;

            PluginRegistry::register($categoryPlugin, $plugin, $pluginDir);

            if ($plugin instanceof ThemePlugin) {
                $plugin->init();
            }

            $data                = $version->getAllData();
            $data['version']     = $version->getVersionString();
            $data['name']        = $plugin->getDisplayName();
            $data['className']   = $plugin->getName();
            $data['description'] = $plugin->getDescription();
            $data['enabled']     = $plugin->getEnabled();
            $data['open']        = false;
            $data['icon']        = method_exists($plugin, 'getPageIcon') ? $plugin->getPageIcon() : $this->getDefaultPluginIcon();
            $data['documentation'] = method_exists($plugin, 'getDocumentation') ? $plugin->getDocumentation() : null;
            $data['page']        = method_exists($plugin, 'getPage') ? $plugin->getPage() : null;

            $plugins[] = $data;
        }

        // HookRegistry::call('PluginRegistry::categoryLoaded::themes');


        $this->registeredModule = $plugins;

        return $plugins;
    }

    public function getRegisteredModules()
    {
        if (!$this->registeredModule) {
            return $this->registerModules();
        }

        return $this->registeredModule;
    }

    public function getDefaultPluginIcon()
    {
        $templateMgr = TemplateManager::getManager($this->getRequest());

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
        if (!$file = file_get_contents($url)) {
            throw new Exception('Failed to download Plugin');
        }
        if (!file_put_contents($file_name, $file)) {
            throw new Exception('Failed to make a temporary plugin file');
        }

        $zip = new ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files plugin file');
        }

        $path    = 'plugins/generic';
        if (!$zip->extractTo($path)) {
            unlink($file_name);
            throw new Exception('Failed to Extract Plugin,maybe because of folder permission.');
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
        $pluginPath = $this->getPluginPath() ?? 'plugins/generic/ojtPlugin';

        return $pluginPath . '/version.xml';
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
        return $this->getName();
    }

    public function getPluginFullUrl($path = '', $withVersion = true)
    {
        $fullUrl =  $this->getRequest()->getBaseUrl() . '/'  . $this->getPluginPath() . '/' . $path;

        if ($withVersion) {
            return $fullUrl . '?v=' . $this->getPluginVersion();
        }

        return $fullUrl;
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
            new OpenWindowAction($request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath()) . '/ojt?PageSpeed=off'),
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
        $path    = $this->getModulesPath($plugin->product);
        try {
            if (!is_dir($path)) {
                throw new Exception("$plugin->name not Found");
                return;
            }
            return $this->recursiveDelete($path);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function recursiveDelete($dirPath, $deleteParent = true)
    {
        if (empty($dirPath) && !is_dir($dirPath)) {
            return false;
        }

        $paths = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($paths as $path) {
            if (!$path->isWritable()) {
                throw new Exception("Can't remove plugins, please check folder permission.");
            };
        }
        foreach ($paths as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
        if ($deleteParent) {
            rmdir($dirPath);
        }

        return true;
    }

    public function getPluginDownloadLink($pluginToken, $license = false, $journalUrl)
    {
        try {
            $payload = [
                'token' => $pluginToken,
                'license' => $license,
                'journal_url' => $journalUrl,
                'ojs_version' => $this->getJournalVersion()
            ];
            $request = $this->getHttpClient(['Content-Type' => 'application/x-www-form-urlencoded',])
                ->post(
                    static::API . '/product/get_download_link',
                    [
                        'form_params' => $payload,
                    ]
                );

            $result = json_decode((string) $request->getBody(), true);

            if (isset($result['error']) && $result['error']) throw new Exception($result['msg']);

            return $result['data']['download_link'];
        } catch (BadResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Installing plugin to targeted folder.
     * throw error if there is something wrong
     * @return bool - true if success.
     */

    public function installPlugin($url)
    {
        $url = str_replace('https', 'http', $url);

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

        return true;
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

    function getDirs($path, $recursive = false, array $filtered = [])
    {
        $this->createModulesFolder();

        if (!is_dir($path)) {
            throw new RuntimeException("$path does not exist.");
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
