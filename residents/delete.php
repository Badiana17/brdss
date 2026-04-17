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

go_back("Resident record deleted successfully.", true);