<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brdss_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Return a mysqli connection or null on failure.
 * Some legacy pages use mysqli, others use PDO ($pdo).
 */
function getDBConnection() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_errno) {
        error_log('MySQLi connection error: ' . $conn->connect_error);
        return null;
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

/**
 * Create a PDO instance for code that expects $pdo.
 * Available as global $pdo after this file is included.
 */
$pdo = null;
try {
    // Try with unix socket first (for LAMPP)
    $dsn = 'mysql:unix_socket=/opt/lampp/var/mysql/mysql.sock;dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e1) {
        // Fallback to host:port connection
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
} catch (Throwable $e) {
    // Log but do not stop execution; fallback to mysqli where used.
    error_log('PDO connection failed: ' . $e->getMessage());
    $pdo = null;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in. Redirects to login if not.
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Check user role. Allows Super Admin to bypass specific role.
 */
function checkRole($requiredRole) {
    checkLogin();
    $role = $_SESSION['role'] ?? '';
    if ($role !== $requiredRole && $role !== 'Super Admin') {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Log activity into activity_log.
 * Uses PDO if available, otherwise falls back to mysqli.
 *
 * Signature: logActivity($userId, $activity, $actionType, $tableAffected, $recordId)
 * Example: logActivity($_SESSION['user_id'], 'Created resident', 'CREATE', 'residents', $newId);
 */
function logActivity($userId, $activity, $actionType = null, $tableAffected = null, $recordId = null) {
    global $pdo;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    // Use PDO if available
    if ($pdo instanceof PDO) {
        try {
            $sql = "INSERT INTO activity_log (user_id, activity, action_type, table_affected, record_id, ip_address)
                    VALUES (:user_id, :activity, :action_type, :table_affected, :record_id, :ip_address)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
            $stmt->bindValue(':activity', (string)$activity, PDO::PARAM_STR);
            $stmt->bindValue(':action_type', $actionType !== null ? (string)$actionType : null, PDO::PARAM_STR);
            $stmt->bindValue(':table_affected', $tableAffected !== null ? (string)$tableAffected : null, PDO::PARAM_STR);
            $stmt->bindValue(':record_id', $recordId !== null ? (int)$recordId : null, PDO::PARAM_INT);
            $stmt->bindValue(':ip_address', $ipAddress !== null ? (string)$ipAddress : null, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('logActivity (PDO) error: ' . $e->getMessage());
        }
        return;
    }

    // Fallback to mysqli
    $conn = getDBConnection();
    if (!$conn) {
        error_log('logActivity: no DB connection available.');
        return;
    }

    $u = (int)$userId;
    $act = $conn->real_escape_string((string)$activity);
    $atype = $actionType !== null ? "'" . $conn->real_escape_string((string)$actionType) . "'" : "NULL";
    $tbl = $tableAffected !== null ? "'" . $conn->real_escape_string((string)$tableAffected) . "'" : "NULL";
    $rid = $recordId !== null ? (int)$recordId : "NULL";
    $ip = $ipAddress !== null ? "'" . $conn->real_escape_string((string)$ipAddress) . "'" : "NULL";

    $sql = "INSERT INTO activity_log (user_id, activity, action_type, table_affected, record_id, ip_address)
            VALUES ($u, '$act', $atype, $tbl, $rid, $ip)";

    if (!$conn->query($sql)) {
        error_log('logActivity (mysqli) error: ' . $conn->error);
    }

    $conn->close();
}

/**
 * Sanitize helper
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

// Set default page title if not already set by the page
if (!isset($pageTitle)) {
    $pageTitle = 'BRDSS - Barangay Resident Data & Social Services';
}
?>