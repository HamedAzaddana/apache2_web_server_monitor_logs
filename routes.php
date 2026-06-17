<?php
/**
 * Route Definitions
 * All application routes are registered here
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ApiController;

$router = new Router();

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// CSV export (protected)
$router->get('/api/export', [ApiController::class, 'exportCsv'])->middleware('auth');

// Dashboard routes (protected)
$router->get('/', [DashboardController::class, 'index'])->middleware('auth');

// API routes (protected)
$router->get('/api/charts', [ApiController::class, 'charts'])->middleware('auth');
$router->get('/api/table', [ApiController::class, 'table'])->middleware('auth');
$router->post('/api/truncate', [ApiController::class, 'truncate'])->middleware('auth');

return $router;
