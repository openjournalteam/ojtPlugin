<?php

use Slim\Http\Response;

import('classes.handler.Handler');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.plugins.Plugin');

class OjtPluginApiHandler extends Handler
{
    public OjtPlugin $ojtPlugin;

    public function __construct()
    {
        // parent::__construct();
        $this->ojtPlugin = new OjtPlugin();
    }

    public function api($args, $request)
    {
        return $this->route('api', $args, $request);
    }

    public function route($version, $args, $request)
    {
        $routes = $this->getRoutes();

        if (!isset($routes[$version])) {
            return new JSONMessage(false, "Unknown API version: $version");
        }

        $path = implode('/', $args);
        $matchedRoute = null;
        $routeParams = [];

        foreach ($routes[$version] as $routePattern => $handler) {
            // Convert route pattern to regex
            $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $routePattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $path, $matches)) {
                $matchedRoute = $handler;

                // Extract named params from matches
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $routeParams[$key] = $value;
                    }
                }
                break;
            }
        }

        if ($matchedRoute === null) {
            return new JSONMessage(false, "Route not found: $path");
        }

        return $this->handleRoute($matchedRoute, $routeParams, $request);
    }

    protected function getRoutes()
    {
        return [
            'api' => [
                // check update plugin
                'check-update-plugin/{pluginClass}' => [$this, 'checkUpdatePlugin'],
            ],
        ];
    }

    public function checkUpdatePlugin($args, $request)
    {
        if(!$request->isPost()) {
            http_response_code(405); // Method Not Allowed
            return new JSONMessage(false, 'This endpoint only accepts POST requests.');
        }

        $getBearerToken = $this->getAuthorizationHeader();
        
        if (empty($getBearerToken)) {
            http_response_code(401); // Unauthorized
            return new JSONMessage(false, 'Authorization header is missing or empty.');
        }

        if (!str_contains($getBearerToken, 'Bearer ')) {
            http_response_code(401); // Unauthorized
            return new JSONMessage(false, 'Invalid authorization format. Expected "Bearer {token}".');
        }

        $getBearerToken = str_replace('Bearer ', '', $getBearerToken);

        $pluginClass = $args['pluginClass'] ?? null;
        $getAllPlugins = PluginRegistry::getAllPlugins();
        if(!isset($getAllPlugins[$pluginClass])) {
            http_response_code(404); // Not Found
            return new JSONMessage(false, 'Plugin class not found: ' . $pluginClass);
        }

        $data = null;
        if (!empty($request->getUserVars())) {
            $data = $request->getUserVars();
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
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

        $dataPlugin = [
            'class' => $pluginClass,
            'category' => $getAllPlugins[$pluginClass]->getCategory(),
            'path' => $getAllPlugins[$pluginClass]->getPluginPath(),
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

        } catch (Exception $e) {
            error_log('Plugin update failed: ' . $e->getMessage());
            http_response_code(500);
            return new JSONMessage(false, 'Plugin update failed: ' . $e->getMessage());
        }
    }

    protected function handleRoute($handler, $routeParams, $request)
    {
        if (is_array($handler) && count($handler) === 2) {
            $controller = $handler[0];
            $method = $handler[1];
            
            // if not using controller, the method is available in this class
            if ($controller === $this) {
                if (method_exists($this, $method)) {
                    return $this->$method($routeParams, $request);
                } else {
                    return new JSONMessage(false, "Method '$method' not found in handler");
                }
            }
            
            // if using controller
            if (is_string($controller) && class_exists($controller)) {
                $controllerInstance = new $controller();
                if (method_exists($controllerInstance, $method)) {
                    return $controllerInstance->$method($routeParams, $request);
                } else {
                    return new JSONMessage(false, "Method '$method' not found in controller '$controller'");
                }
            }
        }
        
        return new JSONMessage(false, "Invalid route handler configuration");
    }

    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
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
        $filesDir = Config::getVar('files', 'files_dir');
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

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

?>