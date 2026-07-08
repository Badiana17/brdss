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

$last_name   = trim($_POST["last_name"] ?? "");
$first_name  = trim($_POST["first_name"] ?? "");
$middle_name = trim($_POST["middle_name"] ?? "");
$suffix      = trim($_POST["suffix"] ?? "");
$birthday    = trim($_POST["birthday"] ?? "");
$age         = (int)($_POST["age"] ?? 0);

$address     = trim($_POST["address"] ?? "");
$barangay    = trim($_POST["barangay"] ?? "");
$zone        = trim($_POST["zone"] ?? "");
$contact_no  = trim($_POST["contact_no"] ?? "");

$gender       = trim($_POST["gender"] ?? "");
$civil_status = trim($_POST["civil_status"] ?? "");
$is_voter     = (int)($_POST["is_voter"] ?? -1);
$status       = trim($_POST["status"] ?? "");

$beneficiary_category = trim($_POST["beneficiary_category"] ?? "None");
$allowedCats = ["Senior","PWD","Student","None"];
if (!in_array($beneficiary_category, $allowedCats, true)) {
  $beneficiary_category = "None";
}

if (
  $last_name==="" || $first_name==="" || $middle_name==="" ||
  $birthday==="" || $address==="" || $barangay==="" || $zone==="" ||
  $contact_no==="" || $gender==="" || $civil_status==="" ||
  ($is_voter !== 0 && $is_voter !== 1) || $status===""
) {
  go_back("Please complete all required fields.");
}

$sql = "UPDATE residents SET
          first_name = ?,
          middle_name = ?,
          last_name = ?,
          suffix = ?,
          birthday = ?,
          age = ?,
          gender = ?,
          civil_status = ?,
          is_voter = ?,
          address = ?,
          barangay = ?,
          zone = ?,
          contact_no = ?,
          beneficiary_category = ?,
          status = ?
        WHERE resident_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) go_back("Prepare failed: " . $conn->error);

/* 16 variables -> 16 types (IMPORTANT) */
$types = "sssssississssssi";

$stmt->bind_param(
  $types,
  $first_name,
  $middle_name,
  $last_name,
  $suffix,
  $birthday,
  $age,
  $gender,
  $civil_status,
  $is_voter,
  $address,
  $barangay,
  $zone,
  $contact_no,
  $beneficiary_category,
  $status,
  $resident_id
);

if (!$stmt->execute()) {
  go_back("Update failed: " . $stmt->error);
}
write_activity_log($conn, "UPDATE", "residents", $resident_id, "Updated resident record for ID: " . $resident_id);

go_back("Resident updated successfully.", true);