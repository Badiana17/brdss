<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brdss_db');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Check if user has required role
function checkRole($requiredRole) {
    checkLogin();
    if ($_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'Super Admin') {
        die('Access denied. Insufficient permissions.');
    }
}

// Log activity function
function logActivity($userId, $activity, $actionType = null, $tableAffected = null, $recordId = null) {
    $conn = getDBConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity, action_type, table_affected, record_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $userId, $activity, $actionType, $tableAffected, $recordId, $ipAddress);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>