<?php

namespace App\Core;

/**
 * Simple HTTP Router
 */
class Router
{
    private $routes = [];
    private $middleware = [];
    private $currentRoutePrefix = '';

    /**
     * Register a GET route
     */
    public function get($uri, $handler)
    {
        $this->addRoute('GET', $uri, $handler);
        return $this;
    }

    /**
     * Register a POST route
     */
    public function post($uri, $handler)
    {
        $this->addRoute('POST', $uri, $handler);
        return $this;
    }

    /**
     * Add middleware to the current route
     */
    public function middleware($name)
    {
        $lastIndex = count($this->routes) - 1;
        if ($lastIndex >= 0) {
            $this->routes[$lastIndex]['middleware'][] = $name;
        }
        return $this;
    }

    /**
     * Add a route to the collection
     */
    private function addRoute($method, $uri, $handler)
    {
        // Ensure URI starts with /
        if (strpos($uri, '/') !== 0) {
            $uri = '/' . $uri;
        }

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => []
        ];
    }

    /**
     * Dispatch the current request
     */
    public function dispatch()
    {
        $request = Request::getInstance();
        $method = $request->method();
        $uri = $request->uri();

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->uriMatches($route['uri'], $uri)) {
                // Run middleware
                foreach ($route['middleware'] as $middlewareName) {
                    $middleware = $this->resolveMiddleware($middlewareName);
                    if ($middleware !== null) {
                        $middleware->handle();
                    }
                }

                // Execute handler
                return $this->executeHandler($route['handler']);
            }
        }

        // No route found - return 404
        $this->abort(404);
    }

    /**
     * Check if URI pattern matches request URI
     */
    private function uriMatches($pattern, $uri)
    {
        // Convert pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        return preg_match($pattern, $uri);
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler)
    {
        if (is_array($handler)) {
            // [Controller::class, 'method']
            [$controller, $method] = $handler;

            if (!class_exists($controller)) {
                throw new \Exception("Controller not found: $controller");
            }

            $instance = new $controller();

            if (!method_exists($instance, $method)) {
                throw new \Exception("Method not found: $controller::$method");
            }

            return call_user_func([$instance, $method]);
        }

        if (is_callable($handler)) {
            return call_user_func($handler);
        }

        throw new \Exception('Invalid route handler');
    }

    /**
     * Resolve middleware instance
     */
    private function resolveMiddleware($name)
    {
        $middlewareMap = [
            'auth' => 'App\\Middleware\\AuthMiddleware',
        ];

        $class = $middlewareMap[$name] ?? null;

        if ($class && class_exists($class)) {
            return new $class();
        }

        return null;
    }

    /**
     * Abort with HTTP status code
     */
    private function abort($code, $message = '')
    {
        http_response_code($code);

        if ($code === 404) {
            echo '<h1>404 - Page Not Found</h1>';
        } elseif ($code === 403) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message ?: 'Unauthorized']);
        } else {
            echo "<h1>$code - Error</h1>";
            if ($message) {
                echo "<p>$message</p>";
            }
        }
        exit;
    }
}
