<?php

use Slim\Http\Response;
use Openjournalteam\OjtPlugin\Actions\ApiUpdatePlugin;

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
                'check-update-plugin/{pluginClass}' => [ApiUpdatePlugin::class, 'handle'],
            ],
        ];
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