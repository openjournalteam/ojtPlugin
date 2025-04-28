<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Handler;
use TemplateManager;

class IndexingPageHandler extends Handler
{
    protected static $ojtPlugin;
    protected static $plugin;

    public static function setPlugin($ojtPlugin, $plugin)
    {
        static::$ojtPlugin = $ojtPlugin;
        static::$plugin = $plugin;
    }

    public function index($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign($this->getJournalData());
        $templateMgr->addStyleSheet('ojtplugin', static::$ojtPlugin->getAssetUrl('stylesheets/sitemap.css'), ['baseUrl' => '', 'contexts' => ['frontend']]);
        $templateMgr->display(static::$ojtPlugin->getTemplateResource('sitemap.tpl'));
    }

    private function getJournalData(): array
    {
        $journal = static::$ojtPlugin->getJournal();
        $locale  = $journal->getPrimaryLocale();

        $content = [
            'journalName'          => $journal->getName($locale),
            'journalAbout'         => $journal->getDescription($locale),
            'journalUrl'           => static::$ojtPlugin->getJournalUrl(),
            'onlineIssn'           => $journal->getData('onlineIssn') ?: '-',
            'printIssn'            => $journal->getData('printIssn') ?: '-',
        ];

        $modules = static::$ojtPlugin->getEnabledPluginsSitemap();
        if ($sitemapData = $modules[static::$plugin]) {
            $content = array_merge($content, $sitemapData);
        }

        return $content;
    }
}
