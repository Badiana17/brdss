<?php
/**
 * aid/export_csv.php — Export distribution records as CSV.
 *
 * Accepts same filter params as the records tab.
 * Streams CSV with proper headers.
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username = $_SESSION["username"] ?? "User";

/* --- Parse filters --- */
$aid_type_id      = isset($_GET["aid_type_id"]) ? (int)$_GET["aid_type_id"] : 0;
$beneficiary_type = trim($_GET["beneficiary_type"] ?? "");
$status           = trim($_GET["status"] ?? "");
$date_from        = trim($_GET["date_from"] ?? "");
$date_to          = trim($_GET["date_to"] ?? "");
$keyword          = trim($_GET["q"] ?? "");

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
    $where[]  = "(r.last_name LIKE ? OR r.first_name LIKE ? OR at.aid_name LIKE ? OR d.remarks LIKE ?)";
    $like     = "%" . $keyword . "%";
    array_push($params, $like, $like, $like, $like);
    $types   .= "ssss";
}

$whereSql = implode(" AND ", $where);

$sql = "
    SELECT d.id, at.aid_name, d.beneficiary_type, d.beneficiary_id,
           CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name,'')) AS beneficiary_name,
           r.address, r.barangay, r.zone, r.contact_no,
           d.status, d.remarks, d.distributed_at,
           d.is_locked, d.locked_at,
           lu.full_name AS locked_by_name
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    LEFT JOIN users lu ON lu.user_id = d.locked_by
    WHERE $whereSql
    ORDER BY d.distributed_at DESC, d.id DESC
    LIMIT 5000
";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --- Log export --- */
write_activity_log($conn, "EXPORT", "aid_distribution", null, "Exported " . count($rows) . " aid distribution records as CSV.");

/* --- Stream CSV --- */
$filename = "aid_distribution_" . date("Y-m-d_H-i-s") . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

/* BOM for Excel UTF-8 */
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* Header row */
fputcsv($output, [
    "ID",
    "Beneficiary Name",
    "Beneficiary Type",
    "Aid Program",
    "Address",
    "Barangay",
    "Zone",
    "Contact No.",
    "Status",
    "Remarks",
    "Distributed At",
    "Locked",
    "Locked At",
    "Locked By",
]);

foreach ($rows as $r) {
    fputcsv($output, [
        $r["id"],
        $r["beneficiary_name"],
        $r["beneficiary_type"],
        $r["aid_name"],
        $r["address"] ?? "",
        $r["barangay"] ?? "",
        $r["zone"] ?? "",
        $r["contact_no"] ?? "",
        $r["status"],
        $r["remarks"] ?? "",
        $r["distributed_at"],
        (int)$r["is_locked"] === 1 ? "Yes" : "No",
        $r["locked_at"] ?? "",
        $r["locked_by_name"] ?? "",
    ]);
}

fclose($output);
exit;
