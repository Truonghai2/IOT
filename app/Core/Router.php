<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $currentGroup = [];
    protected $currentPrefix = '';

    public function get($uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function delete($uri, $action)
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    public function group($attributes, $callback)
    {
        $this->currentGroup = $attributes;
        if (isset($attributes['prefix'])) {
            $this->currentPrefix = $attributes['prefix'];
        }
        
        $callback($this);
        
        $this->currentGroup = [];
        $this->currentPrefix = '';
    }

    protected function addRoute($method, $uri, $action)
    {
        $uri = $this->currentPrefix . $uri;
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch($method, $uri)
    {
        // Handle OPTIONS method for CORS
        if ($method === 'OPTIONS') {
            return ['status' => 'ok'];
        }

        if (!isset($this->routes[$method])) {
            throw new \Exception('Method not allowed: ' . $method);
        }

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            return $this->executeAction($this->routes[$method][$uri], []);
        }

        // Try pattern matching
        foreach ($this->routes[$method] as $route => $action) {
            $pattern = $this->convertRouteToRegex($route);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove the full match
                return $this->executeAction($action, $matches);
            }
        }

        throw new \Exception('Route not found: ' . $uri);
    }

    protected function executeAction($action, $params)
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }
        
        if (is_array($action)) {
            [$controller, $method] = $action;
            if (!class_exists($controller)) {
                throw new \Exception('Controller not found: ' . $controller);
            }
            $controller = new $controller();
            if (!method_exists($controller, $method)) {
                throw new \Exception('Method not found: ' . $method);
            }
            return call_user_func_array([$controller, $method], $params);
        }

        throw new \Exception('Invalid route action');
    }

    protected function convertRouteToRegex($route)
    {
        return '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $route) . '$#';
    }
} 