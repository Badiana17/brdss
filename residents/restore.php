<?php
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

function go_back(string $msg, bool $ok = false): void {
    $key = $ok ? "success" : "error";
    header("Location: index.php?" . $key . "=" . urlencode($msg));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") go_back("Invalid request.");

$csrf = $_POST["csrf_token"] ?? "";
if (!validate_csrf($csrf)) go_back("Invalid CSRF token.");

$resident_id = (int)($_POST["resident_id"] ?? 0);
if ($resident_id <= 0) go_back("Invalid resident ID.");

$stmt = $conn->prepare("UPDATE residents SET deleted_at = NULL, status = 'Active' WHERE resident_id = ? AND deleted_at IS NOT NULL");
if (!$stmt) go_back("Prepare failed: " . $conn->error);

$stmt->bind_param("i", $resident_id);

if (!$stmt->execute() || $stmt->affected_rows < 1) {
    $stmt->close();
    go_back("Restore failed. The resident may not exist in the archive.");
}

$stmt->close();
write_activity_log($conn, "RESTORE", "residents", $resident_id, "Restored archived resident record for ID: " . $resident_id);
go_back("Resident record restored successfully.", true);
