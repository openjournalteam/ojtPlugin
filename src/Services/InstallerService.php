<?php

namespace Openjournalteam\OjtPlugin\Services;

use GuzzleHttp\Exception\BadResponseException;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InstallerService
{
    private \OjtPlugin $plugin;

    public function __construct(\OjtPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function updatePanel($url)
    {
        // Check ziparchive extension
        if (!class_exists('ZipArchive')) {
            throw new Exception('Please Install PHP Zip Extension');
        }

        // Download file
        $file_name = \Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'OJTPanel.zip';

        $resource = \GuzzleHttp\Psr7\Utils::tryFopen($file_name, 'w');
        $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
        $this->plugin->getHttpClient()->request('GET', $url, ['sink' => $stream]);

        $zip = new \ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files plugin file');
        }

        $path    = 'plugins/generic';
        if (!$zip->extractTo($path)) {
            unlink($file_name);
            throw new Exception('Failed to Extract Plugin,maybe because of folder permission.');
        }
        $zip->close();

        unlink($file_name);
    }

    public function getPluginDownloadLink($pluginToken, $license = false, $journalUrl)
    {
        try {
            $payload = [
                'token' => $pluginToken,
                'license' => $license,
                'journal_url' => $journalUrl,
                'ojs_version' => $this->plugin->getJournalVersion()
            ];

            $request = $this->plugin->getHttpClient(['Content-Type' => 'application/x-www-form-urlencoded',])
                ->post(
                    \OjtPlugin::API . '/product/get_download_link',
                    [
                        'form_params' => $payload,
                    ]
                );

            $response = json_decode((string) $request->getBody(), true);

            if (isset($response['error']) && $response['error']) throw new Exception($response['msg']);

            $result['product'] = $response['data']['download_link'];

            $dependencies = [];
            foreach ($response['data']['dependencies'] as $dependency) {
                $data['link'] = $dependency['download_link'];
                $data['folder'] = $dependency['folder'];
                $dependencies[] = $data;
            }

            $result['dependencies'] = $dependencies;

            return $result;
        } catch (BadResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Installing plugin to targeted folder.
     * throw error if there is something wrong
     * @return bool - true if success.
     */
    public function installPlugin($url)
    {
        $url = str_replace('https', 'http', $url);

        // Download file
        $file_name = \Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'OJTTemporaryFile.zip';
        $resource = \GuzzleHttp\Psr7\Utils::tryFopen($file_name, 'w');
        $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
        $this->plugin->getHttpClient()->request('GET', $url, ['sink' => $stream]);
        // Extract file
        if (!class_exists('ZipArchive')) {
            unlink($file_name);
            throw new Exception('Please Install PHP Zip Extension');
        }

        $zip = new \ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files');
        }

        $path    = $this->plugin->getModulesPath();
        if (!$zip->extractTo($path)) {
            unlink($file_name);
            throw new Exception('Failed to Extract Plugin, because of folder permission.');
        }
        $zip->close();

        unlink($file_name);

        return true;
    }

    /**
     * Get the base path for staging directory
     * @return string
     */
    public function getStagingBasePath()
    {
        return \Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'ojt_staging';
    }

    /**
     * Install plugin to staging directory first
     * @param string $url Download URL for the plugin
     * @return array ['stagingPath' => string, 'pluginFolder' => string]
     * @throws Exception if installation fails
     */
    public function installPluginToStaging($url)
    {
        $url = str_replace('https', 'http', $url);

        // Download file
        $file_name = \Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'OJTTemporaryFile_' . uniqid() . '.zip';
        $resource = \GuzzleHttp\Psr7\Utils::tryFopen($file_name, 'w');
        $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
        $this->plugin->getHttpClient()->request('GET', $url, ['sink' => $stream]);

        // Extract file
        if (!class_exists('ZipArchive')) {
            unlink($file_name);
            throw new Exception('Please Install PHP Zip Extension');
        }

        $zip = new \ZipArchive;
        if (!$zip->open($file_name)) {
            unlink($file_name);
            throw new Exception('Failed to Open Files');
        }

        // Create staging directory
        $stagingBasePath = $this->getStagingBasePath();
        if (!is_dir($stagingBasePath)) {
            if (!mkdir($stagingBasePath, 0755, true)) {
                unlink($file_name);
                throw new Exception('Failed to create staging directory');
            }
        }

        // Create unique staging path for this installation
        $stagingPath = $stagingBasePath . DIRECTORY_SEPARATOR . 'staging_' . uniqid();
        if (!mkdir($stagingPath, 0755, true)) {
            unlink($file_name);
            throw new Exception('Failed to create staging subdirectory');
        }

        if (!$zip->extractTo($stagingPath)) {
            unlink($file_name);
            $this->recursiveDelete($stagingPath);
            throw new Exception('Failed to Extract Plugin to staging, because of folder permission.');
        }

        // Detect the plugin folder name from extracted contents
        $extractedFolders = array_diff(scandir($stagingPath), ['.', '..']);
        if (empty($extractedFolders)) {
            unlink($file_name);
            $this->recursiveDelete($stagingPath);
            throw new Exception('No plugin folder found in extracted archive');
        }

        $pluginFolder = reset($extractedFolders);

        $zip->close();
        unlink($file_name);

        return [
            'stagingPath' => $stagingPath,
            'pluginFolder' => $pluginFolder
        ];
    }

    /**
     * Move staged plugin to final destination
     * @param string $stagingPath Path to staging directory
     * @param string $pluginFolder Plugin folder name
     * @param bool $isSiteWide Whether plugin is site-wide
     * @throws Exception if move fails
     */
    public function moveStagedPluginToFinal($stagingPath, $pluginFolder, $isSiteWide)
    {
        $sourcePath = $stagingPath . DIRECTORY_SEPARATOR . $pluginFolder;

        if ($isSiteWide) {
            $destinationPath = getcwd() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . $pluginFolder;
        } else {
            $this->plugin->createModulesFolder();
            $destinationPath = getcwd() . DIRECTORY_SEPARATOR . $this->plugin->getModulesPath($pluginFolder);
        }

        if (!is_dir($sourcePath)) {
            throw new Exception("Source plugin directory not found in staging: {$sourcePath}");
        }

        // Remove existing destination if it exists
        if (is_dir($destinationPath)) {
            $this->recursiveDelete($destinationPath);
        }

        // Move the plugin directory
        if (!rename($sourcePath, $destinationPath)) {
            throw new Exception("Failed to move plugin from staging to final destination");
        }

        // Clean up the staging directory
        if (is_dir($stagingPath) && count(array_diff(scandir($stagingPath), ['.', '..'])) === 0) {
            rmdir($stagingPath);
        }

        $location = $isSiteWide ? 'plugins/generic/' : 'modules/';
        error_log("Plugin '{$pluginFolder}' installed to {$location}");
    }

    /**
     * Instantiate a plugin from the modules directory without throwing an exception
     * @param string $pluginFolder Plugin folder name
     * @return Plugin|false Plugin instance or false if not found
     */
    public function instantiatePluginWithoutThrow($pluginFolder)
    {
        $indexFile = $this->plugin->getModulesPath($pluginFolder . DIRECTORY_SEPARATOR . 'index.php');
        if (!file_exists($indexFile)) {
            return false;
        }
        return @include($indexFile);
    }

    /**
     * Instantiate a plugin from the global plugins/generic directory
     * @param string $pluginFolder Plugin folder name
     * @return Plugin|false Plugin instance or false if not found
     */
    public function instantiatePluginFromGlobalDirectory($pluginFolder)
    {
        $indexFile = getcwd() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . $pluginFolder . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($indexFile)) {
            return false;
        }
        return @include($indexFile);
    }

    /**
     * Clean up old staging directories
     * @param int $hoursOld Delete staging directories older than this many hours
     */
    public function cleanupOldStagingDirectories($hoursOld = 24)
    {
        $stagingBasePath = $this->getStagingBasePath();
        if (!is_dir($stagingBasePath)) {
            return;
        }

        $cutoffTime = time() - ($hoursOld * 3600);
        $dirs = array_diff(scandir($stagingBasePath), ['.', '..']);

        foreach ($dirs as $dir) {
            $dirPath = $stagingBasePath . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($dirPath) && filemtime($dirPath) < $cutoffTime) {
                try {
                    $this->recursiveDelete($dirPath);
                    error_log("Cleaned up old staging directory: {$dirPath}");
                } catch (Exception $e) {
                    error_log("Failed to clean up staging directory: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Removing plugin folder
     * @return bool - true if success.
     */
    public function uninstallPlugin($plugin)
    {
        $path = $this->plugin->getModulesPath($plugin->product);
        if ($plugin->sitewide == true) {
            $path = 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . $plugin->product;
        }
        try {
            if (!is_dir($path)) {
                throw new Exception("$plugin->name not Found");
            }
            return $this->recursiveDelete($path);
        } catch (\Throwable $th) {
            $data['error'] = $th;
            $data['error_type'] = 'pluginRemoveError';

            // Send notification to discord about the deletion error in uninstallPlugin
            // $this->plugin->sendDiscordNotification($plugin->name, $data);

            // Re-throw for proper error handling at caller level
            throw $th;
        }
    }

    public function recursiveDelete($dirPath, $deleteParent = true)
    {
        if (empty($dirPath) && !is_dir($dirPath)) {
            return false;
        }

        $paths = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($paths as $path) {
            if (!$path->isWritable()) {
                throw new Exception("Can't remove plugins, please check folder permission for: " . $path->getPathname());
            };
        }

        foreach ($paths as $path) {
            if ($path->isFile()) {
                if (!unlink($path->getPathname())) {
                    throw new Exception("Failed to delete file: " . $path->getPathname());
                }
            } else {
                if (!rmdir($path->getPathname())) {
                    throw new Exception("Failed to remove directory: " . $path->getPathname());
                }
            }
        }

        if ($deleteParent) {
            rmdir($dirPath);
        }

        return true;
    }
}
