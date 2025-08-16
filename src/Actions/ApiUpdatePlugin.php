<?php 

namespace Openjournalteam\OjtPlugin\Actions;

use JSONMessage;
use OjtPlugin;
use OjtPluginApiHandler;
use Openjournalteam\OjtPlugin\Traits\ApiPostValidate;
use PluginRegistry;
use VersionCheck;
use ZipArchive;

import('lib.pkp.classes.plugins.Plugin');

class ApiUpdatePlugin
{
    use ApiPostValidate;

    public OjtPlugin $ojtPlugin;

    public function __construct()
    {
        $this->ojtPlugin = new OjtPlugin();
    }

    public function handle($args, $request)
    {
        return $this->checkUpdatePlugin($args, $request);
    }

    public function checkUpdatePlugin($args, $request)
    {
        $this->validatePostRequest($request);

        $getBearerToken = $this->validateBearerToken();

        $pluginClass = $args['pluginClass'] ?? null;
        $getAllPlugins = PluginRegistry::getAllPlugins();
        if(!isset($getAllPlugins[$pluginClass])) {
            http_response_code(404); // Not Found
            return new JSONMessage(false, 'Plugin class not found: ' . $pluginClass);
        }
        $plugin = $getAllPlugins[$pluginClass];

        // check token
        $getServicePanelData = $plugin->getSetting(CONTEXT_SITE, 'service_panel_data');
        if($getServicePanelData['token'] == null) {
            http_response_code(403); // Forbidden
            return new JSONMessage(false, 'Service panel token is not set for this plugin.');
        }

        if ($getServicePanelData['token'] !== $getBearerToken) {
            http_response_code(403); // Forbidden
            return new JSONMessage(false, 'Invalid or expired token.');
        }

        $data = null;
        if (!empty($request->getUserVars())) {
            $data = $request->getUserVars();
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }

        if (empty($data)) {
            http_response_code(400); // Bad Request
            return new JSONMessage(false, 'Request body is empty or invalid.');
        }

        $requiredFields = ['link_download', 'latest_version', 'ojs_version'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return new JSONMessage(false, "Missing required parameter: $field");
            }
        }

        $latestVersion = $data['latest_version'];
        $linkDownload = $data['link_download'];
        $ojsVersion = $data['ojs_version'];

        // validate OJS version
        if ($ojsVersion != $this->ojtPlugin->getJournalVersion()) {
            http_response_code(400); // Bad Request
            return new JSONMessage(false, 'OJS version mismatch. Expected: ' . $this->ojtPlugin->getJournalVersion() . ', Received: ' . $ojsVersion);
        }

        // validate link download if https
        if (stripos($linkDownload, 'https://') !== 0) {
            http_response_code(400); // Bad Request
            return new JSONMessage(false, 'Download link must start with "https://".');
        }

        // validate plugin version
        import('lib.pkp.classes.site.VersionCheck');
        $version = VersionCheck::parseVersionXML($plugin->getPluginPath() . '/version.xml');

        // Check if latest version is lower than current version
        if (version_compare($latestVersion, $version['release'], '<')) {
            http_response_code(409); // Conflict
            return new JSONMessage(false, 'Latest version is lower than current version. Current: ' . $version['release'] . ', Latest: ' . $latestVersion);
        }

        // Check if latest version is equal to current version
        if (version_compare($latestVersion, $version['release'], '=')) {
            http_response_code(409); // Conflict
            return new JSONMessage(false, 'Plugin is already up to date. Current version: ' . $version['release']);
        }

        $dataPlugin = [
            'plugin_class' => $plugin,
            'class' => $pluginClass,
            'category' => $plugin->getCategory(),
            'path' => $plugin->getPluginPath(),
        ];

