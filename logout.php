<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'User logged out', 'LOGOUT');
}

session_unset();
session_destroy();
header('Location: login.php');
exit();
?>