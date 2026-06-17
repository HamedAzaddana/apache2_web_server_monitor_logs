<?php
/**
 * Application Bootstrap
 * Loads configuration and initializes the application
 */

// Define base paths (bootstrap.php is at project root, so __DIR__ is BASE_PATH)
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('CONFIG_PATH', BASE_PATH . '/config.php');

// Determine base URL for subdirectory installations
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(dirname($scriptName), '/\\');
define('BASE_URL', $baseUrl !== '' ? $baseUrl : '');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Fallback autoloader for development
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = APP_PATH . '/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// Load configuration
if (file_exists(CONFIG_PATH)) {
    require_once CONFIG_PATH;
} else {
    die('Configuration file not found. Please copy config.sample.php to config.php');
}

// Load app configuration
require_once APP_PATH . '/config/app.php';

// Load helper functions
require_once APP_PATH . '/helpers.php';
