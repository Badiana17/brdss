<?php
require_once "config/db.php";

$username = "superadmin";
$password = password_hash("tae123!", PASSWORD_DEFAULT);
$role = "super_admin";

$stmt = $conn->prepare("
  INSERT INTO users (username, password_hash, full_name, role)
  VALUES (?, ?, 'System Super Admin', ?)
");
$stmt->bind_param("sss", $username, $password, $role);
$stmt->execute();

echo "Super Admin created successfully.";