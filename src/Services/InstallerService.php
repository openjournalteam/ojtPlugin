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
        if (!is_string($pluginFolder)
            || $pluginFolder === ''
            || $pluginFolder === '.'
            || $pluginFolder === '..'
            || strpbrk($pluginFolder, '/\\\\') !== false
            || strpos($pluginFolder, "\0") !== false) {
            throw new Exception('Invalid plugin folder');
        }

        $sourcePath = $stagingPath . DIRECTORY_SEPARATOR . $pluginFolder;

        if ($isSiteWide) {
            $destinationPath = getcwd() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . $pluginFolder;
        } else {
            $this->plugin->createModulesFolder();
            $destinationPath = getcwd() . DIRECTORY_SEPARATOR . $this->plugin->getModulesPath($pluginFolder);
        }

        if (is_link($sourcePath) || !is_dir($sourcePath)) {
            throw new Exception("Source plugin directory not found in staging: {$sourcePath}");
        }

        if (!is_file($sourcePath . DIRECTORY_SEPARATOR . 'index.php')) {
            throw new Exception("Invalid plugin structure: missing index.php in {$sourcePath}");
        }

        $destinationParent = dirname($destinationPath);
        if (!is_dir($destinationParent) || !is_writable($destinationParent)) {
            throw new Exception("Plugin destination is not writable: {$destinationParent}");
        }

        $destinationExists = file_exists($destinationPath) || is_link($destinationPath);
        if ($destinationExists) {
            if (!$this->plugin->isCurrentUserSiteAdmin()) {
                throw new Exception('Only site administrators can replace an installed plugin');
            }
            if (!is_dir($destinationPath) || is_link($destinationPath)) {
                throw new Exception("Plugin destination is not a regular directory: {$destinationPath}");
            }
        }

        // rename() is atomic and cheap when both paths are on the same filesystem.
        if (!$destinationExists && @rename($sourcePath, $destinationPath)) {
            $this->cleanupStagingAfterInstall($sourcePath, $stagingPath);
            $this->logPluginInstalled($pluginFolder, $isSiteWide);
            return;
        }

        // Copy beside the destination so the final rename stays on one filesystem.
        $temporaryPath = $destinationParent . DIRECTORY_SEPARATOR . '.' . $pluginFolder . '.ojt-' . bin2hex(random_bytes(8));
        $backupPath = null;
        $sourceMovedToTemporary = false;
        $destinationMovedToBackup = false;

        try {
            $sourceMovedToTemporary = @rename($sourcePath, $temporaryPath);
            if (!$sourceMovedToTemporary) {
                $this->copyPluginDirectory($sourcePath, $temporaryPath, new \FileManager());
            }

            if (!is_file($temporaryPath . DIRECTORY_SEPARATOR . 'index.php')) {
                throw new Exception('Copied plugin is incomplete: index.php is missing');
            }

            if ($destinationExists) {
                $backupPath = $destinationParent . DIRECTORY_SEPARATOR . '.' . $pluginFolder . '.backup-' . bin2hex(random_bytes(8));
                if (!@rename($destinationPath, $backupPath)) {
                    throw new Exception("Failed to preserve the installed plugin at {$destinationPath}");
                }
                $destinationMovedToBackup = true;
            }

            if (!@rename($temporaryPath, $destinationPath)) {
                if ($destinationMovedToBackup && !@rename($backupPath, $destinationPath)) {
                    throw new Exception("Failed to install plugin; the previous version is preserved at {$backupPath}");
                }
                $destinationMovedToBackup = false;
                throw new Exception("Failed to place copied plugin at {$destinationPath}");
            }
            $destinationMovedToBackup = false;

            if ($backupPath !== null) {
                try {
                    $this->recursiveDelete($backupPath);
                } catch (\Throwable $e) {
                    error_log("Plugin installed, but could not remove previous version at {$backupPath}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            if (is_dir($temporaryPath)) {
                if ($sourceMovedToTemporary) {
                    if (!@rename($temporaryPath, $sourcePath)) {
                        error_log("Could not restore staged plugin from {$temporaryPath} to {$sourcePath}");
                    }
                } else {
                    try {
                        $this->recursiveDelete($temporaryPath);
                    } catch (\Throwable $cleanupError) {
                        error_log("Could not remove incomplete plugin copy at {$temporaryPath}: " . $cleanupError->getMessage());
                    }
                }
            }
            throw $e;
        }

        $this->cleanupStagingAfterInstall($sourcePath, $stagingPath);
        $this->logPluginInstalled($pluginFolder, $isSiteWide);
    }

    private function copyPluginDirectory($sourcePath, $destinationPath, $fileManager)
    {
        if (is_link($sourcePath) || !is_dir($sourcePath)) {
            throw new Exception("Invalid plugin directory while copying: {$sourcePath}");
        }

        if (!@mkdir($destinationPath, 0755)) {
            throw new Exception("Could not create temporary plugin directory on destination filesystem: {$destinationPath}");
        }

        $entries = @scandir($sourcePath);
        if ($entries === false) {
            throw new Exception("Could not read staged plugin directory: {$sourcePath}");
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $source = $sourcePath . DIRECTORY_SEPARATOR . $entry;
            $destination = $destinationPath . DIRECTORY_SEPARATOR . $entry;

            if (is_link($source)) {
                throw new Exception("Plugin archive contains an unsupported symbolic link: {$source}");
            }

            if (is_dir($source)) {
                $this->copyPluginDirectory($source, $destination, $fileManager);
                continue;
            }

            if (!is_file($source) || !@$fileManager->copyFile($source, $destination)) {
                throw new Exception("Could not copy plugin file to destination (check free space and permissions): {$source}");
            }

            $sourceSize = @filesize($source);
            $destinationSize = @filesize($destination);
            if ($sourceSize === false || $destinationSize !== $sourceSize) {
                throw new Exception("Copied plugin file is incomplete: {$destination}");
            }
        }
    }

    private function cleanupStagingAfterInstall($sourcePath, $stagingPath)
    {
        if (is_dir($sourcePath)) {
            try {
                $this->recursiveDelete($sourcePath);
            } catch (\Throwable $e) {
                error_log("Plugin installed, but could not remove staging source at {$sourcePath}: " . $e->getMessage());
            }
        }

        if (is_dir($stagingPath) && count(array_diff(scandir($stagingPath), ['.', '..'])) === 0) {
            @rmdir($stagingPath);
        }
    }

    private function logPluginInstalled($pluginFolder, $isSiteWide)
    {
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
        if (!$this->plugin->isCurrentUserSiteAdmin()) {
            throw new Exception('Only site administrators can uninstall plugins');
        }

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

        $parentDirectory = dirname($dirPath);
        if (!is_writable($parentDirectory)) {
            throw new Exception("Can't remove plugins, please check folder permission for: " . $parentDirectory);
        }

        $paths = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($paths as $path) {
            // Removing a file requires write/execute permission on its parent
            // directory; the file itself may legitimately be read-only (for
            // example, Git object files are commonly mode 0444).
            if (!is_writable($path->getPath())) {
                throw new Exception("Can't remove plugins, please check folder permission for: " . $path->getPath());
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