        try {
            $updateResult = $this->downloadAndExtractPlugin($linkDownload, $dataPlugin);
            
            if (!$updateResult['success']) {
                http_response_code(500);
                return new JSONMessage(false, $updateResult['message']);
            }

            $response = [
                'ojs_version' => $ojsVersion,
                'product_version' => $latestVersion,
                'update_success' => true,
                'message' => 'Plugin updated successfully'
            ];

            header('Content-Type: application/json');
            http_response_code(200);
            return json_encode($response);

        } catch (\Exception $e) {
            error_log('Plugin update failed: ' . $e->getMessage());
            http_response_code(500);
            return new JSONMessage(false, 'Plugin update failed: ' . $e->getMessage());
        }
    }

    /**
     * Download and extract plugin from URL
     * 
     * @param string $url Download URL for the plugin ZIP file
     * @param array $dataPlugin Plugin information array containing class, category, and path
     * @return array Result array with success status and message
     */
    private function downloadAndExtractPlugin($url, $dataPlugin)
    {
        // Check if ZipArchive extension is available
        if (!class_exists('ZipArchive')) {
            return [
                'success' => false,
                'message' => 'PHP ZipArchive extension is required but not installed'
            ];
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Invalid download URL provided'
            ];
        }

        $pluginClass = $dataPlugin['class'];
        $pluginCategory = $dataPlugin['category'];
        $pluginPath = $dataPlugin['path'];
        
        // put in files directory
        $filesDir = \Config::getVar('files', 'files_dir');
        if (!$filesDir || !is_writable($filesDir)) {
            return [
                'success' => false,
                'message' => 'Files directory is not configured or not writable'
            ];
        }
        
        $tempFileName = $filesDir . DIRECTORY_SEPARATOR . 'plugin-' . $pluginClass . '.zip';
        
        try {
            // Download the plugin ZIP file
            $downloadResult = $this->downloadFile($url, $tempFileName);
            if (!$downloadResult['success']) {
                return $downloadResult;
            }

            // Extract the plugin
            $extractResult = $this->extractPlugin($tempFileName, $pluginPath);
            
            // Clean up temporary file
            if (file_exists($tempFileName)) {
                unlink($tempFileName);
            }
            
            return $extractResult;

        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempFileName)) {
                unlink($tempFileName);
            }
            
            return [
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download file from URL using Guzzle HTTP client
     * 
     * @param string $url Source URL
     * @param string $destination Local file path
     * @return array Result array with success status and message
     */
    private function downloadFile($url, $destination)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 60, // 60 seconds timeout
                'verify' => true, // Verify SSL certificates
            ]);

            $resource = fopen($destination, 'w');
            if (!$resource) {
                return [
                    'success' => false,
                    'message' => 'Cannot create temporary file for download'
                ];
            }

            $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
            $response = $client->request('GET', $url, [
                'sink' => $stream,
                'headers' => [
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'OJS-Plugin-Updater/1.0',
                ]
            ]);

            fclose($resource);

            if ($response->getStatusCode() !== 200) {
                unlink($destination);
                return [
                    'success' => false,
                    'message' => 'Download failed with HTTP status: ' . $response->getStatusCode()
                ];
            }

            // Verify file was downloaded and has content
            if (!file_exists($destination) || filesize($destination) === 0) {
                if (file_exists($destination)) {
                    unlink($destination);
                }
                return [
                    'success' => false,
                    'message' => 'Downloaded file is empty or corrupt'
                ];
            }

            return ['success' => true, 'message' => 'File downloaded successfully'];

        } catch (\Exception $e) {
            if (file_exists($destination)) {
                unlink($destination);
            }
            return [
                'success' => false,
                'message' => 'Download error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract plugin ZIP file to appropriate directory
     * 
     * @param string $zipFile Path to ZIP file
     * @param string $pluginPath Path to plugin directory
     * @return array Result array with success status and message
     */
    private function extractPlugin($zipFile, $pluginPath)
    {
        try {
            $zip = new ZipArchive();
            $result = $zip->open($zipFile);
            
            if ($result !== true) {
                $errorMessages = [
                    ZipArchive::ER_OK => 'No error',
                    ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
                    ZipArchive::ER_RENAME => 'Renaming temporary file failed',
                    ZipArchive::ER_CLOSE => 'Closing zip archive failed',
                    ZipArchive::ER_SEEK => 'Seek error',
                    ZipArchive::ER_READ => 'Read error',
                    ZipArchive::ER_WRITE => 'Write error',
                    ZipArchive::ER_CRC => 'CRC error',
                    ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
                    ZipArchive::ER_NOENT => 'No such file',
                    ZipArchive::ER_EXISTS => 'File already exists',
                    ZipArchive::ER_OPEN => 'Can\'t open file',
                    ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
                    ZipArchive::ER_ZLIB => 'Zlib error',
                    ZipArchive::ER_MEMORY => 'Memory allocation failure',
                    ZipArchive::ER_CHANGED => 'Entry has been changed',
                    ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
                    ZipArchive::ER_EOF => 'Premature EOF',
                    ZipArchive::ER_INVAL => 'Invalid argument',
                    ZipArchive::ER_NOZIP => 'Not a zip archive',
                    ZipArchive::ER_INTERNAL => 'Internal error',
                    ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                    ZipArchive::ER_REMOVE => 'Can\'t remove file',
                    ZipArchive::ER_DELETED => 'Entry has been deleted'
                ];

                $errorMessage = $errorMessages[$result] ?? 'Unknown error';
                return [
                    'success' => false,
                    'message' => "Failed to open ZIP file: $errorMessage (Code: $result)"
                ];
            }

            $excludePluginName = explode('/', $pluginPath);
            array_pop($excludePluginName);
            $pluginFolder = implode('/', $excludePluginName);

            // Ensure extraction directory exists and is writable
            if (!is_dir($pluginFolder)) {
                if (!mkdir($pluginFolder, 0755, true)) {
                    $zip->close();
                    return [
                        'success' => false,
                        'message' => 'Cannot create plugin directory: ' . $pluginFolder
                    ];
                }
            }

            if (!is_writable($pluginFolder)) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Plugin directory is not writable: ' . $pluginFolder
                ];
            }

            // Extract the plugin
            if (!$zip->extractTo($pluginFolder)) {
                $zip->close();
                return [
                    'success' => false,
                    'message' => 'Failed to extract plugin files. Check directory permissions.'
                ];
            }

            $zip->close();

            return [
                'success' => true,
                'message' => 'Plugin extracted successfully to ' . $pluginFolder
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Extraction error: ' . $e->getMessage()
            ];
        }
    }
}