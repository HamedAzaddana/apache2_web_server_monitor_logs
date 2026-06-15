<?php
/**
 * DrSerita Monitor - Main Entry Point
 * Handles authentication and routing
 */

session_start();
define('ACCESS_ALLOWED', true);
require_once dirname(__DIR__) . '/config.php';

// --- Handle Logout ---
if (isset($_GET['do_logout'])) {
    session_destroy();
    unset($_SESSION['monitor_auth']);
    header('Location: ?do_login=1');
    exit;
}

// --- Handle Login ---
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (password_verify($password, MONITOR_PASSWORD_HASH)) {
        $_SESSION['monitor_auth'] = true;
        header('Location: ?');
        exit;
    } else {
        $error = 'Invalid password';
    }
}

// --- Determine View ---
$isLoggedIn = isset($_SESSION['monitor_auth']) && $_SESSION['monitor_auth'] === true;
$showLogin = isset($_GET['do_login']) || !$isLoggedIn;

if ($showLogin) {
    // Check if user was just logged out
    if (isset($_GET['do_login'])) {
        $success = 'You have been logged out. Please login again.';
    }
    require __DIR__ . '/views/login.php';
} else {
    // Show dashboard
    require __DIR__ . '/views/dashboard.php';
}
