<?php
require_once "config/db.php";

$yearFilterSql = ""; // "All Years" selected
$sqlPie = "
  SELECT r.beneficiary_category AS cat,
         COUNT(DISTINCT a.beneficiary_id) AS total
  FROM aid_distribution a
  INNER JOIN residents r ON r.resident_id = a.beneficiary_id
  WHERE a.status = 'Received'
    AND r.deleted_at IS NULL
    $yearFilterSql
  GROUP BY r.beneficiary_category
";
$qPie = $conn->query($sqlPie);
$studentsCount = 0; $seniorCount = 0; $pwdCount = 0; $residentCount = 0;
if ($qPie) {
  while ($row = $qPie->fetch_assoc()) {
    $cat = $row["cat"] ?? "";
    $val = (int)($row["total"] ?? 0);
    if ($cat === "Student") $studentsCount = $val;
    elseif ($cat === "Senior") $seniorCount = $val;
    elseif ($cat === "PWD") $pwdCount = $val;
    elseif ($cat === "None") $residentCount = $val;
  }
}
echo json_encode([$studentsCount, $seniorCount, $pwdCount, $residentCount]);
