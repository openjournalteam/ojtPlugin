<?php

namespace APP\plugins\generic\ojtControlPanel;

use Exception;
use ZipArchive;
use Monolog\Logger;
use PKP\plugins\Hook;
use RuntimeException;
use PKP\config\Config;
use PKP\security\Role;
use PKP\db\DAORegistry;
use PKP\plugins\Plugin;
use PKP\site\VersionDAO;
use APP\core\Application;
use PKP\file\FileManager;
use PKP\site\VersionCheck;
use PKP\cache\CacheManager;
use PKP\plugins\ThemePlugin;
use PKP\linkAction\LinkAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\LazyLoadPlugin;
use PKP\plugins\PluginRegistry;
use APP\template\TemplateManager;
use Monolog\Handler\StreamHandler;
use PKP\linkAction\request\OpenWindowAction;
use GuzzleHttp\Exception\BadResponseException;
use APP\plugins\generic\ojtControlPanel\classes\ErrorHandler;
use APP\plugins\generic\ojtControlPanel\classes\ParamHandler;
use APP\plugins\generic\ojtControlPanel\classes\ServiceHandler;
use Monolog\Utils;
use Psr\Log\LogLevel;
use Throwable;

require_once(dirname(__FILE__) . '/vendor/autoload.php');

class OjtControlPanelPlugin extends GenericPlugin
{
    public $registeredModule;

    const API = "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v3";
    const SERVICE_API = "https://sp.openjournaltheme.com/";

    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                // register_shutdown_function([$this, 'fatalHandler']);
                $this->init();
                $this->setLogger();
                $this->createModulesFolder();
                $this->registerModules();
                // HookRegistry::register('Template::Settings::website', array($this, 'settingsWebsite'));
                Hook::add('LoadHandler', [$this, 'setPageHandler']);
                Hook::add('TemplateManager::setupBackendPage', [$this, 'setupBackendPage']);
                Hook::add('TemplateManager::display', [$this, 'fixThemeNotLoadedOnFrontend']);
                Hook::add('TemplateManager::display', [$this, 'addHeader']);
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

    public function apiUrl()
    {
        return static::API;
    }

    public static function get(): OjtControlPanelPlugin
    {
        $plugin = PluginRegistry::getPlugin('generic', 'OjtControlPanelPlugin');
        if (!$plugin) return new static();

        return $plugin;
    }

