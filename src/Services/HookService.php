<?php

namespace Openjournalteam\OjtPlugin\Services;

use Openjournalteam\OjtPlugin\Classes\IndexingPageHandler;

class HookService
{
    private \OjtPlugin $plugin;

    public function __construct(\OjtPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function registerHooks(): void
    {
        // HookRegistry::register('Template::Settings::website', array($this, 'settingsWebsite'));
        \HookRegistry::register('LoadHandler', [$this, 'setPageHandler']);
        \HookRegistry::register('TemplateManager::setupBackendPage', [$this, 'setupBackendPage']);
        \HookRegistry::register('TemplateManager::display', [$this, 'fixThemeNotLoadedOnFrontend']);
        // \HookRegistry::register('TemplateManager::display', [$this, 'addHeader']);
        \HookRegistry::register('SitemapHandler::createJournalSitemap', [$this->plugin, 'addIndexingPage']);
    }

    public function fixThemeNotLoadedOnFrontend($hookName, $args)
    {
        $templateMgr            = $args[0];
        if ($this->plugin->getJournalVersion() != '31') {
            if ($templateMgr->getTemplateVars('activeTheme')) return;
        }

        $allThemes = \PluginRegistry::loadCategory('themes', true);
        $activeTheme = null;
        $context = $this->plugin->getCurrentContextId() ? $this->plugin->getRequest()->getContext() : $this->plugin->getRequest()->getSite();
        $themePluginPath = $context->getData('themePluginPath');

        foreach ($allThemes as $theme) {
            if ($themePluginPath === basename($theme->pluginPath) && $theme->getEnabled()) {
                $activeTheme = $theme;
                break;
            }
        }

        $templateMgr->assign('activeTheme', $activeTheme);
    }

    public function addHeader($hookName, $args)
    {
        $templateMgr            = &$args[0];

        $templateMgr->addHeader(
            'ojtcontrolpanel',
            '<meta name="ojtcontrolpanel" content="OJT Control Panel Version ' . $this->plugin->getPluginVersion() . ' by openjournaltheme.com">',
            [
                'contexts' => ['frontend'],
            ]
        );
    }

    public function setupBackendPage($hookName, $args)
    {
        $request = $this->plugin->getRequest();

        if (!$request->getContext()) return;

        $templateMgr    = \TemplateManager::getManager($this->plugin->getRequest());
        $router         = $request->getRouter();
        $userRoles      = (array) $router->getHandler()->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
        $user           = $request->getUser();

        if (!$user || !count(array_intersect([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN], $userRoles))) return;

        $menu = $templateMgr->getState('menu');
        $menu['ojtPlugin'] = [
            'name' => 'OJT Control Panel',
            'url' => $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath(), 'ojt') . '?PageSpeed=off',
            "isCurrent" => false
        ];

        if ($this->plugin->getSetting($this->plugin->getCurrentContextId(), 'show_support_link_ojs') ?? true) {
            $menu['ojtSupportTicketing'] = [
                'name' => 'Get OJT support',
                'url' => $request->getDispatcher()->url($request, ROUTE_PAGE, $request->getContext()->getPath(), 'ojt', 'support'),
                "isCurrent" => false
            ];
        }

        $templateMgr->setState(['menu' => $menu]);
    }

    public function setPageHandler($hookName, $params)
    {
        if ($this->plugin->getCurrentContextId() == 0) {
            // Panel tidak support untuk sitewide
            return false;
        }

        $page = $params[0];
        $op   = &$params[1];

        // Force http or https based on host
        $request = \Application::get()->getRequest();
        $serverHost = $request->getServerHost(null, false);
        $host = explode(':', (string) $serverHost)[0];
        $shouldUseHttpProtocol = $this->shouldUseHttpProtocolForHost($host);
        // $request->_protocol = $shouldUseHttpProtocol ? 'http' : 'https';

        if ($page === 'ojt' && $op === 'api') {
            define('HANDLER_CLASS', 'OjtPluginApiHandler');
            $this->plugin->import('OjtPluginApiHandler');

            return true;
        }

        switch ($page) {
            case 'ojt':
                define('HANDLER_CLASS', 'OjtPageHandler');
                $this->plugin->import('OjtPageHandler');

                return true;
                break;
            case $this->plugin->getIndexingPagePath():
                $enabledPlugins = $this->plugin->getEnabledPluginsSitemap();

                // don't show page for plugins that is not enabled
                // and don't have getSitemapData method in it
                if (!$this->plugin->isAddSitemap($enabledPlugins[$op] ?? null)) {
                    return false;
                }

                $plugin = $params[1];
                $op = 'index';

                define('HANDLER_CLASS', IndexingPageHandler::class);
                IndexingPageHandler::setPlugin($this->plugin, $plugin);

                return true;
        }

        return false;
    }

    private function shouldUseHttpProtocolForHost(string $host)
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host)) {
            return true;
        }

        return false;
    }

    // Show available update on Setting -> Website
    public function settingsWebsite($hookName, $args)
    {
        if (!$this->plugin->getSetting(CONTEXT_SITE, 'isNewVersionAvailable')) {
            return false;
        }

        $templateMgr = $args[1];
        $output = &$args[2];

        $output .= $templateMgr->fetch($this->plugin->getTemplateResource('backend/notif.tpl'));

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    // addIndexingPage handled directly by OjtPlugin (trait).
}
