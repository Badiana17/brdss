<?php
/**
 * Index/Landing Page - BRDSS
 * Redirects authenticated users to dashboard, others to login
 */
session_start();

// If user is already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Otherwise, redirect to login
header('Location: login.php');
exit();