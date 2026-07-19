<?php
require_once "config/db.php";
$sqlPie = "
  SELECT r.beneficiary_category AS cat,
         COUNT(DISTINCT a.beneficiary_id) AS total
  FROM aid_distribution a
  INNER JOIN residents r ON r.resident_id = a.beneficiary_id
  WHERE a.status = 'Received'
    AND r.deleted_at IS NULL
  GROUP BY r.beneficiary_category
";
$res = $conn->query($sqlPie);
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
