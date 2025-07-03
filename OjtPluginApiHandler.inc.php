<?php

use Slim\Http\Response;

import('classes.handler.Handler');
import('plugins.generic.ojtPlugin.helpers.OJTHelper');
import('lib.pkp.classes.plugins.Plugin');

class OjtPluginApiHandler extends Handler
{
    public function __construct()
    {
        // parent::__construct();
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
                // Article routes
                'articles' => [$this, 'articles'],
                'article/{articleId}' => [$this, 'article'],

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

        $response = [
            'status_code' => 200,
            'update_success' => true,
            'product_version' => '1.0.0',
            'ojs_version' => '33'
        ];

        header('Content-Type: application/json');
        http_response_code($response['status_code']);
        return json_encode($response);
    }

    public function journal($args, $request)
    {
        return new JSONMessage(true, [
            'message' => 'Journal endpoint',
            'args' => $args,
            'data' => 'Journal data would go here'
        ]);
    }

    public function issues($args, $request)
    {
        return new JSONMessage(true, [
            'message' => 'Issues endpoint',
            'args' => $args,
            'data' => 'Issues list would go here'
        ]);
    }

    public function issue($args, $request)
    {
        $issueId = $args['issueId'] ?? null;
        
        if (!$issueId) {
            return new JSONMessage(false, 'Issue ID is required');
        }

        return new JSONMessage(true, [
            'message' => 'Single issue endpoint',
            'issueId' => $issueId,
            'args' => $args,
            'data' => "Issue $issueId data would go here"
        ]);
    }

    public function articles($args, $request)
    {
        return new JSONMessage(true, [
            'message' => 'Articles endpoint',
            'args' => $args,
            'data' => 'Articles list would go here'
        ]);
    }

    public function article($args, $request)
    {
        $articleId = $args['articleId'] ?? null;
        
        if (!$articleId) {
            return new JSONMessage(false, 'Article ID is required');
        }

        return new JSONMessage(true, [
            'message' => 'Single article endpoint',
            'articleId' => $articleId,
            'args' => $args,
            'data' => "Article $articleId data would go here"
        ]);
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
}

?>