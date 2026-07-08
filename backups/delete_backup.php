<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";
require_once "../config/auth.php";
require_once "../includes/role_guard.php";
requireRole(["super_admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['backup_error'] = "Invalid request method.";
    header("Location: index.php");
    exit;
}

$csrf = $_POST["csrf_token"] ?? "";
if (!validate_csrf($csrf)) {
    $_SESSION['backup_error'] = "Invalid CSRF token.";
    header("Location: index.php");
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['backup_error'] = "Invalid backup ID.";
    header("Location: index.php");
    exit;
}

$backupId = (int) $_POST['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM backup_history WHERE backup_id = ?");
mysqli_stmt_bind_param($stmt, "i", $backupId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$backup = mysqli_fetch_assoc($result);

if (!$backup) {
    $_SESSION['backup_error'] = "Backup record not found.";
    header("Location: index.php");
    exit;
}

$filePath = __DIR__ . "/files/" . $backup['file_name'];

if (file_exists($filePath)) {
    unlink($filePath);
}

$deleteStmt = mysqli_prepare($conn, "DELETE FROM backup_history WHERE backup_id = ?");
mysqli_stmt_bind_param($deleteStmt, "i", $backupId);
mysqli_stmt_execute($deleteStmt);

$_SESSION['backup_success'] = "Backup deleted successfully.";
header("Location: index.php");
exit;