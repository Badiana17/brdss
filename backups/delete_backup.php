<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['backup_error'] = "Invalid backup ID.";
    header("Location: index.php");
    exit;
}

$backupId = (int) $_GET['id'];

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