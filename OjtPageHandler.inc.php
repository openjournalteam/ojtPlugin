<?php

use GuzzleHttp\Exception\BadResponseException;

import('classes.handler.Handler');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.plugins.Plugin');

class OjtPageHandler extends Handler
{
    /** @var OjtPlugin  */
    public $ojtPlugin;
    public $contextId;
    public $baseUrl;

    public function __construct($request)
    {
        parent::__construct();

        $this->addRoleAssignment(
            [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER],
            ['index', 'getInstalledPlugin', 'updatePanel', 'settings', 'saveSettings', 'downloadLog', 'reportBug', 'submitBug', 'checkUpdate', 'getPluginGalleryList', 'getExclusivePlugins', 'save', 'installPlugin', 'uninstallPlugin', 'checkPluginInstalled', 'toggleInstalledPlugin', 'resetSetting', 'support'],
        );

        $this->ojtPlugin = OjtPlugin::get();

        $this->contextId = $this->ojtPlugin->getCurrentContextId();
        $this->baseUrl = $this->ojtPlugin->getJournalURL();
    }

    /**
     * @copydoc PKPHandler::authorize
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PolicySet');
        $rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

        import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function updatePanel($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $ojtPlugin = json_decode($request->getUserVar('ojtPlugin'));

        $url = $ojtPlugin->link_download;
        // trying to install plugin
        try {
            $plugin->updatePanel($url);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }


        $json['error']  = 0;
        $json['msg']    = 'Success updating plugin.';
        return showJson($json);
    }

    public function index($args, $request)
    {
        $plugin = $this->ojtPlugin;
        $baseUrl = $request->getBaseUrl() . '/';
        $pluginFullUrl          = $baseUrl . $plugin->getPluginPath();
        $templateMgr            = TemplateManager::getManager($request);

        $publicFileManager = new PublicFileManager();

        $publicFolder      = ($plugin->getJournalVersion() > '31')
            ? $baseUrl . $publicFileManager->getContextFilesPath($this->contextId) . '/'
            : $baseUrl . $publicFileManager->getContextFilesPath(ASSOC_TYPE_JOURNAL, $this->contextId) . '/';

        $ojtPlugin                                  = new \stdClass;
        $ojtPlugin->api                             = $plugin->apiUrl() . '/product/';
        $ojtPlugin->baseUrl                         = $this->baseUrl;
        $ojtPlugin->journalPublicFolder             = $publicFolder;
        $ojtPlugin->pluginFullUrl                   = $pluginFullUrl;
        $ojtPlugin->version                         = $this->ojtPlugin->getPluginVersion();
        $ojtPlugin->logo                            = $this->getPluginFullUrl('assets/img/ojt-logo.png');
        $ojtPlugin->favIcon                         = $this->getPluginFullUrl('assets/img/ojt.ico');
        $ojtPlugin->placeholderImg                  = $this->getPluginFullUrl('assets/img/placeholder.png');
        $ojtPlugin->tailwindCss                     = $this->getPluginFullUrl('assets/stylesheets/tailwind.css');
        $ojtPlugin->fontAwesomeCss                  = $this->getPluginFullUrl('assets/vendors/font-awesome-5/css/all.min.css');
        $ojtPlugin->sweetAlertCss                   = $this->getPluginFullUrl('assets/vendors/sweetalert/sweetalert2.min.css');
        $ojtPlugin->pageName                        = 'ojt';

        $ojtPlugin->javascript  = [
            $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jquery/jquery.min.js',
            $this->getPluginFullUrl('assets/vendors/sweetalert/sweetalert2.all.min.js'),
            $this->getPluginFullUrl('assets/js/jquery.form.min.js'),
            // $this->getPluginFullUrl('assets/js/alpine/spruce.umd.js'),
            $this->getPluginFullUrl('assets/js/jquery.validate.min.js'),
            // $this->getPluginFullUrl('assets/js/alpine/component.min.js'),
            $this->getPluginFullUrl('assets/js/mainAlpine.js'),
            $this->getPluginFullUrl('assets/js/app.js'),
            $this->getPluginFullUrl('assets/js/store.js'),
            $this->getPluginFullUrl('assets/js/main.js'),
            $this->getPluginFullUrl('assets/js/updater.js'),
            $this->getPluginFullUrl('assets/js/alpine/alpine.min.js'),
            $this->getPluginFullUrl('assets/js/htmx.min.js'),
        ];

        HookRegistry::call('OjtPageHandler::index', array(&$ojtPlugin));

        $templateMgr->assign('ojtPlugin', $ojtPlugin);
        $templateMgr->assign('journal', $this->contextId ? $request->getContext() : $request->getSite());
        $templateMgr->assign('pluginGalleryHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugingallery.tpl')));
        $templateMgr->assign('pluginInstalledHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugininstalled.tpl')));
        $templateMgr->assign('pluginExclusiveHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('pluginexclusive.tpl')));

        return $templateMgr->display($this->ojtPlugin->getTemplateResource('index.tpl'));
    }

    public function support($args, $request)
    {
        $user           = $request->getUser();
        $journalName    = $request->getContext()->getLocalizedName();
        $params         = [
            $user->getEmail(),
            time(),
            $user->getLocalizedGivenName(),
            $user->getLocalizedFamilyName(),
            $journalName
        ];

        $url            = 'https://ticketing.openjournaltheme.com/login/' . base64_encode(implode('+', $params));

        header('Location: ' . $url, true, 302);

        return;
    }

    protected function getPluginFullUrl($path = '', $withVersion = true)
    {
        return $this->ojtPlugin->getPluginFullUrl($path, $withVersion);
    }

    public function settings($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);
        $templateMgr->assign('settings', [
            'enable_diagnostic' => $this->ojtPlugin->isDiagnosticEnabled(),
            'show_support_link_ojs' => $this->ojtPlugin->getSetting($this->contextId, 'show_support_link_ojs') ?? true,
        ]);


        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('settings.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function saveSettings($args, $request)
    {
        $this->ojtPlugin->updateSetting(CONTEXT_SITE, 'enable_diagnostic', filter_var($request->getUserVar('enable_diagnostic'), FILTER_VALIDATE_BOOLEAN));
        $this->ojtPlugin->updateSetting($this->contextId, 'show_support_link_ojs', filter_var($request->getUserVar('show_support_link_ojs'), FILTER_VALIDATE_BOOLEAN));

        $json['error'] = 0;
        $json['msg']   = 'Save Success';
        return showJson($json);
    }

    public function downloadLog($args, $request)
    {
        $file = $this->ojtPlugin->getErrorLogFile();

        $fileManager = new FileManager();

        return $fileManager->downloadByPath($file);
    }

    public function reportBug($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);
        $templateMgr->assign('plugins', $this->ojtPlugin->getRegisteredModules());

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('reportBug.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function submitBug($args, $request)
    {
        try {
            $url = 'https://sp.openjournaltheme.com/api/v1/report';

            $params = $request->getUserVars();
            $files = $this->reArrayFiles($_FILES['pictures']);
            $logFile = OjtPlugin::getErrorLogFile();
            $multipart = [];
            foreach ($params as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }

            foreach ($files ?? [] as $key => $file) {
                $multipart[] =
                    [
                        'name' => 'pictures[]',
                        'filename' => $file['name'],
                        // 'contents' => $file['tmp_name'],
                        'contents' => file_get_contents($file['tmp_name']),
                        'headers' => [
                            'Content-Type' => mime_content_type($file['tmp_name'])
                        ]
                    ];
            }

            $multipart[] = [
                'name' => 'log',
                'filename' => 'error.log',
                'contents' => file_get_contents($logFile),
                'headers' => [
                    'Content-Type' => mime_content_type($logFile)
                ]
            ];

            $multipart[] = [
                'name' => 'ip',
                'contents' => $request->getRemoteAddr(),
            ];

            $multipart[] = [
                'name' => 'journal_url',
                'contents' => $this->baseUrl
            ];

            $client = $this->ojtPlugin->getHttpClient([
                'Accept'     => 'application/json',
            ]);

            $response = $client->post($url, [
                'multipart' => $multipart
            ]);


            $result = json_decode((string) $response->getBody(), true);

            return showJson([
                'error' => 0,
                'msg' => $result['message']
            ]);
        } catch (BadResponseException $e) {
            $result = json_decode((string) $e->getResponse()->getBody(), true);
            return showJson([
                'error' => 1,
                'msg' => $result['message']
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkUpdate($args, $request)
    {
        $url = 'https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v1/ojtplugin/check_update';
        try {
            $response = $this->ojtPlugin->getHttpClient()->get($url);
            $json = json_decode((string) $response->getBody(), true);
            $json['updateAvailable'] = version_compare($this->ojtPlugin->getPluginVersion(), $json['latest_version'], '<');

            return showJson($json);
        } catch (\Throwable $th) {
            return showJson([
                'error' => 1,
                'msg' => $th->getMessage()
            ]);
        }
    }

    protected function reArrayFiles(&$file_post)
    {
        if (!$file_post) return $file_post;

        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }

    public function getPluginGalleryList($args, $request)
    {
        $url = $this->ojtPlugin->apiUrl() . '/product/list/ojs';

        $params = [
            'query' => [
                'ojt_plugin_version' => $this->ojtPlugin->getPluginVersion(),
                'ojs_version' => $this->ojtPlugin->getJournalVersion()
            ]
        ];

        try {
            $response = $this->ojtPlugin->getHttpClient()->get($url, $params);

            $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
            $ojtplugin = $this->ojtPlugin;
            $pluginGenericPath = 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR;
            $plugins = array_map(function ($plugin) use ($ojtplugin, $pluginSettingsDao, $pluginGenericPath) {
                $pluginFolder = $plugin['folder'];
                $pluginVersion = $plugin['version'];
                
                $targetPlugin = @include($ojtplugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "index.php"));
                $isSiteWide = false;
                if (!$targetPlugin) {
                    $targetPlugin = @include($pluginGenericPath . $pluginFolder . DIRECTORY_SEPARATOR . "index.php");
                    $isSiteWide = true;
                }

                $plugin['update'] = false;
                $plugin['license'] = $pluginSettingsDao->getSetting($this->ojtPlugin->getCurrentContextId(), $plugin['class'], 'license') ?? null;

                if ($targetPlugin) {
                    import('lib.pkp.classes.site.VersionCheck');
                    if ($isSiteWide) {
                        $version = VersionCheck::parseVersionXML($pluginGenericPath . $pluginFolder . DIRECTORY_SEPARATOR . "version.xml");
                    } else {
                        $version = VersionCheck::parseVersionXML($ojtplugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "version.xml"));
                    }
                    $plugin['update'] = version_compare($version['release'], $pluginVersion, '<');
                }

                $plugin['installed'] = ($targetPlugin) ? true : false;

                return $plugin;
            }, json_decode((string) $response->getBody(), true));
            if (!$plugins) throw new Exception("Couldn't connect to Server, please try again.");

            return showJson($plugins);
        } catch (\Throwable $th) {
            // return showJson([
            //     'error' => 1,
            //     'msg' => $th->getMessage()
            // ]);
        }
    }

    public function getExclusivePlugins($args, $request)
    {


        // Reset array keys if needed
        return showJson([]);
    }

    public function save($args, $request)
    {
        ajaxOrError();

        $plugin = $this->ojtPlugin;

        foreach ($_POST as $settingName => $settingValue) {
            $plugin->updateSetting($this->contextId, $settingName, $settingValue);
        }

        $json['error']  = 0;
        $json['msg']    = 'Sukses';
        return showJson($json);
    }

    protected function installedPlugins()
    {
        $plugins = $this->ojtPlugin->registeredModule;

        // Call the hook to allow other plugins to register to ojt control panel modules
        HookRegistry::call('OjtPageHandler::installed::plugins', array($this, &$plugins));

        return $plugins;
    }

    public function getInstalledPlugin($args, $request)
    {
        $plugins = $this->installedPlugins();

        return showJson($plugins ?? []);
    }

    public function toggleInstalledPlugin($args, $request)
    {
        $plugin = $this->ojtPlugin;

        if (!$plugin->getCanDisable()) {
            $json['error'] = 1;
            $json['msg'] = 'User does not have permission to disable/enable plugin';
            showJson($json);
            return;
        }

        $pluginType      = explode('.', $request->getUserVar('productType'))[1];
        $pluginClassName = $request->getUserVar('className');
        $isEnabled       = ($request->getUserVar('enabled') == 'true') ? true : false;

        $targetPlugin = PluginRegistry::getPlugin($pluginType, $pluginClassName);

        if (!$targetPlugin && !is_object($targetPlugin)) {
            $json['error'] = 1;
            $json['msg']   = 'Plugin is Invalid';
            showJson($json);
            return;
        }

        if (!$targetPlugin->getCanEnable()) {
            $json['error'] = 1;
            $json['msg']   = 'Plugin cannot be enabled/disabled';
            showJson($json);
            return;
        }

        $targetPlugin->setEnabled($isEnabled);

        $enabledMessage = ($isEnabled) ? ' has been enabled.' : ' has been disabled.';


        $json['error']  = 0;
        $json['msg']    = 'The plugin ' . $targetPlugin->getDisplayName() . $enabledMessage;
        $json['enabled'] = $isEnabled;
        return showJson($json);
    }

    public function installPlugin($args, $request)
    {
        try {
            $ojtPlugin = $this->ojtPlugin;
            $fileManager = new FileManager();
            $pluginToInstall = json_decode($request->getUserVar('plugin'));

            $pluginGenericPath = 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR;

            $indexFile = $ojtPlugin->getModulesPath(DIRECTORY_SEPARATOR . $pluginToInstall->folder . DIRECTORY_SEPARATOR . "index.php");
            if (!$fileManager->fileExists($indexFile)) {
                $indexFile = $pluginGenericPath . $pluginToInstall->folder . DIRECTORY_SEPARATOR . "index.php";
            }

            $license = $request->getUserVar('license') ?? false;
            $update = $request->getUserVar('update');
            if ($update && $fileManager->fileExists($indexFile)) {
                $pluginInstance = include($indexFile);


                // licenseMain is old version validation
                // the updated one is license
                $license = $pluginInstance->getSetting($this->contextId, 'license');

                if(!$license) {
                    $license = $pluginInstance->getSetting($this->contextId, 'licenseMain');
                }
            }

            $downloadLink = $ojtPlugin->getPluginDownloadLink($pluginToInstall->token, $license, $this->baseUrl);
            if (!$downloadLink) throw new Exception("There's a problem on the server, please try again later.");

            // Track installed plugins (main + dependencies) for site-wide validation
            $installedPluginFolders = [];

            // trying to install dependencies
            foreach ($downloadLink['dependencies'] as $dependency) {
                // Check if dependency exists in modules directory
                $indexDependency = $ojtPlugin->getModulesPath(DIRECTORY_SEPARATOR . $dependency['folder'] . DIRECTORY_SEPARATOR . "index.php");
                
                // Check if dependency exists in global plugins directory (for site-wide plugins)
                $globalIndexDependency = $pluginGenericPath . $dependency['folder'] . DIRECTORY_SEPARATOR . 'index.php';
                $absoluteGlobalIndexDependency = getcwd() . DIRECTORY_SEPARATOR . $globalIndexDependency;
                
                // Check if dependency already exists in either location
                $dependencyExistsInModules = $fileManager->fileExists($indexDependency);
                $dependencyExistsGlobally = file_exists($absoluteGlobalIndexDependency);
                
                if ($dependencyExistsInModules || $dependencyExistsGlobally) {
                    $location = $dependencyExistsGlobally ? $pluginGenericPath : 'modules/';
                    error_log("Dependency '{$dependency['folder']}' already exists in {$location}. Skipping reinstallation.");
                    continue;
                }

                if (!$fileManager->fileExists($indexDependency)) {
                    $ojtPlugin->installPlugin($dependency['link']);
                }

                if (!$fileManager->fileExists($indexDependency)) throw new Exception("Index file dependency not found.");
                
                // Track dependency folder for site-wide validation
                $installedPluginFolders[] = $dependency['folder'];
            }

            // trying to install plugin
            $ojtPlugin->installPlugin($downloadLink['product']);

            $this->simulateRegisterModules($pluginToInstall);

            if (!$fileManager->fileExists($indexFile)) throw new Exception("Index file not found.");

            $pluginInstance         = $pluginInstance ?? include($indexFile);
            // Applying input license to plugin setting
            if ($pluginInstance instanceof Plugin && $license && !$update) {
                $pluginInstance->updateSetting($this->contextId, 'licenseMain', $license);
            }

            // Track main plugin folder for site-wide validation
            $installedPluginFolders[] = $pluginToInstall->folder;

            // Perform site-wide validation for all installed plugins (main + dependencies)
            $this->relocateSiteWidePlugins($installedPluginFolders, $ojtPlugin);

            $json['error']  = 0;
            $json['msg']    =  !$update ? 'Plugin Installed' : 'Plugin Updated';
            return showJson($json);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }
    }

    protected function relocateSiteWidePlugins($pluginFolders, $ojtPlugin)
    {
        $movedPlugins = [];
        
        foreach ($pluginFolders as $pluginFolder) {
            try {
                $indexFile = $ojtPlugin->getModulesPath(DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR . "index.php");
                
                // Check if plugin still exists in modules directory
                if (!file_exists($indexFile)) {
                    error_log("Plugin index file not found for site-wide validation: {$pluginFolder}");
                    continue;
                }
                
                // Load plugin instance
                $pluginInstance = @include($indexFile);
                
                // Check if plugin is site-wide
                if ($pluginInstance instanceof Plugin && method_exists($pluginInstance, 'isSitePlugin') && $pluginInstance->isSitePlugin()) {
                    $this->moveSitePluginToGlobalDirectory($pluginFolder, $ojtPlugin);
                    $movedPlugins[] = $pluginFolder;
                }
                
            } catch (Exception $e) {
                error_log("Error validating site-wide plugin '{$pluginFolder}': " . $e->getMessage());
                // Continue with other plugins even if one fails
            }
        }
        
        // Log summary if any plugins were moved
        if (!empty($movedPlugins)) {
            error_log("Relocated " . count($movedPlugins) . " site-wide plugin(s) to global directory: " . implode(', ', $movedPlugins));
        }
    }

    /**
     * Move site-wide plugin from modules directory to global plugins directory
     * 
     * @param string $pluginFolder Plugin folder name
     * @param OjtPlugin $ojtPlugin Instance of OjtPlugin
     * @throws Exception if move operation fails
     */
    protected function moveSitePluginToGlobalDirectory($pluginFolder, $ojtPlugin)
    {
        try {
            $sourcePath = $ojtPlugin->getModulesPath($pluginFolder);

            $pluginGenericPath = 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR;
            
            $destinationPath = $pluginGenericPath . $pluginFolder;
            
            // Get absolute paths
            $absoluteSourcePath = getcwd() . DIRECTORY_SEPARATOR . $sourcePath;
            $absoluteDestinationPath = getcwd() . DIRECTORY_SEPARATOR . $destinationPath;
            
            if (!is_dir($absoluteSourcePath)) {
                throw new Exception("Source plugin directory not found: {$absoluteSourcePath}");
            }
            
            // Check if destination already exists
            if (is_dir($absoluteDestinationPath)) {
                $ojtPlugin->recursiveDelete($absoluteDestinationPath);
            }
            
            // Move the plugin directory
            if (!rename($absoluteSourcePath, $absoluteDestinationPath)) {
                throw new Exception("Failed to move plugin to global directory");
            }
            
            error_log("Site-wide plugin '{$pluginFolder}' moved to global directory: {$destinationPath}");
            
        } catch (Exception $e) {
            error_log("Error moving site plugin to global directory: " . $e->getMessage());
            throw new Exception("Failed to move site plugin to global directory: " . $e->getMessage());
        }
    }

