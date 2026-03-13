<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');

use Openjournalteam\OjtPlugin\Classes\ApiServicePanel;
use Openjournalteam\OjtPlugin\Classes\ParamHandler;
use Openjournalteam\OjtPlugin\Classes\DiscordNotifier;
use Openjournalteam\OjtPlugin\Services\JobQueueService;
use Openjournalteam\OjtPlugin\Services\HookService;
use Openjournalteam\OjtPlugin\Services\InstallerService;
use Openjournalteam\OjtPlugin\Services\LoggingService;
use Openjournalteam\OjtPlugin\Services\ModulesService;
use Openjournalteam\OjtPlugin\Traits\HasIndexing;
use VersionDAO;

class OjtPlugin extends \GenericPlugin
{
    use HasIndexing;

    public $registeredModule;
    private ?HookService $hookService = null;
    private ?InstallerService $installerService = null;
    private ?LoggingService $loggingService = null;
    private ?ModulesService $modulesService = null;
    private ?JobQueueService $jobQueueService = null;

    const API = "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v3";
    const SERVICE_API = "https://sp.openjournaltheme.com/";

    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                // register_shutdown_function([$this, 'fatalHandler']);
                $this->init();
                $this->jobQueueService();
                $this->loggingService()->setLogger();
                $this->modulesService()->createModulesFolder();
                $this->modulesService()->registerModules();
                $this->hookService()->registerHooks();
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

    /**
     * Send a notification to Discord about a plugin removal event.
     *
     * @param string $pluginFolder The folder name of the plugin being removed.
     * @param array $data The error details associated with the removal.
     * @return void
     */
    public function sendDiscordNotification($pluginFolder, $data)
    {
        $discordNotifier = new DiscordNotifier($this);
        $discordNotifier->notifyPluginError($pluginFolder, $data);
    }

    private function hookService(): HookService
    {
        if ($this->hookService === null) {
            $this->hookService = new HookService($this);
        }
        return $this->hookService;
    }

    private function installerService(): InstallerService
    {
        if ($this->installerService === null) {
            $this->installerService = new InstallerService($this);
        }
        return $this->installerService;
    }

    private function loggingService(): LoggingService
    {
        if ($this->loggingService === null) {
            $this->loggingService = new LoggingService($this);
        }
        return $this->loggingService;
    }

    private function modulesService(): ModulesService
    {
        if ($this->modulesService === null) {
            $this->modulesService = new ModulesService($this);
        }
        return $this->modulesService;
    }

    public function jobQueueService(): JobQueueService
    {
        if ($this->jobQueueService === null) {
            $this->jobQueueService = new JobQueueService($this);
        }
        return $this->jobQueueService;
    }

    /**
     * Determine whether the plugin can be enabled.
     * @return boolean
     */
    function getCanEnable()
    {
        return $this->getCanDisable();
    }

    function getCanDisable()
    {
        if ($this->isCurrentUserAreJournalManager()) return true;

        $currentUser = $this->getRequest()->getUser();
        if (!$currentUser) return false;

        return $currentUser->hasRole([ROLE_ID_SITE_ADMIN], CONTEXT_SITE);
    }

    public function isCurrentUserAreJournalManager()
    {
        $currentUser = $this->getRequest()->getUser();
        if (!$currentUser) return false;

        $userGroupDao = \DAORegistry::getDAO('UserGroupDAO'); /** @var \UserGroupDAO $userGroupDao */
        $currentUserGroups = $userGroupDao->getByUserId($currentUser->getId(), $this->getCurrentContextId());

        $currentUserGroupNameLocaleKeys = collect($currentUserGroups->toArray())->map(function ($userGroup) {
            return $userGroup->getData('nameLocaleKey');
        })->toArray();

        if (in_array('default.groups.name.manager', $currentUserGroupNameLocaleKeys)) return true;

        return false;
    }

    public function apiUrl()
    {
        return static::API;
    }

