<?php

import('classes.handler.Handler');

class IndexingPageHandler extends Handler
{
    protected static $plugin;

    public static function setPlugin($plugin)
    {
        static::$plugin = $plugin;
    }

    public function index($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign($this->getJournalData());
        $templateMgr->display(static::$plugin->getTemplateResource('sitemap/index.tpl'));
    }

    private function getJournalData(): array
    {
        $journal = static::$plugin->getJournal();
        $locale  = $journal->getPrimaryLocale();
        // $ass = 'http://localhost/ojs-3.3/plugins/generic/ojtPlugin/modules/ojtPlus/assets/css/tailwindcss.css';
        $ass = static::$plugin->getAssetUrl('css/sitemap.css');
        // dd($ass);
        return [
            'cssPath'              => $ass,
            'journalName'          => $journal->getName($locale),
            'journalAbout'         => $journal->getDescription(),
            'onlineIssn'           => $journal->getData('onlineIssn') ?: '-',
            'printIssn'            => $journal->getData('printIssn') ?: '-',
            'productName'          => static::$plugin->getDisplayName(),
            'productVersion'       => static::$plugin->getPluginVersion(),
            'productInstalledDate' => static::$plugin->getPluginInstalledDate(),
            'productDescription'   => 'OJT Plus is an OJS plugin that adds extended features for your journal, including allow to add video abstract, allow to change submission date, accepted date, published date, improve OJS performance, and more',
            'productUrl'           => 'https://openjournaltheme.com',
            'templatePath'         => static::$plugin->getTemplateResource(),
        ];
    }
}