    /**
     * Lakukan pengecekan sewaktu menginstall plugin baru
     * delete jika ada error
     */
    protected function simulateRegisterModules($pluginToInstall)
    {
        $fileManager = new FileManager();
        $ojtPlugin = $this->ojtPlugin;
        $indexFile = $ojtPlugin->getModulesPath(DIRECTORY_SEPARATOR . $pluginToInstall->folder . DIRECTORY_SEPARATOR . "index.php");

        // delete plugin when error occured
        register_shutdown_function(function () use ($ojtPlugin, $pluginToInstall) {
            $error = error_get_last();
            if (!in_array($error['type'], [E_COMPILE_ERROR, E_ERROR])) return;

            // Working directory berubah ketika callback ini berjalan, jadi harus mendapatkan fullpath
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $pluginToInstall->folder;
            try {
                if (!is_dir($path)) {
                    throw new \Exception("$path is not directory");
                    return;
                }
                try {
                    $ojtPlugin->recursiveDelete($path);
                } catch (\Throwable $deleteError) {
                    // Log the error
                    error_log("Error in recursiveDelete: " . $deleteError->getMessage());

                    // Use the plugin's method to send Discord notification
                    $ojtPlugin->sendDiscordNotificationForDeleteError($pluginToInstall->folder, $deleteError);
                }
            } catch (\Throwable $th) {
                error_log("Error in simulateRegisterModules: " . $th->getMessage());
            }
        });

        if (!$fileManager->fileExists($indexFile)) throw new Exception("Index file not found.");

        $plugin         = include($indexFile);
    }

