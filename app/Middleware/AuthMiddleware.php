<?php

namespace App\Middleware;

use App\Core\Request;

/**
 * Authentication Middleware
 * Checks if user is authenticated before allowing access
 */
class AuthMiddleware
{
    /**
     * Handle the incoming request
     */
    public function handle()
    {
        if (!isset($_SESSION['monitor_auth']) || $_SESSION['monitor_auth'] !== true) {
            $request = Request::getInstance();

            if ($request->expectsJson() || $request->isAjax() || strpos($request->uri(), '/api/') === 0) {
                // API request - return JSON error
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            // Web request - redirect to login
            redirect('/login?do_login=1');
        }
    }
}
