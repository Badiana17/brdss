<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function go($url){
  header("Location: ".$url);
  exit;
}

/**
 * category (UI) -> beneficiary_type (DB enum)
 * DB enum: Resident | Student | Senior | PWD
 */
function mapCategoryToBeneficiaryType(string $category): string {
  $category = trim($category);

  if ($category === "Student") return "Student";
  if ($category === "Senior")  return "Senior";
  if ($category === "PWD")     return "PWD";

  // For "Resident" and "All" (or anything else)
  return "Resident";
}

$aid_type_id = isset($_POST["aid_type_id"]) ? (int)$_POST["aid_type_id"] : 0;
$category    = trim($_POST["category"] ?? "Resident");   // coming from index.php
$remarks     = trim($_POST["remarks"] ?? "");
$ids         = $_POST["beneficiary_ids"] ?? [];

if ($aid_type_id <= 0) {
  go("index.php?category=" . urlencode($category) . "&error=" . urlencode("Invalid aid selected."));
}

$beneficiary_type = mapCategoryToBeneficiaryType($category);
$allowedTypes = ["Resident","Student","Senior","PWD"];
if (!in_array($beneficiary_type, $allowedTypes, true)) {
  go("index.php?category=" . urlencode($category) . "&aid_id=" . $aid_type_id . "&error=" . urlencode("Invalid request."));
}

if (!is_array($ids) || count($ids) === 0) {
  go("index.php?category=" . urlencode($category) . "&aid_id=" . $aid_type_id . "&error=" . urlencode("No beneficiary selected."));
}

/* Insert one history row per selected beneficiary */
$stmt = $conn->prepare("
  INSERT INTO aid_distribution
    (aid_type_id, beneficiary_type, beneficiary_id, status, remarks)
  VALUES
    (?, ?, ?, 'Received', ?)
");

$ok = 0;
foreach ($ids as $raw) {
  $bid = (int)$raw;
  if ($bid <= 0) continue;

  $stmt->bind_param("isis", $aid_type_id, $beneficiary_type, $bid, $remarks);
  if ($stmt->execute()) $ok++;
}
$stmt->close();

go("index.php?category=" . urlencode($category) . "&aid_id=" . $aid_type_id . "&success=" . urlencode("Saved {$ok} distribution record(s)."));