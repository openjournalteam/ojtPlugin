<?php

use GuzzleHttp\Exception\BadResponseException;
use Openjournalteam\OjtPlugin\Classes\ApiServicePanel;

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
            ROLE_ID_SITE_ADMIN,
            ['index', 'getInstalledPlugin', 'updatePanel', 'settings', 'saveSettings', 'getJobs', 'jobAction', 'getSchedules', 'getScheduleHistory', 'scheduleAction', 'downloadLog', 'reportBug', 'submitBug', 'checkUpdate', 'getPluginGalleryList', 'getExclusivePlugins', 'save', 'installPlugin', 'uninstallPlugin', 'checkPluginInstalled', 'toggleInstalledPlugin', 'resetSetting', 'support'],
        );

        $this->addRoleAssignment(
            ROLE_ID_MANAGER,
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
        $templateMgr->assign('canDeletePlugins', $plugin->isCurrentUserSiteAdmin());
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
            'background_jobs_enabled' => $this->ojtPlugin->areBackgroundJobsEnabled(),
            'can_manage_background_jobs' => $this->canManageBackgroundJobs($request),
            'csrfToken' => $request->getSession()->getCSRFToken(),
        ]);


        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('settings.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    /**
     * Background Jobs is an administrator-level feature.
     */
    protected function canManageBackgroundJobs($request)
    {
        return $this->isSiteAdmin($request);
    }

    protected function isSiteAdmin($request)
    {
        $user = $request->getUser();
        return $user && $user->hasRole([ROLE_ID_SITE_ADMIN], CONTEXT_SITE);
    }

    /**
     * Return the current journal's Background Jobs data.
     */
    public function getJobs($args, $request)
    {
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to access Background Jobs.']);
        }

        try {
            $service = $this->ojtPlugin->jobQueueService();
            $result = $service->listJobs([
                'page' => $request->getUserVar('page') ?: 1,
                'pageSize' => $request->getUserVar('pageSize') ?: 10,
                'filter' => $request->getUserVar('filter') ?: 'all',
                'query' => $request->getUserVar('query') ?: '',
                'dateRange' => $request->getUserVar('dateRange') ?: 'all',
                'queue' => $request->getUserVar('queue') ?: 'default',
                'contextId' => $this->contextId,
            ]);

            return showJson([
                'error' => 0,
                'data' => $result,
                'backgroundJobsEnabled' => $this->ojtPlugin->areBackgroundJobsEnabled(),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            return showJson([
                'error' => 1,
                'msg' => 'Unable to load background jobs.',
            ]);
        }
    }

    public function getSchedules($args, $request)
    {
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to access schedules.']);
        }

        try {
            return showJson([
                'error' => 0,
                'data' => $this->ojtPlugin->scheduleService()->listSchedules($this->contextId),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            return showJson(['error' => 1, 'msg' => 'Unable to load schedules.']);
        }
    }

    public function getScheduleHistory($args, $request)
    {
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to access schedule history.']);
        }

        try {
            $result = $this->ojtPlugin->scheduleService()->history(
                (int) $request->getUserVar('scheduleId'),
                $this->contextId,
                (int) ($request->getUserVar('page') ?: 1),
                (int) ($request->getUserVar('pageSize') ?: 20)
            );
            if (empty($result['success'])) {
                http_response_code(404);
                return showJson(['error' => 1, 'msg' => $result['message'] ?? 'Schedule not found.']);
            }
            return showJson(['error' => 0, 'data' => $result]);
        } catch (Throwable $e) {
            http_response_code(500);
            return showJson(['error' => 1, 'msg' => 'Unable to load schedule history.']);
        }
    }

    public function scheduleAction($args, $request)
    {
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to manage schedules.']);
        }

        if (!$request->isPost()) {
            http_response_code(405);
            return showJson(['error' => 1, 'msg' => 'This endpoint only accepts POST requests.']);
        }

        $csrfToken = (string) $request->getUserVar('csrfToken');
        $sessionToken = (string) $request->getSession()->getCSRFToken();
        if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'Invalid security token.']);
        }

        $action = (string) $request->getUserVar('action');
        $scheduleId = (int) $request->getUserVar('scheduleId');
        $service = $this->ojtPlugin->scheduleService();

        try {
            switch ($action) {
                case 'toggle':
                    $enabled = filter_var($request->getUserVar('enabled'), FILTER_VALIDATE_BOOLEAN);
                    $result = $service->toggle($scheduleId, $enabled, $this->contextId, $this->isSiteAdmin($request));
                    break;
                case 'update':
                    $result = $service->update(
                        $scheduleId,
                        $request->getUserVar('cron'),
                        $request->getUserVar('timezone'),
                        $this->contextId,
                        $this->isSiteAdmin($request)
                    );
                    break;
                case 'run_now':
                    $result = $service->runNow($scheduleId, $this->contextId, $this->isSiteAdmin($request));
                    break;
                default:
                    $result = ['success' => false, 'message' => 'Unknown schedule action.'];
                    break;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            return showJson(['error' => 1, 'msg' => 'Unable to apply the schedule action.']);
        }

        if (empty($result['success'])) {
            http_response_code(422);
            return showJson(['error' => 1, 'msg' => $result['message'] ?? 'Unable to apply the schedule action.']);
        }

        return showJson(['error' => 0, 'msg' => $result['message'] ?? 'Schedule action completed.']);
    }

    /**
     * Apply a confirmed queue action.
     */
    public function jobAction($args, $request)
    {
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to manage Background Jobs.']);
        }

        if (!$request->isPost()) {
            http_response_code(405);
            return showJson(['error' => 1, 'msg' => 'This endpoint only accepts POST requests.']);
        }

        $csrfToken = (string) $request->getUserVar('csrfToken');
        $sessionToken = (string) $request->getSession()->getCSRFToken();
        if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'Invalid security token.']);
        }

        $action = (string) $request->getUserVar('action');
        $jobId = (int) $request->getUserVar('jobId');
        $service = $this->ojtPlugin->jobQueueService();

        try {
            switch ($action) {
                case 'toggle_enabled':
                    $enabled = filter_var($request->getUserVar('enabled'), FILTER_VALIDATE_BOOLEAN);
                    $this->ojtPlugin->setBackgroundJobsEnabled($enabled);
                    $result = [
                        'success' => true,
                        'message' => $enabled ? 'Background jobs enabled.' : 'Background jobs disabled.',
                    ];
                    break;
                case 'pause':
                    $result = $service->pause($jobId, $this->contextId);
                    break;
                case 'resume':
                    $result = $service->resume($jobId, $this->contextId);
                    break;
                case 'stop':
                    $result = $service->stop($jobId, $this->contextId);
                    break;
                case 'retry':
                    $result = $service->retry($jobId, $this->contextId);
                    break;
                case 'stop_all':
                    $result = $service->stopAll($this->contextId, 'default');
                    break;
                default:
                    $result = ['success' => false, 'message' => 'Unknown job action.'];
                    break;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            return showJson(['error' => 1, 'msg' => 'Unable to apply the job action.']);
        }

        if (empty($result['success'])) {
            http_response_code(422);
            return showJson(['error' => 1, 'msg' => $result['message'] ?? 'Unable to apply the job action.']);
        }

        return showJson([
            'error' => 0,
            'msg' => $result['message'] ?? 'Job action completed.',
        ]);
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
        if (!$this->canManageBackgroundJobs($request)) {
            http_response_code(403);
            return showJson(['error' => 1, 'msg' => 'You are not authorized to download the OJT log.']);
        }

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
            $url = ApiServicePanel::make($this->ojtPlugin)->getApiUrl('report');

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
        $url = 'https://sp-staging.ojthost.xyz/api/v1/product/control-panel';
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

            $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /** @var \PluginSettingsDAO $pluginSettingsDao */
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
                $licenseContextId = $isSiteWide ? CONTEXT_SITE : $ojtplugin->getCurrentContextId();
                $plugin['license'] = $pluginSettingsDao->getSetting($licenseContextId, $plugin['class'], 'license') ?? null;
                if (!$plugin['license']) {
                    $plugin['license'] = $pluginSettingsDao->getSetting($licenseContextId, $plugin['class'], 'licenseMain') ?? null;
                }

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

        // Ensure canDelete and isAuthorized are set
        foreach($plugins as &$plugin) {
            if(!isset($plugin['canDelete'])) {
                $plugin['canDelete'] = true;
            }
            if(!isset($plugin['isAuthorized'])) {
                $plugin['isAuthorized'] = true;
            }
        }

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

        $targetPlugin = PluginRegistry::getPlugin($pluginType, $pluginClassName); /** @var \LazyLoadPlugin $targetPlugin */

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

    /**
     * Detect if a plugin is site-wide by parsing its main file from staging directory
     * 
     * This method parses the PHP file content instead of including it to avoid
     * namespace/path mismatch issues when the plugin is still in staging.
     * 
     * @param string $stagingPath Full path to staging directory
     * @param string $pluginFolder Name of the plugin folder within staging
     * @return bool True if plugin has isSitePlugin() method returning true
     */
    protected function detectSiteWidePluginFromStaging($stagingPath, $pluginFolder)
    {
        try {
            // Get the plugin path within staging
            $pluginPath = $stagingPath . DIRECTORY_SEPARATOR . $pluginFolder;
            
            if (!is_dir($pluginPath)) {
                return false;
            }
            
            $files = scandir($pluginPath);
            $mainPluginFile = null;
            
            foreach ($files as $file) {
                // Match files ending with "Plugin.inc.php"
                if (preg_match('/Plugin\.inc\.php$/i', $file)) {
                    $mainPluginFile = $pluginPath . DIRECTORY_SEPARATOR . $file;
                    break;
                }
            }
            
            if (!$mainPluginFile || !file_exists($mainPluginFile)) {
                return false;
            }
            
            // Read and parse the file content
            $content = file_get_contents($mainPluginFile);
            
            // Check if the file contains isSitePlugin method
            if (!preg_match('/(?:public\s+)?function\s+isSitePlugin\s*\([^\)]*\)/s', $content)) {
                return false;
            }
            
            // Extract the isSitePlugin method body to check its return value
            if (preg_match('/(?:public\s+)?function\s+isSitePlugin\s*\([^\)]*\)\s*(?::\s*bool\s*)?\s*\{([^}]*)\}/s', $content, $matches)) {
                $methodBody = $matches[1];
                
                // Remove comments to avoid false positives
                $methodBody = preg_replace('/\/\/.*$/m', '', $methodBody);
                $methodBody = preg_replace('/\/\*.*?\*\//s', '', $methodBody);
                
                // Look for "return true" pattern (case-insensitive, flexible whitespace)
                if (preg_match('/return\s+true\s*;/i', $methodBody)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error detecting site-wide plugin from staging '{$pluginFolder}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Install a plugin from the marketplace
     * 
     * Workflow with staging validation:
     * 1. Download and extract dependencies to staging directory
     * 2. Detect if each dependency is site-wide by parsing PHP file (avoids namespace issues)
     * 3. Move dependencies to correct location (plugins/generic/ or modules/)
     * 4. Download and extract main plugin to staging
     * 5. Detect if main plugin is site-wide and move to correct location
     * 6. Instantiate plugin from correct location
     * 7. Apply license and trigger hooks
     * 8. Clean up old staging directories
     * 
     * @param array $args Handler arguments
     * @param object $request PKP Request object
     * @return string JSON response
     */
    public function installPlugin($args, $request)
    {
        $stagingInfo = null;
        
        try {
            $ojtPlugin = $this->ojtPlugin;
            $fileManager = new FileManager();
            $pluginToInstall = json_decode($request->getUserVar('plugin'));
            $license = $request->getUserVar('license') ?? false;
            $update = $request->getUserVar('update');
            $pluginFolder = $pluginToInstall->folder;
            
            // Clean up old staging directories (older than 24 hours)
            // $ojtPlugin->cleanupOldStagingDirectories(24);
            
            // Check if plugin already exists (for update scenarios)
            $pluginInstance = $ojtPlugin->instantiatePluginWithoutThrow($pluginFolder);
            $isExistingPluginSiteWide = false;
            if (!$pluginInstance) {
                $pluginInstance = $ojtPlugin->instantiatePluginFromGlobalDirectory($pluginFolder);
                $isExistingPluginSiteWide = (bool) $pluginInstance;
            }
            
            if ($update && $pluginInstance) {
                // Try newer 'license' setting first, then fall back to 'licenseMain' for backward compatibility
                $licenseContextId = $isExistingPluginSiteWide ? CONTEXT_SITE : $this->contextId;
                $license = $pluginInstance->getSetting($licenseContextId, 'license');

                if(!$license) {
                    $license = $pluginInstance->getSetting($licenseContextId, 'licenseMain');
                }

                // Keep compatibility with licenses saved by older releases in the journal context.
                if (!$license && $isExistingPluginSiteWide && $licenseContextId !== $this->contextId) {
                    $license = $pluginInstance->getSetting($this->contextId, 'license');
                    if (!$license) {
                        $license = $pluginInstance->getSetting($this->contextId, 'licenseMain');
                    }
                }
            }

            $downloadLink = $ojtPlugin->getPluginDownloadLink($pluginToInstall->token, $license, $this->baseUrl);
            if (!$downloadLink) throw new Exception("There's a problem on the server, please try again later.");

            // Install dependencies using staging workflow
            foreach ($downloadLink['dependencies'] as $dependency) {
                $dependencyFolder = $dependency['folder'];
                
                // Check if dependency exists in modules directory
                $indexDependency = $ojtPlugin->getModulesPath($dependencyFolder . DIRECTORY_SEPARATOR . "index.php");
                
                // Check if dependency exists in global plugins directory (for site-wide plugins)
                $globalIndexDependency = 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . $dependencyFolder . DIRECTORY_SEPARATOR . 'index.php';
                $absoluteGlobalIndexDependency = getcwd() . DIRECTORY_SEPARATOR . $globalIndexDependency;
                
                $dependencyExistsInModules = $fileManager->fileExists($indexDependency);
                $dependencyExistsGlobally = file_exists($absoluteGlobalIndexDependency);
                
                if ($dependencyExistsInModules || $dependencyExistsGlobally) {
                    $location = $dependencyExistsGlobally ? 'plugins/generic/' : 'modules/';
                    error_log("Dependency '{$dependencyFolder}' already exists in {$location}. Skipping reinstallation.");
                    continue;
                }

                // Install dependency to staging
                $depStagingInfo = $ojtPlugin->installPluginToStaging($dependency['link']);
                
                // Detect if dependency is site-wide by parsing PHP file (not including)
                $isSiteWideDependency = $this->detectSiteWidePluginFromStaging(
                    $depStagingInfo['stagingPath'], 
                    $depStagingInfo['pluginFolder']
                );
                
                // Move dependency to correct final location
                $ojtPlugin->moveStagedPluginToFinal(
                    $depStagingInfo['stagingPath'],
                    $depStagingInfo['pluginFolder'],
                    $isSiteWideDependency
                );
                
                error_log("Dependency '{$dependencyFolder}' installed as " . ($isSiteWideDependency ? 'site-wide' : 'regular') . " plugin");
            }

            // Install main plugin to staging
            $stagingInfo = $ojtPlugin->installPluginToStaging($downloadLink['product']);
            
            // Detect if main plugin is site-wide by parsing PHP file (not including)
            $isSiteWidePlugin = $this->detectSiteWidePluginFromStaging(
                $stagingInfo['stagingPath'],
                $stagingInfo['pluginFolder']
            );
            
            // Move main plugin to correct final location
            $ojtPlugin->moveStagedPluginToFinal(
                $stagingInfo['stagingPath'],
                $stagingInfo['pluginFolder'],
                $isSiteWidePlugin
            );
            
            // Re-instantiate plugin from the correct final location (namespace now matches path)
            if ($isSiteWidePlugin) {
                $pluginInstance = $ojtPlugin->instantiatePluginFromGlobalDirectory($pluginFolder);
            } else {
                // For non-site-wide plugins, run simulateRegisterModules for error handling
                $this->simulateRegisterModules($pluginToInstall);
                $pluginInstance = $ojtPlugin->instantiatePluginWithoutThrow($pluginFolder);
            }

            // Apply license setting (backward compatible with licenseMain)
            if ($pluginInstance instanceof Plugin && $license && !$update) {
                $licenseContextId = $isSiteWidePlugin ? CONTEXT_SITE : $this->contextId;
                $pluginInstance->updateSetting($licenseContextId, 'license', $license);
                $pluginInstance->updateSetting($licenseContextId, 'licenseMain', $license);
            }
            
            // Clean up staging base path if empty
            $stagingBasePath = $ojtPlugin->getStagingBasePath();
            if (is_dir($stagingBasePath) && count(array_diff(scandir($stagingBasePath), ['.', '..'])) === 0) {
                rmdir($stagingBasePath);
            }

            $json['error']  = 0;
            $json['msg']    = !$update ? 'Plugin Installed' : 'Plugin Updated';
            return showJson($json);
        } catch (Exception $e) {
            // Clean up staging directory on failure
            if (isset($stagingInfo) && isset($stagingInfo['stagingPath']) && is_dir($stagingInfo['stagingPath'])) {
                $ojtPlugin->recursiveDelete($stagingInfo['stagingPath']);
            }
            
            // Clean up staging base path if empty
            if (isset($ojtPlugin)) {
                $stagingBasePath = $ojtPlugin->getStagingBasePath();
                if (is_dir($stagingBasePath) && count(array_diff(scandir($stagingBasePath), ['.', '..'])) === 0) {
                    rmdir($stagingBasePath);
                }
            }
            
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
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

        if (!$removePlugin->isAuthorized) {
            $json['error'] = 1;
            $json['msg'] = 'User does not have permission to uninstall this plugin';
            showJson($json);
            return;
        }

        if (!$removePlugin->canDelete) {
            $json['error'] = 1;
            $json['msg'] = 'This plugin cannot be uninstalled';
            showJson($json);
            return;
        }

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
