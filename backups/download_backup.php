<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid backup ID.");
}

$backupId = (int) $_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM backup_history WHERE backup_id = ?");
mysqli_stmt_bind_param($stmt, "i", $backupId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$backup = mysqli_fetch_assoc($result);

if (!$backup) {
    die("Backup record not found.");
}

$filePath = __DIR__ . "/files/" . $backup['file_name'];

if (!file_exists($filePath)) {
    die("Backup file not found.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: public');
header('Expires: 0');
readfile($filePath);
exit;