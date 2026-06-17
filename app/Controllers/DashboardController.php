<?php

namespace App\Controllers;

/**
 * Dashboard Controller
 * Handles the main dashboard view
 */
class DashboardController
{
    /**
     * Show dashboard
     */
    public function index()
    {
        require_once APP_PATH . '/Views/dashboard/index.php';
    }
}
