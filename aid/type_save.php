<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function go(string $url): void {
  header("Location: ".$url);
  exit;
}

$aid_name = trim($_POST["aid_name"] ?? "");
$beneficiary_category = trim($_POST["beneficiary_category"] ?? "");
$description = trim($_POST["description"] ?? "");
$is_active = isset($_POST["is_active"]) ? (int)$_POST["is_active"] : 1;

$allowed = ["Resident","Student","Senior","PWD"];
if ($aid_name === "" || !in_array($beneficiary_category, $allowed, true)) {
  go("index.php?error=" . urlencode("Invalid aid type data."));
}

$stmt = $conn->prepare("
  INSERT INTO aid_types (aid_name, beneficiary_category, description, is_active)
  VALUES (?, ?, ?, ?)
");
$stmt->bind_param("sssi", $aid_name, $beneficiary_category, $description, $is_active);

if ($stmt->execute()) {
  $insId = $conn->insert_id;
  write_activity_log($conn, "CREATE", "aid_types", $insId, "Created new aid type: " . $aid_name);
  $stmt->close();
  go("index.php?category=" . urlencode($beneficiary_category) . "&success=" . urlencode("Aid type created successfully."));
}

$err = $conn->error;
$stmt->close();
go("index.php?error=" . urlencode("Failed to create aid type. ".$err));