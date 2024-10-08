<?php

namespace APP\plugins\generic\ojtControlPanel;

use PKP\plugins\Hook;
use PKP\db\DAORegistry;
use PKP\plugins\Plugin;
use APP\handler\Handler;
use APP\core\Application;
use PKP\file\FileManager;
use PKP\site\VersionCheck;
use Illuminate\Support\Str;
use APP\file\PublicFileManager;
use APP\template\TemplateManager;
use PKP\plugins\PluginSettingsDAO;
use GuzzleHttp\Exception\BadResponseException;
use APP\plugins\generic\ojtControlPanel\OjtControlPanelPlugin;

class OjtPageHandler extends Handler
{
    public OjtControlPanelPlugin $ojtPlugin;
    public $contextId;
    public $baseUrl;

    public function __construct($request)
    {
        if (!$request->getUser()) {
            $request->redirect(null, 'login', null, null);
        }

        $this->ojtPlugin = OjtControlPanelPlugin::get();
        $this->contextId = $this->ojtPlugin->getCurrentContextId();
        $this->baseUrl = $this->ojtPlugin->getJournalURL();
    }

    public function updatePanel($args, $request)
    {
        $plugin = $this->ojtPlugin;

        $ojtPlugin = json_decode($request->getUserVar('ojtPlugin'));

        $url = $ojtPlugin->link_download;
        // trying to install plugin
        try {
            $plugin->updatePanel($url);
        } catch (\Exception $e) {
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
            : $baseUrl . $publicFileManager->getContextFilesPath(Application::ASSOC_TYPE_JOURNAL, $this->contextId) . '/';

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

        Hook::call('OjtPageHandler::index', array(&$ojtPlugin));

        $templateMgr->assign('ojtPlugin', $ojtPlugin);
        $templateMgr->assign('journal', $this->contextId ? $request->getContext() : $request->getSite());

        $templateMgr->assign('pluginGalleryHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugingallery.tpl')));
        $templateMgr->assign('pluginInstalledHtml', $templateMgr->fetch($this->ojtPlugin->getTemplateResource('plugininstalled.tpl')));

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
        $this->ojtPlugin->updateSetting(Application::CONTEXT_SITE, 'enable_diagnostic', filter_var($request->getUserVar('enable_diagnostic'), FILTER_VALIDATE_BOOLEAN));
        $this->ojtPlugin->updateSetting($this->contextId, 'show_support_link_ojs', filter_var($request->getUserVar('show_support_link_ojs'), FILTER_VALIDATE_BOOLEAN));
        