    public static function get()
    {
        $plugin = \PluginRegistry::getPlugin('generic', 'ojtPlugin');
        if (!$plugin) return new static();

        return $plugin;
    }

    public function getHttpClient($headers = [])
    {

        $versionDao = \DAORegistry::getDAO('VersionDAO'); /** @var \VersionDAO $versionDao */
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
            'journal-url' => $this->getJournalURL(),
            'php-version' => PHP_VERSION,
        ]);

        return new \GuzzleHttp\Client([
            'timeout' => 60,
            'headers' => $headers
        ]);
    }

    public static function dispatch($jobType, $payload = [], $options = [])
    {
        $plugin = self::get();
        if (!$plugin) {
            error_log('OjtWorkerBee dispatch skipped: plugin instance not found.');
            return null;
        }

        if (!$plugin->isEnabledForRuntime()) {
            error_log('OjtWorkerBee dispatch skipped: plugin is not enabled for runtime.');
            return null;
        }

        $jobId = $plugin->jobQueueService()->dispatch($jobType, $payload, $options);
        if (!$jobId) {
            error_log('OjtWorkerBee dispatch returned empty job id for type "' . (string) $jobType . '".');
        }

        return $jobId;
    }

    /**
     * Remove modules disaat terjadi fatal error
     */
    function fatalHandler()
    {
        $error = error_get_last();

        // Sometimes fatalHandler called without error
        if (!is_array($error)) return;

        // Fatal error, E_ERROR === 1
        if (array_key_exists('type', $error) && !in_array($error['type'], [E_COMPILE_ERROR, E_ERROR])) return;

        // Sometime there's no file in error so we need to check it first
        if (!array_key_exists('file', $error)) return;

        $standalonePlugins = [
            [
                'name'      => 'ojtAdvanceSecurity',
                'urlPath'   => 'generic' . DIRECTORY_SEPARATOR . 'ojtAdvanceSecurity',
            ]
        ];

        $data['error'] = $error;

        if (ojt_str_contains($error['file'], 'ojtPlugin')) {
            $folders = explode('/', $error['file']);
            $key = array_search('modules', $folders);
            if (is_int($key)) {
                $errorPluginFolder = $folders[$key + 1];
                $path = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $errorPluginFolder;
                $plugin = include($path . DIRECTORY_SEPARATOR . 'index.php');
                $isRemoveAllowed = true;
                if (!$plugin && $plugin instanceof \Plugin) return;

                // check if plugin can be deleted
                if (method_exists($plugin, 'getCanDelete')) {
                    $isRemoveAllowed = $plugin->getCanDelete();
                }

                if ($isRemoveAllowed) {
                    try {
                        if (!is_dir($path)) {
                            throw new \Exception("$path is not directory");
                            return;
                        }

                        $this->installerService()->recursiveDelete($path);
                        $this->sendDiscordNotification($errorPluginFolder, $data);
                    } catch (\Throwable $th) {
                        $data['error_type'] = 'pluginRemoveError';
                        $this->sendDiscordNotification($errorPluginFolder, $data);
                    }
                    return;
                }

                // passed remove plugin, send notify error
                try {
                    if (!is_dir($path)) {
                        throw new \Exception("$path is not directory");
                        return;
                    }

                    $data['error_type'] = 'notifyError';
                    $this->sendDiscordNotification($errorPluginFolder, $data);
                } catch (\Throwable $th) {
                }
                return;
            }
        }

        foreach($standalonePlugins as $genericPlugin) {
            if (ojt_str_contains($error['file'], $genericPlugin['name'])) {
                $folders = explode('/', $error['file']);
                $key = array_search('generic', $folders);

                if (is_int($key)) {
                    $path = explode('generic', $error['file'])[0] . $genericPlugin['urlPath'];
                    $plugin = include($path . DIRECTORY_SEPARATOR . 'index.php');

                    $isRemoveAllowed = true;
                    if (!$plugin && $plugin instanceof \Plugin) return;

                    // check if plugin can be deleted
                    if (method_exists($plugin, 'getCanDelete')) {
                        $isRemoveAllowed = $plugin->getCanDelete();
                    }

                    if ($isRemoveAllowed) {
                        try {
                            if (!is_dir($path)) {
                                throw new \Exception("$path is not directory");
                                return;
                            }

                            $this->installerService()->recursiveDelete($path);

                            $this->sendDiscordNotification($genericPlugin['name'], $data);
                        } catch (\Throwable $th) {
                            $data['error_type'] = 'pluginRemoveError';
                            $this->sendDiscordNotification($genericPlugin['name'], $data);
                        }
                        return;
                    }

                    try {
                        if (!is_dir($path)) {
                            throw new \Exception("$path is not directory");
                            return;
                        }

                        $data['error_type'] = 'notifyError';
                        $this->sendDiscordNotification($genericPlugin['name'], $data);
                    } catch (\Throwable $th) {
                    }
                    return;
                }
            }
        }
    }

    public function flushCache()
    {
        $templateMgr = \TemplateManager::getManager($this->getRequest());
        $templateMgr->clearTemplateCache();
        $templateMgr->clearCssCache();

        $cacheMgr = \CacheManager::getManager();
        $cacheMgr->flush();
    }

    public function registerHooks(): void
    {
        $this->hookService()->registerHooks();
    }

    public function fixThemeNotLoadedOnFrontend($hookName, $args)
    {
        return $this->hookService()->fixThemeNotLoadedOnFrontend($hookName, $args);
    }

    public function addHeader($hookName, $args)
    {
        return $this->hookService()->addHeader($hookName, $args);
    }

    public function setupBackendPage($hookName, $args)
    {
        return $this->hookService()->setupBackendPage($hookName, $args);
    }

    public function setPageHandler($hookName, $params)
    {
        return $this->hookService()->setPageHandler($hookName, $params);
    }

    public function settingsWebsite($hookName, $args)
    {
        return $this->hookService()->settingsWebsite($hookName, $args);
    }

    public function addIndexingPage($hookName, $args)
    {
        return $this->hookService()->addIndexingPage($hookName, $args);
    }

    public function getModulesPath($path = '')
    {
        return $this->modulesService()->getModulesPath($path);
    }

    public function createModulesFolder()
    {
        return $this->modulesService()->createModulesFolder();
    }

    public function registerModules()
    {
        return $this->modulesService()->registerModules();
    }

    public function getRegisteredModules()
    {
        return $this->modulesService()->getRegisteredModules();
    }

    public function getDirs($path, $recursive = false, array $filtered = [])
    {
        return $this->modulesService()->getDirs($path, $recursive, $filtered);
    }

    public function isAllowSendLog($hour = 4)
    {
        return $this->loggingService()->isAllowSendLog($hour);
    }

    public static function getErrorLogFile()
    {
        return static::$loggingService->getErrorLogFile();
    }

    public function deleteLogFile(): bool
    {
        return $this->loggingService()->deleteLogFile();
    }

    public function isTimeToDeleteLog($days = 2)
    {
        return $this->loggingService()->isTimeToDeleteLog($days);
    }

    public function setLogger()
    {
        return $this->loggingService()->setLogger();
    }

    public function isDiagnosticEnabled()
    {
        return $this->loggingService()->isDiagnosticEnabled();
    }

    public function updatePanel($url)
    {
        return $this->installerService()->updatePanel($url);
    }

    public function getPluginDownloadLink($pluginToken, $license = false, $journalUrl)
    {
        return $this->installerService()->getPluginDownloadLink($pluginToken, $license, $journalUrl);
    }

    public function installPlugin($url)
    {
        return $this->installerService()->installPlugin($url);
    }

    public function getStagingBasePath()
    {
        return $this->installerService()->getStagingBasePath();
    }

    public function installPluginToStaging($url)
    {
        return $this->installerService()->installPluginToStaging($url);
    }

    public function moveStagedPluginToFinal($stagingPath, $pluginFolder, $isSiteWide)
    {
        return $this->installerService()->moveStagedPluginToFinal($stagingPath, $pluginFolder, $isSiteWide);
    }

    public function instantiatePluginWithoutThrow($pluginFolder)
    {
        return $this->installerService()->instantiatePluginWithoutThrow($pluginFolder);
    }

    public function instantiatePluginFromGlobalDirectory($pluginFolder)
    {
        return $this->installerService()->instantiatePluginFromGlobalDirectory($pluginFolder);
    }

    public function cleanupOldStagingDirectories($hoursOld = 24)
    {
        return $this->installerService()->cleanupOldStagingDirectories($hoursOld);
    }

    public function uninstallPlugin($plugin)
    {
        return $this->installerService()->uninstallPlugin($plugin);
    }

    public function recursiveDelete($dirPath, $deleteParent = true)
    {
        return $this->installerService()->recursiveDelete($dirPath, $deleteParent);
    }

    public function isEnabledForRuntime()
    {
        if ($this->getEnabled()) {
            return true;
        }

        $pluginName = strtolower_codesafe($this->getName());
        $pluginSettingsDao = \DAORegistry::getDAO('PluginSettingsDAO');
        $result = $pluginSettingsDao->retrieve(
            'SELECT setting_value FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND setting_value IN (?, ?, ?, ?) LIMIT 1',
            [
                $pluginName,
                'enabled',
                '1',
                'true',
                'on',
                'yes',
            ]
        );

        return (bool) $result->current();
    }

    public static function reportToServicePanel($plugin, $isGlobalPlugin = false, $params = [], $force = false)
    {
        $ojtPlugin = new self();
        
        if (!$plugin->getEnabled()) return;
        
        if (!$force) {
            $serviceData = $plugin->getSetting(CONTEXT_SITE, 'service_panel_data');
            
            if ($serviceData && isset($serviceData['url'])) {
                $serviceData['journal_site'] = $serviceData['url'];
                unset($serviceData['url']);
                $plugin->updateSetting(CONTEXT_SITE, 'service_panel_data', $serviceData);
            }

            if ($serviceData) return;
        }

        $apiService = ApiServicePanel::make($plugin);

        $params['product-class'] = get_class($plugin);

        if ($isGlobalPlugin) {
            $headers['Client-Url'] = $plugin->getRequest()->getBaseUrl();
        } else {
            $headers ['Client-Url'] = $ojtPlugin->getJournalURL();
        }

        try {
            $response = $apiService->registerClient($params, $headers);

            $plugin->updateSetting(CONTEXT_SITE, 'service_panel_data', $response['journal_data']);

            return true;
        } catch (\Throwable $th) {
            // throw $th;
            return false;
        }
    }

    public function getDefaultPluginIcon()
    {
        // In some ojs this func trigger error, can't read defaultIcon.tpl
        // $templateMgr = TemplateManager::getManager($this->getRequest());
        // return $templateMgr->fetch($this->getTemplateResource('defaultIcon.tpl'));
        return '<svg class="ojt-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
</svg>';
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

    public function getDisplayName()
    {
        return 'OJT Control Panel';
    }

    public function getName()
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

    public function getPluginVersion()
    {
        import('lib.pkp.classes.site.VersionCheck');
        $version = \VersionCheck::parseVersionXML($this->getPluginVersionFile());
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
            new \OpenWindowAction($request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath()) . '/ojt?PageSpeed=off'),
            'Control Panel',
            null
        );

        // Add the LinkAction to the existing actions.
        // Make it the first action to be consistent with
        // other plugins.
        array_unshift($actions, $linkAction);

        return $actions;
    }

    public function getJournalURL()
    {
        $request = $this->getRequest();
        return $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath());
    }

    public function getJournalVersion()
    {
        $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
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

    public function getAssetUrl($asset)
    {
        return $this->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR  . $asset;
    }
}
