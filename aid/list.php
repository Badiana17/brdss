<?php
/**
 * aid/list.php — JSON endpoint for filtered distribution records.
 * Returns paginated records with joins to aid_types + residents.
 *
 * GET params: aid_type_id, beneficiary_type, status, date_from, date_to, q, page
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

header("Content-Type: application/json; charset=utf-8");

/* --- Parse filters --- */
$aid_type_id     = isset($_GET["aid_type_id"]) ? (int)$_GET["aid_type_id"] : 0;
$beneficiary_type = trim($_GET["beneficiary_type"] ?? "");
$status          = trim($_GET["status"] ?? "");
$date_from       = trim($_GET["date_from"] ?? "");
$date_to         = trim($_GET["date_to"] ?? "");
$keyword         = trim($_GET["q"] ?? "");
$page            = max(1, (int)($_GET["page"] ?? 1));
$perPage         = 50;
$offset          = ($page - 1) * $perPage;

/* --- Build WHERE conditions --- */
$where  = ["1=1"];
$params = [];
$types  = "";

if ($aid_type_id > 0) {
    $where[]  = "d.aid_type_id = ?";
    $params[] = $aid_type_id;
    $types   .= "i";
}

$allowedBeneficiary = ["Resident", "Student", "Senior", "PWD"];
if ($beneficiary_type !== "" && in_array($beneficiary_type, $allowedBeneficiary, true)) {
    $where[]  = "d.beneficiary_type = ?";
    $params[] = $beneficiary_type;
    $types   .= "s";
}

$allowedStatus = ["Pending", "Received", "Cancelled"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
    $where[]  = "d.status = ?";
    $params[] = $status;
    $types   .= "s";
}

if ($date_from !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where[]  = "d.distributed_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types   .= "s";
}

if ($date_to !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where[]  = "d.distributed_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types   .= "s";
}

if ($keyword !== "") {
    $where[]  = "(r.last_name LIKE ? OR r.first_name LIKE ? OR r.middle_name LIKE ? OR at.aid_name LIKE ? OR d.remarks LIKE ?)";
    $like     = "%" . $keyword . "%";
    array_push($params, $like, $like, $like, $like, $like);
    $types   .= "sssss";
}

$whereSql = implode(" AND ", $where);

/* --- Count total --- */
$countSql = "
    SELECT COUNT(*) AS total
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    WHERE $whereSql
";
$stmt = $conn->prepare($countSql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

/* --- Fetch records --- */
$sql = "
    SELECT
        d.id,
        d.aid_type_id,
        at.aid_name,
        d.beneficiary_type,
        d.beneficiary_id,
        CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) AS beneficiary_name,
        r.address,
        r.barangay,
        d.status,
        d.remarks,
        d.distributed_at,
        d.is_locked,
        d.locked_by,
        d.locked_at,
        d.finalized_at,
        lu.full_name AS locked_by_name
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    LEFT JOIN users lu ON lu.user_id = d.locked_by
    WHERE $whereSql
    ORDER BY d.distributed_at DESC, d.id DESC
    LIMIT ? OFFSET ?
";
$fetchTypes  = $types . "ii";
$fetchParams = array_merge($params, [$perPage, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($fetchTypes, ...$fetchParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --- Response --- */
echo json_encode([
    "success" => true,
    "data"    => $rows,
    "total"   => $total,
    "page"    => $page,
    "perPage" => $perPage,
    "pages"   => max(1, ceil($total / $perPage)),
], JSON_UNESCAPED_UNICODE);
