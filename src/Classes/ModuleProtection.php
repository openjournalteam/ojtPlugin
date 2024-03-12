<?php

namespace Openjournalteam\OjtPlugin\Classes;

use FileManager;
use OjtPlugin;

class ModuleProtection
{
    public $module;
    public $path;

    public function __construct($module, $path)
    {
        $this->module = $module;
        $this->path   = $path;
    }

    public function generateProtectionFile() : void
    {
        $fileManager            = new FileManager();

        $fileManager->writeFile($this->getFilePath(), $this->hash());
    }

    public function hash() : string
    {
        $ojtPlugin = OjtPlugin::get();

        $array = [
            'journal_url'   => $ojtPlugin->getJournalURL(),
            'product'       => $this->module->getName(),
        ];

        return md5(json_encode($array));
    }

    public function check() : bool
    {
        $fileManager            = new FileManager();
        if ($fileManager->fileExists($this->getFilePath())) {
            $hash = $fileManager->readFileFromPath($this->getFilePath());
            if ($hash === $this->hash()) {
                return true;
            }
        }

        return false;
    }

    public function getFilePath() : string
    {
        return $this->path . 'installed';
    }
}
