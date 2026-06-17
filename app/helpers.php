<?php
/**
 * Global Helper Functions
 */

use App\Core\Request;

/**
 * Get the current request instance
 */
function request()
{
    return Request::getInstance();
}

/**
 * Redirect to a URL
 * Automatically prepends BASE_URL for relative paths
 */
function redirect($url, $statusCode = 302)
{
    // If URL starts with /, prepend BASE_URL for subdirectory installs
    if (strpos($url, '/') === 0 && BASE_URL !== '') {
        $url = BASE_URL . $url;
    }
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Return JSON response
 */
function json($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get an old input value (for form repopulation)
 */
function old($key, $default = '')
{
    return $_POST[$key] ?? $default;
}

/**
 * Escape HTML output
 */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get config value
 */
function config($key)
{
    static $config = [];

    if (empty($config)) {
        $config = [
            'db.path' => DB_PATH ?? BASE_PATH . '/cache/logs.db',
            'app.title' => APP_TITLE ?? 'Monitor',
        ];
    }

    return $config[$key] ?? null;
}

/**
 * Check if user is authenticated
 */
function auth()
{
    return $_SESSION['monitor_auth'] ?? false;
}

/**
 * Get authenticated user (returns true/false for this simple auth)
 */
function user()
{
    return auth();
}

/**
 * Generate a URL for the application
 * Handles subdirectory installations
 */
function url($path = '')
{
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Get the current URL with query parameters
 */
function currentUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}
