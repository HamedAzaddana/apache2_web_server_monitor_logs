<?php
/**
 * Main Entry Point
 * All requests are routed through this file
 */

// Bootstrap the application
require_once __DIR__ . '/../bootstrap.php';

// Load routes
$router = require_once __DIR__ . '/../routes.php';

// Dispatch the request
$router->dispatch();
