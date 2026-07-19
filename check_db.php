<?php
require_once "config/db.php";
$res = $conn->query("SHOW COLUMNS FROM aid_distribution");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo json_encode($cols);
