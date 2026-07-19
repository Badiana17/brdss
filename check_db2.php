<?php
require_once "config/db.php";
$res = $conn->query("SELECT * FROM aid_distribution WHERE YEAR(distributed_at) = 2025 OR YEAR(created_at) = 2025");
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
