<?php

namespace App\Controllers;

use App\Core\Request;

/**
 * Authentication Controller
 * Handles login and logout functionality
 */
class AuthController
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        $error = $_GET['error'] ?? null;
        $success = isset($_GET['do_login']) ? 'You have been logged out. Please login again.' : null;

        require_once APP_PATH . '/Views/auth/login.php';
    }

    /**
     * Handle login submission
     */
    public function login()
    {
        $password = Request::getInstance()->post('password', '');

        if (password_verify($password, MONITOR_PASSWORD_HASH)) {
            $_SESSION['monitor_auth'] = true;
            redirect('/');
        } else {
            redirect('/login?error=Invalid+password');
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        session_destroy();
        unset($_SESSION['monitor_auth']);
        redirect('/login?do_login=1');
    }
}
