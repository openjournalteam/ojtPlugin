<?php

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
        if (!$request->getUser()) {
            $request->redirect(null, 'login', null, null);
        }

        $this->ojtPlugin = PluginRegistry::getPlugin('generic', 'ojtPlugin');

        $this->contextId = $request->getContext()->getId();
        $this->baseUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext());
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
        ];

        $templateMgr->assign('ojtPlugin', $ojtPlugin);
        $templateMgr->assign('pluginGalleryHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugingallery.tpl')));
        $templateMgr->assign('pluginInstalledHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugininstalled.tpl')));

        return $templateMgr->display($this->ojtPlugin->getTemplateResource('index.tpl'));
    }

    protected function getPluginFullUrl($path = '', $withVersion = true)
    {
        return $this->ojtPlugin->getPluginFullUrl($path, $withVersion);
    }

    public function setting($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('setting.tpl'));
        $json['js']   = [];
        return showJson($json);
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
        // dd($_FILES);
        dd($request->getUserVars());
    }

    public function getPluginGalleryList($args, $request)
    {
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $version    = $versionDao->getCurrentVersion();

        $url = $this->ojtPlugin->apiUrl() . '/product/list/ojs';

        $params = [
            'ojt_plugin_version' => $this->ojtPlugin->getPluginVersion(),
            'ojs_version' => $version->getVersionString()
        ];

        try {
            $response = $this->ojtPlugin->getHttpClient()->get($url, $params);

            $plugins = array_map(function ($plugin) {
                $ojtplugin = $this->ojtPlugin;

                $pluginFolder = $plugin['folder'];
                $pluginVersion = $plugin['version'];
                $targetPlugin = @include($ojtplugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "index.php"));

                $plugin['update'] = false;

                if ($targetPlugin) {
                    import('lib.pkp.classes.site.VersionCheck');
                    $version = VersionCheck::parseVersionXML($ojtplugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "version.xml"));
                    $plugin['update'] = version_compare($version['release'], $pluginVersion, '<');
                }

                $plugin['installed'] = ($targetPlugin) ? true : false;

                return $plugin;
            }, json_decode((string) $response->getBody(), true));

            if (!$plugins) throw new Exception("Couldn't connect to Server, please try again.");

            return showJson($plugins);
        } catch (\Throwable $th) {
            return showJson([
                'error' => 1,
                'msg' => $th->getMessage()
            ]);
        }
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

    public function getInstalledPlugin($args, $request)
    {
        return showJson($this->ojtPlugin->registeredModule ?? []);
    }

    public function toggleInstalledPlugin($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $pluginFolder = $request->getUserVar('pluginFolder');
        $isEnabled    = ($request->getUserVar('enabled') == 'true') ? true : false;

        $targetPlugin         = include($plugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "index.php"));

        if (!$targetPlugin && !is_object($targetPlugin)) {
            $json['error'] = 1;
            $json['msg']   = 'Plugin is Invalid';
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
            $license = $request->getUserVar('license') ?? false;
            $update = $request->getUserVar('update');

            $downloadLink = $ojtPlugin->getPluginDownloadLink($pluginToInstall->token, $license, $this->baseUrl);
            if (!$downloadLink) throw new Exception("There's a problem on the server, please try again later.");

            // trying to install plugin
            $ojtPlugin->installPlugin($downloadLink);


            $indexFile = $ojtPlugin->getModulesPath(DIRECTORY_SEPARATOR . $pluginToInstall->folder . DIRECTORY_SEPARATOR . "index.php");
            if (!$fileManager->fileExists($indexFile)) throw new Exception("Index file not found.");

            $pluginInstance         = include($indexFile);
            // Applying input license to plugin setting  
            if ($pluginInstance instanceof Plugin && $license && !$update) {
                $pluginInstance->updateSetting($this->contextId, 'licenseMain', $license);
            }


            $json['error']  = 0;
            $json['msg']    = 'Plugin Installed';
            return showJson($json);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }
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
}
