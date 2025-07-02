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

        $pluginClass = $args['pluginClass'];

        if (!$pluginClass) {
            return new JSONMessage(false, 'Plugin class is required');
        }

        $response = [
            "status" => "error",
            "error_code" => 404, // kode error, misal: 404 (not found)
            "message" => "Data tidak ditemukan",
            "data" => null
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

        return new JSONMessage(true, [
            'message' => 'API is working',
            'args' => $args,
            'request' => $request->getUserVars() ?? 'No test variable provided',
        ]);
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

}

?>