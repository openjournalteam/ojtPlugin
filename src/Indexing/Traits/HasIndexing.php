<?php

namespace Openjournalteam\OjtPlugin\Indexing\Traits;

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
     * 2. Plugin Sitemap data file exist, which contain detail about itself
     */
    public function addIndexingPage($hookName, $args)
    {
        $xmlDom = $args[0];
        foreach ($this->getEnabledPlugins() as $folderName => $pluginName) {
            if ($this->hasSitemapData($folderName)) {
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

    public function hasSitemapData($pluginName): bool
    {
        $pluginContentPath = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'SitemapData.php';

        return file_exists($pluginContentPath);
    }
}