    public function resetSetting($args, $showJson = true)
    {
        $pluginName = is_array($args) ? $args[0] : $args;

        $pluginSettingsDao = \DAORegistry::getDAO('PluginSettingsDAO');
        $pluginName = strtolower_codesafe($pluginName);

        $cache = $pluginSettingsDao->_getCache($this->contextId, $pluginName);
        $cache->flush();

        $pluginSettingsDao->update(
            'DELETE FROM plugin_settings WHERE context_id = ? AND plugin_name = ?
                AND setting_name NOT IN (\'license\', \'licenseMain\', \'status_validated\', \'html\', \'time\')',
            array((int) $this->contextId, $pluginName)
        );

        if ($showJson) {
            $json['error'] = 0;
            $json['msg'] = 'Reset Setting Success.';
            showJson($json);
            return;
        }
    }

    public function uninstallPlugin($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $removePlugin = json_decode($request->getUserVar('plugin'));

        if ($request->getUserVar('resetSetting')) {
            $this->resetSetting($removePlugin->class, false);
        }

        // trying to remove plugin
        try {
            $plugin->uninstallPlugin($removePlugin);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            showJson($json);
            return;
        }

        $json['error']  = 0;
        $json['msg']    = 'Plugin Uninstalled';
        showJson($json);
        return;
    }

    public function checkPluginInstalled($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $pluginFolder = $_POST['pluginFolder'];
        $pluginVersion = $_POST['pluginVersion'];

        $targetPlugin = @include($plugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "index.php"));

        $json['update'] = false;

        if ($targetPlugin) {
            import('lib.pkp.classes.site.VersionCheck');
            $version = VersionCheck::parseVersionXML($plugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "version.xml"));
            $json['update'] = version_compare($version['release'], $pluginVersion, '<');
        }

        $json['error'] = 0;
        $json['installed'] = ($targetPlugin) ? true : false;
        showJson($json);
    }

    // TODO: this function purposes to delete certain plugins inside the modules
    public function deteleModules() {}
}
