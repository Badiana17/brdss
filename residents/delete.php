<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function go_back($msg, $ok=false) {
  $key = $ok ? "success" : "error";
  header("Location: index.php?$key=" . urlencode($msg));
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") go_back("Invalid request.");

$csrf = $_POST["csrf_token"] ?? "";
if (!validate_csrf($csrf)) go_back("Invalid CSRF token.");

$resident_id = (int)($_POST["resident_id"] ?? 0);
if ($resident_id <= 0) go_back("Invalid resident id.");

$sql = "UPDATE residents
        SET deleted_at = NOW(),
            status = 'Inactive'
        WHERE resident_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) go_back("Prepare failed: " . $conn->error);

$stmt->bind_param("i", $resident_id);

if (!$stmt->execute()) {
  go_back("Delete failed: " . $stmt->error);
}
write_activity_log($conn, "DELETE", "residents", $resident_id, "Soft-deleted resident record for ID: " . $resident_id);

go_back("Resident record deleted successfully.", true);