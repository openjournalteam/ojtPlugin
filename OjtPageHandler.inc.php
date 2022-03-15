<?php

import('classes.handler.Handler');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.plugins.Plugin');

const API = 'https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v1/product/';
class OjtPageHandler extends Handler
{
    /** @var OjtPlugin  */
    static $_plugin;

    public $_contextId;
    public $_baseUrl;

    public function __construct($request)
    {
        if (!$request->getUser()) {
            $request->redirect(null, 'login', null, null);
        }
        $this->_contextId = $request->getContext()->getId();
        $this->_baseUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext());
    }


    static function setPlugin($plugin)
    {
        self::$_plugin = $plugin;
    }

    public function update($args, $request)
    {
        $plugin = self::$_plugin;

        $ojtPlugin = json_decode($_POST['ojtPlugin']);
        $payload = [
            'token'        => $ojtPlugin->token,
            'journal_url'   => $this->_baseUrl,
        ];



        $response = $this->curl($payload, API . 'get_download_link', true);
        if ($response == false) {
            $json['error']  = 1;
            $json['msg']    = "There's a problem on the server, please try again later.";
            return showJson($json);
        }

        if ($response->error == 1) {
            $json['error']  = 1;
            $json['msg']    = $response->msg;
            return showJson($json);
        }

        $data = $response->data;

        // trying to install plugin
        try {
            $plugin->installPlugin($data->download_link);
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
        $plugin = self::$_plugin;

        $baseUrl = $request->getBaseUrl() . '/';
        $pluginFullUrl          = $baseUrl . $plugin->getPluginPath();
        $templateMgr            = TemplateManager::getManager($request);

        $publicFileManager = new PublicFileManager();

        $publicFolder      = ($plugin->getJournalVersion() > '31')
            ? $baseUrl . $publicFileManager->getContextFilesPath($this->_contextId) . '/'
            : $baseUrl . $publicFileManager->getContextFilesPath(ASSOC_TYPE_JOURNAL, $this->_contextId) . '/';

        $ojtPlugin                                  = new \stdClass;
        $ojtPlugin->api                             = API;
        $ojtPlugin->baseUrl                         = $this->_baseUrl;
        $ojtPlugin->journalPublicFolder             = $publicFolder;
        $ojtPlugin->pluginFullUrl                   = $pluginFullUrl;
        $ojtPlugin->version                         = self::$_plugin->getPluginVersion();
        $ojtPlugin->logo                            = $pluginFullUrl . '/assets/img/ojt-logo.png';
        $ojtPlugin->favIcon                         = $pluginFullUrl . '/assets/img/ojt.ico';
        $ojtPlugin->placeholderImg                  = $pluginFullUrl . '/assets/img/placeholder.png';
        $ojtPlugin->tailwindCss                     = $pluginFullUrl . '/assets/stylesheets/tailwind.css';
        $ojtPlugin->themeCss                        = $pluginFullUrl . '/assets/stylesheets/theme.css';
        $ojtPlugin->fontAwesomeCss                  = $pluginFullUrl . '/assets/vendors/font-awesome-5/css/all.min.css';
        $ojtPlugin->sweetAlertCss                   = $pluginFullUrl . '/assets/vendors/sweetalert/sweetalert2.min.css';
        $ojtPlugin->pageName                        = 'ojt';

        $ojtPlugin->javascript  = [
            self::$_plugin->_getJQueryUrl($request),
            $pluginFullUrl . '/assets/vendors/sweetalert/sweetalert2.all.min.js',
            $pluginFullUrl . '/assets/js/theme.js',
            $pluginFullUrl . '/assets/js/jquery.form.min.js',
            $pluginFullUrl . '/assets/js/jquery.validate.min.js',
            $pluginFullUrl . '/assets/js/alpine/alpine.min.js',
            $pluginFullUrl . '/assets/js/alpine/component.min.js',
            $pluginFullUrl . '/assets/js/mainAlpine.js',
            $pluginFullUrl . '/assets/js/main.js',
        ];

        $templateMgr->assign('ojtPlugin', $ojtPlugin);

        return $templateMgr->display(self::$_plugin->getTemplateResource('index.tpl'));
    }

    public function setting($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch(self::$_plugin->getTemplateResource('setting.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function plugin_gallery($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch(self::$_plugin->getTemplateResource('plugingallery.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function plugin_installed($args, $request)
    {
        $templateMgr            = TemplateManager::getManager($request);

        $json['css']  = [];
        $json['html'] = $templateMgr->fetch(self::$_plugin->getTemplateResource('plugininstalled.tpl'));
        $json['js']   = [];
        return showJson($json);
    }

    public function save($args, $request)
    {
        ajaxOrError();

        $plugin = self::$_plugin;

        foreach ($_POST as $settingName => $settingValue) {
            $plugin->updateSetting($this->_contextId, $settingName, $settingValue);
        }

        $json['error']  = 0;
        $json['msg']    = 'Sukses';
        return showJson($json);
    }

    public function getInstalledPlugin($args, $request)
    {
        return showJson(self::$_plugin->_registeredModule ?? []);
    }

    public function toggleInstalledPlugin()
    {
        $plugin = self::$_plugin;

        $pluginFolder = $_POST['pluginFolder'];
        $isEnabled    = ($_POST['enabled'] == 'true') ? true : false;

        $targetPlugin         = include($plugin->getModulesPath() . "/$pluginFolder/index.php");

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
        $plugin = self::$_plugin;

        $pluginToInstall = json_decode($_POST['plugin']);
        $license         = $_POST['license'] ?? false;

        if (isset($_POST['upgrade']) && $targetPlugin = @include($plugin->getModulesPath() . "/$pluginToInstall->folder/index.php")) {
            $license = $targetPlugin->getSetting($this->_contextId, 'license');
        }


        $payload = [
            'token'        => $pluginToInstall->token,
            'license'       => $license,
            'journal_url'   => $this->_baseUrl,
        ];


        $response = $this->curl($payload, API . 'get_download_link', true);
        if ($response == false) {
            $json['error']  = 1;
            $json['msg']    = "There's a problem on the server, please try again later.";
            return showJson($json);
        }

        if ($response->error == 1) {
            $json['error']  = 1;
            $json['msg']    = $response->msg;
            return showJson($json);
        }

        $data = $response->data;

        // trying to install plugin
        try {
            $plugin->installPlugin($data->download_link);
        } catch (Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }

        // Applying input license to plugin setting  
        import('plugins.generic.ojtPlugin.modules.' . $pluginToInstall->folder . '.' . $pluginToInstall->class);
        $pluginInstance = new $pluginToInstall->class();
        if ($pluginInstance instanceof Plugin && isset($license)) {
            $pluginInstance->updateSetting($this->_contextId, 'licenseMain', $license);
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

        $cache = $pluginSettingsDao->_getCache($this->_contextId, $pluginName);
        $cache->flush();

        $pluginSettingsDao->update(
            'DELETE FROM plugin_settings WHERE context_id = ? AND plugin_name = ?
                AND setting_name NOT IN (\'license\', \'licenseMain\', \'status_validated\', \'html\', \'time\')',
            array((int) $this->_contextId, $pluginName)
        );

        if ($showJson) {
            $json['error'] = 0;
            $json['msg']   = 'Reset Setting Success.';
            showJson($json);
            return;
        }
    }

    public function uninstallPlugin($args, $request)
    {
        $plugin = self::$_plugin;

        $removePlugin = json_decode($_POST['plugin']);

        if ($_POST['resetSetting']) {
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

    public function checkCurrentPlugin()
    {
        $plugin          = self::$_plugin;

        $pluginFolder         = $_POST['pluginFolder'];
        $pluginVersion        = $_POST['pluginVersion'];

        $targetPlugin         = @include($plugin->getModulesPath() . "/$pluginFolder/index.php");

        $json['update']       = false;

        if ($targetPlugin) {
            import('lib.pkp.classes.site.VersionCheck');
            $version              = VersionCheck::parseVersionXML($plugin->getModulesPath() . "/$pluginFolder/version.xml");
            $json['update']       = version_compare($version['release'], $pluginVersion, '<');
        }

        $json['error']     = 0;
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
        $ch      = curl_init();
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
