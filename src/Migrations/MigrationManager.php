<?php

namespace Openjournalteam\OjtPlugin\Migrations;

class MigrationManager
{
    protected $_plugin;

    public function __construct($plugin)
    {
        $this->_plugin = $plugin;
    }

    public static function make(...$args)
    {
        return new static(...$args);
    }

    public function getMigrations()
    {
        $migrations = [];
        $migrationPath = $this->_plugin->getPluginPath() . "/src/Migrations/";

        $files = glob($migrationPath . 'v*.php');
        foreach ($files as $file) {
            require_once $file;
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClassName = "Openjournalteam\\OjtPlugin\\Migrations\\$className";

            if (class_exists($fullClassName)) {
                $migrations[$className] = new $fullClassName();
            }
        }

        return $migrations;
    }

    public function runMigrations()
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $version => $migration) {
            if (!$this->_plugin->getSetting(CONTEXT_SITE, 'ojt_plugin_migration_' . $version)) {
                $migration->up();
                $this->_plugin->updateSetting(CONTEXT_SITE, 'ojt_plugin_migration_' . $version, true);
            }
        }
    }

    public function rollbackMigrations()
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $version => $migration) {
            if ($this->_plugin->getSetting(CONTEXT_SITE, 'ojt_plugin_migration_' . $version)) {
                $migration->down();
                $this->_plugin->updateSetting(CONTEXT_SITE, 'ojt_plugin_migration_' . $version, false);
            }
        }
    }
}