<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function go_back(string $msg, bool $ok=false): void {
  $key = $ok ? "success" : "error";
  header("Location: index.php?$key=" . urlencode($msg));
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  go_back("Invalid request.");
}

// collect + sanitize
$last_name  = trim($_POST["last_name"] ?? "");
$first_name = trim($_POST["first_name"] ?? "");
$middle_name= trim($_POST["middle_name"] ?? "");
$suffix     = trim($_POST["suffix"] ?? "");
$birthday   = trim($_POST["birthday"] ?? "");
$address    = trim($_POST["address"] ?? "");
$barangay   = trim($_POST["barangay"] ?? "");
$zone       = trim($_POST["zone"] ?? "");
$contact_no = trim($_POST["contact_no"] ?? "");
$gender     = trim($_POST["gender"] ?? "");
$civil_status = trim($_POST["civil_status"] ?? "");
$is_voter   = $_POST["is_voter"] ?? "";
$status     = trim($_POST["status"] ?? "");
$beneficiary_category = trim($_POST["beneficiary_category"] ?? "None");

// basic required validation
if (
  $last_name==="" || $first_name==="" || $middle_name==="" || $birthday==="" ||
  $address==="" || $barangay==="" || $zone==="" || $contact_no==="" ||
  $gender==="" || $civil_status==="" || $is_voter==="" || $status===""
) {
  go_back("Please complete all required fields (*).");
}

// compute age server-side (source of truth)
$computedAge = 0;
try {
  $b = new DateTime($birthday);
  $t = new DateTime();
  $computedAge = (int)$t->diff($b)->y;
} catch (Exception $e) {
  go_back("Invalid birth date.");
}

if ($computedAge < 0 || $computedAge > 130) {
  go_back("Birth date/age seems invalid.");
}

// normalize beneficiary_category
$allowedCats = ["Senior","PWD","Student","None"];
if (!in_array($beneficiary_category, $allowedCats, true)) {
  $beneficiary_category = "None";
}

// normalize is_voter
$is_voter = ($is_voter === "1" || $is_voter === 1) ? 1 : 0;

// ✅ FIX: 15 placeholders only (created_at uses NOW())
$sql = "INSERT INTO residents
  (first_name, middle_name, last_name, suffix, birthday, age, gender, civil_status, is_voter,
   address, barangay, zone, contact_no, beneficiary_category, status)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  go_back("Prepare failed: " . $conn->error);
}

$types = "sssssississssss"; // ✅ 15 letters

$stmt->bind_param(
  $types,
  $first_name,
  $middle_name,
  $last_name,
  $suffix,
  $birthday,
  $computedAge,
  $gender,
  $civil_status,
  $is_voter,
  $address,
  $barangay,
  $zone,
  $contact_no,
  $beneficiary_category,
  $status
);

if (!$stmt->execute()) {
  go_back("Insert failed: " . $stmt->error);
}
$insId = $conn->insert_id;
write_activity_log($conn, "CREATE", "residents", $insId, "Added new resident: " . $first_name . " " . $last_name);

go_back("Resident saved successfully!", true);