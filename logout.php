<?php
session_start();
require_once 'config.php';

// Log logout activity if available
if (!empty($_SESSION['user_id']) && function_exists('logActivity')) {
    try {
        logActivity((int)$_SESSION['user_id'], 'User logged out', 'LOGOUT');
    } catch (Throwable $e) {
        error_log('Logout logActivity error: ' . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = [];

// Delete the session cookie if present
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>