<?php

namespace Openjournalteam\OjtPlugin\Traits;

use DAORegistry;
use DateTime;

trait HasIndexing
{
    private string $pagePath = "about-product";

    public function getIndexingPagePath()
    {
        return $this->pagePath;
    }

    /**
     * Only register to sitemap if
     * 1. Plugin is enabled
     * 2. getSitemapData method exist in the plugins
     */
    public function addIndexingPage($hookName, $args)
    {
        $xmlDom = $args[0];
        foreach ($this->getEnabledPluginsSitemap() as $pluginName => $sitemapData) {
            if ($this->isAddSitemap($sitemapData)) {
                $this->updateXmlSitemap($xmlDom, $this->pagePath . '/' . $pluginName);
            }
        }
    }

    private function updateXmlSitemap($xmlDom, $pagePath)
    {
        $url = $xmlDom->createElement('url');
        $loc = $xmlDom->createElement('loc');
        $loc->textContent = $this->getJournalUrl() . '/' . $pagePath;
        $url->appendChild($loc);
        $urlset = $xmlDom->getElementsByTagName('urlset')->item(0);
        $urlset->appendChild($url);
    }

    public function getJournalUrl(): string
    {
        $request = $this->getRequest();
        $router  = $request->getRouter();
        $journal = $request->getContext();
        return $router->url($request, $journal->getPath());
    }

    public function getPluginInstalledDate(): string
    {
        $dao     = DAORegistry::getDAO('VersionDAO');
        $version = $dao->getCurrentVersion("plugins.{$this->getCategory()}", $this->getDirName());
        $date = $version->getData("dateInstalled");
        if (!$date) {
            return '-';
        }
        return (new DateTime($date))->format('Y-m-d');
    }

    public function getJournal()
    {
        $request = $this->getRequest();
        $journal = $request->getContext();

        if (!$journal) {
            $dispatcher = $request->getDispatcher();
            $dispatcher->handle404();
            return;
        }

        return $journal;
    }

    public function getEnabledPluginsSitemap(): array
    {
        $listOfEnabledPlugins = [];
        foreach ($this->getRegisteredModules() as $plugin) {
            if ($plugin['enabled'] ?? false) {
                $listOfEnabledPlugins[$plugin['className']] = array_merge(
                    [
                        'productName'            => $plugin['name'],
                        'productDescription'     => $plugin['description'],
                        'productInstalledDate'   => $this->getPluginInstalledDate(),
                        'productVersion'         => $plugin['version'],
                        'productUrl'             => 'https://openjournaltheme.com',
                        'productLongDescription' => [],
                    ],
                    $plugin['sitemapData'] ?? [] // from getSitemapData in each plugins
                );
            }
        }
        return $listOfEnabledPlugins;
    }

    public function isAddSitemap($plugin)
    {
        return isset($plugin['productLongDescription']) && count($plugin['productLongDescription']);
    }
}
