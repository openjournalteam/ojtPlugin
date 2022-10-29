<?php

import('classes.handler.Handler');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.plugins.Plugin');

use Illuminate\Http\Client\PendingRequest as Http;

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
            $this->getPluginFullUrl('assets/js/theme.js'),
            $this->getPluginFullUrl('assets/js/jquery.form.min.js'),
            $this->getPluginFullUrl('assets/js/alpine/spruce.umd.js'),
            $this->getPluginFullUrl('assets/js/jquery.validate.min.js'),
            $this->getPluginFullUrl('assets/js/alpine/alpine.min.js'),
            // $this->getPluginFullUrl('assets/js/alpine/component.min.js'),
            $this->getPluginFullUrl('assets/js/mainAlpine.js'),
            $this->getPluginFullUrl('assets/js/store.js'),
            $this->getPluginFullUrl('assets/js/main.js'),
            $this->getPluginFullUrl('assets/js/updater.js')
        ];

        $templateMgr->assign('ojtPlugin', $ojtPlugin);
        $templateMgr->assign('pluginGalleryHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugingallery.tpl')));
        $templateMgr->assign('pluginInstalledHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugininstalled.tpl')));

        return $templateMgr->display($this->ojtPlugin->getTemplateResource('index.tpl'));
    }

    protected function getPluginFullUrl($path = '', $withVersion = true)
    {
        $plugin = $this->ojtPlugin;

        $fullUrl =  $plugin->getRequest()->getBaseUrl() . '/'  . $plugin->getPluginPath() . '/' . $path;
        if ($withVersion) {
            return $fullUrl . '?v=' . $plugin->getPluginVersion();
        }

        return $fullUrl;
    }

    public function setting($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('setting.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function pluginGallery($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugingallery.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function getPluginGalleryList($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $url = $plugin->apiUrl() . '/product/list';

        $request = app(Http::class)
            ->get($url);

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
        }, $request->json());

        if ($plugins) return showJson($plugins);

        return showJson([
            'error' => 1,
            'msg' => "Couldn't connect to Server, please try again."
        ]);
    }

    public function pluginInstalled($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugininstalled.tpl'));
        $json['js']   = [];
        return showJson($json);
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
        $plugin = $this->ojtPlugin;

        $pluginToInstall = json_decode($_POST['plugin']);
        $license = $_POST['license'] ?? false;

        if (isset($_POST['update']) && $targetPlugin = @include($plugin->getModulesPath($pluginToInstall->folder . DIRECTORY_SEPARATOR . 'index.php'))) {
            $license = $targetPlugin->getSetting($this->contextId, 'license');
        }

        $payload = [
            'token' => $pluginToInstall->token,
            'license' => $license,
            'journal_url' => $this->baseUrl,
        ];

        $downloadLink = $plugin->getPluginDownloadLink($pluginToInstall->token, $license, $this->baseUrl);

        if (!$downloadLink) {
            $json['error']  = 1;
            $json['msg']    = "There's a problem on the server, please try again later.";
            return showJson($json);
        }
        // trying to install plugin
        try {
            $plugin->installPlugin($downloadLink);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }

        // Applying input license to plugin setting  
        import('plugins.generic.ojtPlugin.modules.' . $pluginToInstall->folder . '.' . $pluginToInstall->class);

        $pluginInstance = new $pluginToInstall->class();

        if ($pluginInstance instanceof Plugin && isset($license)) {
            $pluginInstance->updateSetting($this->contextId, 'licenseMain', $license);
        }


        $json['error']  = 0;
        $json['msg']    = 'Plugin Installed';
        return showJson($json);
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
            $json['msg']    = $e;
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


    /**
     * send the curl
     */
    public function curl($payload, $url, $isReturnJson)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: x-csrf-uap-admin-token');
        // user agents
        $agents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.9) Gecko/20100508 SeaMonkey/2.0.4',
            'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agents[array_rand($agents)]);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($isReturnJson) {
            return json_decode($output);
        }

        return $output;
    }
}