        $json['error'] = 0;
        $json['msg']   = 'Save Success';
        return showJson($json);
    }

    public function downloadLog($args, $request)
    {
        $file = $this->ojtPlugin->getErrorLogFile();

        if (!file_exists($file)) {
            // create empty file
            file_put_contents($file, '');
        }

        $fileManager = new FileManager();

        return $fileManager->downloadByPath($file);
    }

    public function clearLog($args, $request)
    {
        $this->ojtPlugin->deleteLogFile();

        return showJson([
            'error' => 0,
            'msg' => 'Log file has been deleted.'
        ]);
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
            $files = $this->reArrayFiles($request->getUserVar('pictures'));
            $logFile = OjtControlPanelPlugin::getErrorLogFile();
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

            $ojtplugin = $this->ojtPlugin;

            $plugins = array_map(function ($plugin) use ($ojtplugin) {
                $plugin['folder'] = $pluginFolder = Str::camel($plugin['folder']);
                $pluginVersion = $plugin['version'];

                $targetPlugin = $this->ojtPlugin->instatiantePluginWithoutThrow($pluginFolder);


                $plugin['update'] = false;
                $plugin['license'] = $targetPlugin?->getSetting($this->contextId, 'license') ?? null;

                if ($targetPlugin) {
                    $version = VersionCheck::parseVersionXML($ojtplugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "version.xml"));
                    $plugin['update'] = version_compare($version['release'], $pluginVersion, '<');
                }

                $plugin['installed'] = ($targetPlugin) ? true : false;

                return $plugin;
            }, json_decode((string) $response->getBody(), true));

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
        $json['msg']    = 'Success Updating Settings.';
        return showJson($json);
    }

    public function getInstalledPlugin($args, $request)
    {
        return showJson($this->ojtPlugin->registeredModule ?? []);
    }

    public function toggleInstalledPlugin($args, $request)
    {
        try {
            $ojtPlugin      = $this->ojtPlugin;

            $pluginFolder   = $request->getUserVar('pluginFolder');
            $plugin         = $ojtPlugin->instatiatePlugin($pluginFolder);
            $isEnabled      = ($request->getUserVar('enabled') == 'true') ? true : false;

            if (!$plugin && !$plugin instanceof Plugin) {
                throw new \Exception("Plugin is Invalid");
            }

            $plugin->setEnabled($isEnabled);

            $enabledMessage = ($isEnabled) ? ' has been enabled.' : ' has been disabled.';
        } catch (\Throwable $th) {
            $json['error']  = 1;
            $json['msg']    = $th->getMessage();
            $json['enabled'] = false;
            return showJson($json);
        }



        $json['error']  = 0;
        $json['msg']    = 'The plugin ' . $plugin->getDisplayName() . $enabledMessage;
        $json['enabled'] = $isEnabled;
        return showJson($json);
    }

    public function installPlugin($args, $request)
    {
        try {
            $ojtPlugin = $this->ojtPlugin;
            $pluginToInstall = json_decode($request->getUserVar('plugin'));
            $license = $request->getUserVar('license') ?? false;
            $update = $request->getUserVar('update');
            $pluginFolder = Str::camel($pluginToInstall->folder);
            $pluginInstance = $ojtPlugin->instatiantePluginWithoutThrow($pluginFolder);
            if ($update && $pluginInstance) {

                $license = $pluginInstance?->getSetting($this->contextId, 'license');
            }

            $downloadLink = $ojtPlugin->getPluginDownloadLink($pluginToInstall->token, $license);
            if (!$downloadLink) throw new \Exception("There's a problem on the server, please try again later.");

            // trying to install dependencies
            foreach ($downloadLink['dependencies'] as $dependency) {
                $ojtPlugin->installPlugin($dependency['link']);
            }

            // trying to install plugin
            $ojtPlugin->installPlugin($downloadLink['product']);

            // FIXME: Disabled for now as when new plugin installed, the class is not autoloaded yet, trying to figure out how to autoload it
            // $this->simulateRegisterModules($pluginToInstall);

            // try to instantiate a plugin again
            $pluginInstance = $ojtPlugin->instatiantePluginWithoutThrow($pluginFolder);

            // Applying input license to plugin setting  
            if ($pluginInstance instanceof Plugin) {
                if ($license && !$update) {
                    $pluginInstance->updateSetting($this->contextId, 'license', $license);
                }
                Hook::call('OJT::pluginInstalled', array($pluginInstance));
            }


            $json['error']  = 0;
            $json['msg']    =  !$update ? 'Plugin Installed' : 'Plugin Updated';
            return showJson($json);
        } catch (\Exception $e) {
            $json['error']  = 1;
            $json['msg']    = $e->getMessage();
            return showJson($json);
        }
    }

    protected function toSnakeCase($string)
    {
        return Str::snake($string);
    }

    /**
     * Lakukan pengecekan sewaktu menginstall plugin baru
     * delete jika ada error
     */
    protected function simulateRegisterModules($pluginToInstall)
    {
        $ojtPlugin = $this->ojtPlugin;
        $pluginFolder = Str::camel($pluginToInstall->folder);
        // delete plugin when error occured
        register_shutdown_function(function () use ($ojtPlugin, $pluginToInstall, $pluginFolder) {
            $error = error_get_last();
            if (!in_array($error['type'], [E_COMPILE_ERROR, E_ERROR])) return;

            // Working directory berubah ketika callback ini berjalan, jadi harus mendapatkan fullpath
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $pluginFolder;
            try {
                if (!is_dir($path)) {
                    throw new \Exception("$path is not directory");
                    return;
                }
                $ojtPlugin->recursiveDelete($path);
            } catch (\Throwable $th) {
            }
        });

        if (!$ojtPlugin->instatiatePlugin($pluginFolder)) throw new \Exception("Plugin error");
    }

    public function resetSetting($args, $showJson = true)
    {
        $pluginName = is_array($args) ? $args[0] : $args;

        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        /** @var PluginSettingsDAO $pluginSettingsDao */
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
        } catch (\Exception $e) {
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
        $ojtPlugin = $this->ojtPlugin;

        $pluginFolder = $request->getUserVar('pluginFolder');
        $pluginVersion = $request->getUserVar('pluginVersion');

        $targetPlugin = $ojtPlugin->instatiatePlugin($pluginFolder);

        $json['update'] = false;

        if ($targetPlugin) {
            import('lib.pkp.classes.site.VersionCheck');
            $version = VersionCheck::parseVersionXML($ojtPlugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . "version.xml"));
            $json['update'] = version_compare($version['release'], $pluginVersion, '<');
        }

        $json['error'] = 0;
        $json['installed'] = ($targetPlugin) ? true : false;
        showJson($json);
    }
}
