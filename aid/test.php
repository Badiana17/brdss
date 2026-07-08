<?php
require_once "../config/db.php";
$stmt = $conn->prepare("SELECT resident_id, first_name, last_name, beneficiary_category, status, deleted_at FROM residents ORDER BY resident_id DESC LIMIT 5");
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}
file_put_contents("C:/Users/John Lyod Badiana/.gemini/antigravity-ide/brain/c3725e1c-691a-4575-8f82-4b237d518de9/scratch/db_out.json", json_encode($out, JSON_PRETTY_PRINT));
