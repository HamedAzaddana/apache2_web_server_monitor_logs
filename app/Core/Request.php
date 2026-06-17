<?php

namespace App\Core;

/**
 * HTTP Request Handler
 */
class Request
{
    private static $instance = null;
    private $method;
    private $uri;
    private $queryParams;
    private $postParams;
    private $files;

    private function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->files = $_FILES;

        // Get the request URI without query string
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove base path if the app is in a subdirectory
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $this->uri = $uri;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function method()
    {
        return $this->method;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function get($key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post($key, $default = null)
    {
        return $this->postParams[$key] ?? $default;
    }

    public function all()
    {
        return array_merge($this->queryParams, $this->postParams);
    }

    public function has($key)
    {
        return isset($this->queryParams[$key]) || isset($this->postParams[$key]);
    }

    public function expectsJson()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false;
    }

    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