    public function isAllowSendLog($hour = 4): bool
    {
        $now = time();
        $lastSendLogTime = $this->getSetting(Application::CONTEXT_SITE, 'lastSendLogTime');
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
        /** @var VersionDAO $versionDao */
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
            'ojt-plugin-version' => $this->getPluginVersion(),
            'ojs-version' => $this->getJournalVersion(),
            'ojs-version-detail' => $version->getVersionString(),
            'php-version' => PHP_VERSION,
        ]);

        return new \GuzzleHttp\Client([
            'timeout' => 60,
            'headers' => $headers
        ]);
    }

    /**
     * Remove modules disaat terjadi fatal error 
     */
    function fatalHandler()
    {
        $error = error_get_last();
        // Fatal error, E_ERROR === 1
        if (!in_array($error['type'], [E_COMPILE_ERROR, E_ERROR])) return;
        if (!str_contains($error['file'], 'ojtControlPanel')) {
            return;
        }

        /**
         * Get folder name from error file
         */
        $folders = explode('/', $error['file']);
        $key = array_search('modules', $folders);
        if (!is_int($key)) {
            return;
        }
        $errorPluginFolder = $folders[$key + 1];

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $errorPluginFolder;
        try {
            if (!is_dir($path)) {
                throw new Exception("$path is not directory");
                return;
            }
            $this->recursiveDelete($path);
        } catch (\Throwable $th) {
        }
    }

    public static function getErrorLogFile(): string
    {
        return Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'ojtPlugin' . DIRECTORY_SEPARATOR . 'error.log';
    }

    public static function deleteLogFile(): bool
    {
        $errorLogFile = static::getErrorLogFile();
        if (!is_file($errorLogFile)) return false;


        return unlink($errorLogFile);
    }

    public function isTimeToDeleteLog($days = 2)
    {
        $errorLogFile = static::getErrorLogFile();

        if (!is_file($errorLogFile)) return false;

        $dateCreatedFile = filemtime($errorLogFile);

        $now = time();
        $datediff = $now - $dateCreatedFile;
        $diffInDays = round($datediff / (60 * 60 * 24));

        return $diffInDays > $days;
    }

    public function setLogger()
    {
        // Jangan simpan log error ketika setting ini didisable
        if (!$this->isDiagnosticEnabled()) return;

        $logger = new Logger('OJTLog');
        $logger->pushHandler(new StreamHandler(static::getErrorLogFile()));
        $logger->pushHandler(new ServiceHandler());

        set_exception_handler(function (Throwable $e) use ($logger): void {
            if ($this->isTimeToDeleteLog()) {
                static::deleteLogFile();
            };

            $logger->log(
                LogLevel::ERROR,
                sprintf('Uncaught Exception %s: "%s" at %s line %s', Utils::getClass($e), $e->getMessage(), $e->getFile(), $e->getLine()),
                ['exception' => $e]
            );

            throw $e;
        });

        set_error_handler(function (int $code, string $message, string $file = '', int $line = 0, ?array $context = []) use ($logger): bool {
            if ($code !== E_ERROR) return false;

            if ($this->isTimeToDeleteLog()) {
                static::deleteLogFile();
            };


            $logger->log(LogLevel::CRITICAL, 'E_ERROR: ' . $message, ['code' => $code, 'message' => $message, 'file' => $file, 'line' => $line]);
            return false;
        });
    }

    public function fixThemeNotLoadedOnFrontend($hookName, $args)
    {
        $templateMgr            = $args[0];

        if ($templateMgr->getTemplateVars('activeTheme')) return;

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

        if (!$request->getContext()) return;

        $templateMgr = TemplateManager::getManager($this->getRequest());
        $dispatcher = $request->getDispatcher();
        $router = $request->getRouter();
        $userRoles = (array) $router->getHandler()->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);
        if (!$request->getUser() || !count(array_intersect([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN], $userRoles))) return;

        $menu = $templateMgr->getState('menu');
        $menu['ojtPlugin'] = [
            'name' => 'OJT Control Panel',
            'url' => $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $request->getContext()->getPath()) . '/ojt?PageSpeed=off',
            "isCurrent" => false,
        ];

        $templateMgr->setState(['menu' => $menu]);
    }

    public function getModulesPath($path = '')
    {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $path;
    }

    public function registerModules(): void
    {
        $modulesFolder = $this->getDirs($this->getModulesPath());

        $plugins = [];
        foreach ($modulesFolder as $moduleFolder) {
            $plugin = $this->instatiatePlugin($moduleFolder);

            $versionFile = $this->getModulesPath($moduleFolder  . DIRECTORY_SEPARATOR . "version.xml");
            $version        = VersionCheck::getValidPluginVersionInfo($versionFile);
            
            $versionDao = DAORegistry::getDAO('VersionDAO');
            $versionDao->disableVersion($version->getData('productType'), $version->getData('product'));

            $categoryPlugin = explode('.', $version->getData('productType'))[1];
            $categoryDir    = $this->getModulesPath();
            $pluginDir      = $categoryDir .  $moduleFolder;

            if ($plugin->getEnabled() || $this->getCurrentContextId() == Application::CONTEXT_SITE) {
                PluginRegistry::register($categoryPlugin, $plugin, $pluginDir);
                if ($plugin instanceof ThemePlugin) {
                    $plugin->init();
                }
            } else {
                // manually load locale data for disabled plugins
                $plugin->pluginPath = $pluginDir;
                $plugin->addLocaleData();
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

        $this->registeredModule = $plugins;
    }

    public function getRegisteredModules()
    {
        if (!$this->registeredModule) {
            $this->registerModules();
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
        if (!$this->getSetting(Application::CONTEXT_SITE, 'isNewVersionAvailable')) {
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
        $file_name = "OJTPanel.zip";

        // place file to root of ojs
        if (!$file = file_get_contents($url)) {
            throw new Exception('Failed to download Plugin');
        }
        if (!file_put_contents($file_name, $file)) {
            throw new Exception('Failed to make a temporary plugin file');
        }

        $zip = new \ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files plugin file');
        }

        $path    = 'plugins' . DIRECTORY_SEPARATOR . 'generic';
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
    public function getContextSpecificPluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    public function getPluginVersionFile(): string
    {
        $pluginPath = $this->getPluginPath() ?? 'plugins/generic/ojtControlPanel';
        return $pluginPath . '/version.xml';
    }

    /**
     * Get the display name of this plugin.
     * @return String
     */
    public function getDisplayName(): string
    {
        return 'OJT Control Panel';
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription(): string
    {
        return 'Control Panel Service Plugin From OpenJournalTheme.com';
    }

    public function getPluginType()
    {
        $info = VersionCheck::getValidPluginVersionInfo($this->getPluginVersionFile());

        return $info[1];
    }

    public function getName()
    {
        return 'OjtControlPanelPlugin';
    }

    public function getPluginVersion()
    {
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
        $handler = &$params[3];
        if ($this->getCurrentContextId() == 0) {
            // Panel tidak support untuk sitewide 
            return false;
        }

        $page = $params[0];

        switch ($page) {
            case 'ojt':
                $handler = new OjtPageHandler($this->getRequest());

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
            new OpenWindowAction($request->getDispatcher()->url($request, Application::ROUTE_PAGE, $request->getContext()->getPath()) . '/ojt?PageSpeed=off'),
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
    public function uninstallPlugin($plugin): bool
    {
        $path    = $this->getModulesPath($plugin->product);
        try {
            if (!is_dir($path)) {
                throw new Exception("$plugin->name not Found");
            }
            return $this->recursiveDelete($path);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function recursiveDelete($dirPath, $deleteParent = true): bool
    {
        $fileManager = new FileManager();

        return $fileManager->rmtree($dirPath);
    }

    public function getJournalURL(): string
    {
        $request = $this->getRequest();
        return $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $request->getContext()->getPath());
    }

    public function getPluginDownloadLink($pluginToken, $license = false): array
    {
        try {
            $payload = [
                'token' => $pluginToken,
                'license' => $license,
                'journal_url' => $this->getJournalURL(),
                'ojs_version' => $this->getJournalVersion()
            ];
            $request = $this->getHttpClient(['Content-Type' => 'application/x-www-form-urlencoded',])
                ->post(
                    static::API . '/product/get_download_link',
                    [
                        'form_params' => $payload,
                    ]
                );

            $response = json_decode((string) $request->getBody(), true);

            if (isset($response['error']) && $response['error']) throw new Exception($response['msg']);

            $result['product'] = $response['data']['download_link'];

            $dependencies = [];
            foreach ($response['data']['dependencies'] as $dependency) {
                $data['link'] = $dependency['download_link'];
                $data['folder'] = $dependency['folder'];
                $dependencies[] = $data;
            }

            $result['dependencies'] = $dependencies;
            return $result;
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
        $file_name = __DIR__ . DIRECTORY_SEPARATOR . 'OJTTemporaryFile.zip';
        $resource = \GuzzleHttp\Psr7\Utils::tryFopen($file_name, 'w');
        $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
        $this->getHttpClient()->request('GET', $url, ['sink' => $stream]);

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
        /** @var VersionDAO $versionDao */
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
            if (is_dir("$path" . DIRECTORY_SEPARATOR . "$entry") && !in_array($entry, $filtered)) {
                $dirs[] = $entry;

                if ($recursive) {
                    $newDirs = $this->getDirs("$path" . DIRECTORY_SEPARATOR . "$entry");
                    foreach ($newDirs as $newDir) {
                        $dirs[] = "$path" . DIRECTORY_SEPARATOR . "$entry";
                    }
                }
            }
        }
        sort($dirs);

        return $dirs;
    }

    public function isDiagnosticEnabled()
    {
        return $this->getSetting(Application::CONTEXT_SITE, 'enable_diagnostic') ?? true;
    }

    public function instatiatePlugin($moduleFolder): LazyLoadPlugin
    {
        $fileManager = new FileManager();
        $plugin = null;
        $versionFile = $this->getModulesPath($moduleFolder  . DIRECTORY_SEPARATOR . "version.xml");
        if (
            !$fileManager->fileExists($versionFile)
        ) {
            throw new Exception("Plugin $moduleFolder not found");
        }

        $version        = VersionCheck::getValidPluginVersionInfo($versionFile);
        $pluginClassName = __NAMESPACE__ . "\\modules\\{$moduleFolder}\\" .  $version->getProductClassName();
        if (!class_exists($pluginClassName)) {
            $indexFile = $this->getModulesPath(DIRECTORY_SEPARATOR . $moduleFolder . DIRECTORY_SEPARATOR . "index.php");
            if (!$fileManager->fileExists($indexFile)) {
                throw new Exception("Plugin with classname : $pluginClassName not found");
            }
            $plugin = @include($indexFile);
        }


        $plugin         = $plugin ?? new $pluginClassName();
        if (!$plugin && $plugin instanceof Plugin) {
            throw new Exception("Plugin with classname : $pluginClassName not found");
        }

        return $plugin;
    }

    public function instatiantePluginWithoutThrow($moduleFolder): ?LazyLoadPlugin
    {
        try {
            return $this->instatiatePlugin($moduleFolder);
        } catch (\Throwable $th) {
            return null;
        }
    }
}
