<?php

import('classes.handler.Handler');

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
        $templateMgr->display(static::$ojtPlugin->getTemplateResource('sitemap/index.tpl'));
    }

    private function getJournalData(): array
    {
        $journal = static::$ojtPlugin->getJournal();
        $locale  = $journal->getPrimaryLocale();

        $content = [
            'journalName'          => $journal->getName($locale),
            'journalAbout'         => $journal->getDescription(),
            'onlineIssn'           => $journal->getData('onlineIssn') ?: '-',
            'printIssn'            => $journal->getData('printIssn') ?: '-',
            'productName'          => static::$ojtPlugin->getDisplayName(),
            'productVersion'       => static::$ojtPlugin->getPluginVersion(),
            'productInstalledDate' => static::$ojtPlugin->getPluginInstalledDate(),
            'cssPath'              => static::$ojtPlugin->getAssetUrl('stylesheets/sitemap.css'),
            'templatePath'         => static::$ojtPlugin->getTemplateResource(),
        ];

        $pluginContentPath = static::$ojtPlugin->getPluginPath() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . static::$plugin . DIRECTORY_SEPARATOR . 'SitemapData.php';

        if (file_exists($pluginContentPath)) {
            $pluginContents = include $pluginContentPath;
            foreach ($pluginContents as $key => $value) {
                $content[$key] = $value;
            }
        }

        return $content;
    }
}
