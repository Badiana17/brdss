<?php
require_once "../config/db.php";
$res = $conn->query("SELECT * FROM backup_history");
if (!$res) {
    echo json_encode(["error" => $conn->error]);
} else {
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
