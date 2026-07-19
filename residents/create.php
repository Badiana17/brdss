<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function go_back(string $msg, bool $ok = false): void {
    $key = $ok ? "success" : "error";
    header("Location: index.php?" . $key . "=" . urlencode($msg));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") go_back("Invalid request.");

/* ── Collect & sanitize ── */
$first_name           = trim($_POST["first_name"]           ?? "");
$middle_name          = trim($_POST["middle_name"]          ?? "");
$last_name            = trim($_POST["last_name"]            ?? "");
$suffix               = trim($_POST["suffix"]               ?? "");
$birthday             = trim($_POST["birthday"]             ?? "");
$address              = trim($_POST["address"]              ?? "");
$barangay             = trim($_POST["barangay"]             ?? "");
$zone                 = trim($_POST["zone"]                 ?? "");
$contact_no           = trim($_POST["contact_no"]           ?? "");
$gender               = trim($_POST["gender"]               ?? "");
$civil_status         = trim($_POST["civil_status"]         ?? "");
$is_voter             = $_POST["is_voter"]                  ?? "";
$status               = trim($_POST["status"]               ?? "");
$beneficiary_category = trim($_POST["beneficiary_category"] ?? "None");

/* ── Required field validation ── */
if ($first_name === "" || $last_name === "" || $middle_name === "" || $birthday === "" ||
    $address === "" || $barangay === "" || $zone === "" || $contact_no === "" ||
    $gender === "" || $civil_status === "" || $is_voter === "" || $status === "") {
    go_back("Please complete all required fields (*).");
}

/* ── Compute age server-side ── */
$computedAge = 0;
try {
    $b = new DateTime($birthday);
    $t = new DateTime();
    $computedAge = (int)$t->diff($b)->y;
} catch (Exception $e) {
    go_back("Invalid birth date.");
}
if ($computedAge < 0 || $computedAge > 130) go_back("Birth date/age seems invalid.");

/* ── Normalize ── */
$allowedCats = ["Senior", "PWD", "Student", "None"];
if (!in_array($beneficiary_category, $allowedCats, true)) $beneficiary_category = "None";
$is_voter = ($is_voter === "1" || $is_voter === 1) ? 1 : 0;

/* ── Check for duplicates ── */
$chkSql = "SELECT resident_id, deleted_at FROM residents WHERE first_name = ? AND last_name = ? AND birthday = ? LIMIT 1";
$chkStmt = $conn->prepare($chkSql);
if ($chkStmt) {
    $chkStmt->bind_param("sss", $first_name, $last_name, $birthday);
    $chkStmt->execute();
    $dupResult = $chkStmt->get_result()->fetch_assoc();
    $chkStmt->close();

    if ($dupResult) {
        if ($dupResult["deleted_at"] !== null) {
            go_back("This resident already exists but is in the Archive/Recycle Bin. Please restore them instead.");
        } else {
            go_back("A resident with the exact Name and Birth Date already exists!");
        }
    }
}

/* ── Insert main resident record ──
   Columns: first_name(s) middle_name(s) last_name(s) suffix(s) birthday(s)
            age(i) gender(s) civil_status(s) is_voter(i)
            address(s) barangay(s) zone(s) contact_no(s) beneficiary_category(s) status(s)
   = 5s + i + 2s + i + 6s  →  15 params
*/
$sql = "INSERT INTO residents
    (first_name, middle_name, last_name, suffix, birthday, age, gender, civil_status, is_voter,
     address, barangay, zone, contact_no, beneficiary_category, status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
if (!$stmt) go_back("Prepare failed: " . $conn->error);

$t_str = str_repeat('s', 5) . 'i' . str_repeat('s', 2) . 'i' . str_repeat('s', 6); // 15 chars

$stmt->bind_param($t_str,
    $first_name, $middle_name, $last_name, $suffix, $birthday,
    $computedAge, $gender, $civil_status, $is_voter,
    $address, $barangay, $zone, $contact_no,
    $beneficiary_category, $status
);

if (!$stmt->execute()) go_back("Insert failed: " . $stmt->error);
$insId = (int)$conn->insert_id;
$stmt->close();

/* ── Insert category-specific details ── */
if ($beneficiary_category === "Senior") {
    $osca_id_no       = trim($_POST["osca_id_no"]       ?? "");
    $osca_issued_date = trim($_POST["osca_issued_date"] ?? "") ?: null;

    $s = $conn->prepare("INSERT INTO senior_citizens (resident_id, osca_id_no, osca_issued_date) VALUES (?,?,?)");
    if ($s) {
        $s->bind_param("iss", $insId, $osca_id_no, $osca_issued_date);
        $s->execute();
        $s->close();
    }

} elseif ($beneficiary_category === "PWD") {
    $disability_type = trim($_POST["disability_type"] ?? "");
    $pwd_id_no       = trim($_POST["pwd_id_no"]       ?? "");
    $guardian_name   = trim($_POST["guardian_name"]   ?? "");
    $pwd_remarks     = trim($_POST["pwd_remarks"]     ?? "");
    $created_by      = $_SESSION["user_id"] ?? null;

    $s = $conn->prepare("INSERT INTO persons_with_disabilities
        (resident_id, disability_type, pwd_id_no, guardian_name, remarks, created_by)
        VALUES (?,?,?,?,?,?)");
    if ($s) {
        $s->bind_param("issssi", $insId, $disability_type, $pwd_id_no, $guardian_name, $pwd_remarks, $created_by);
        $s->execute();
        $s->close();
    }

} elseif ($beneficiary_category === "Student") {
    $grade_level       = trim($_POST["grade_level"]       ?? "");
    $student_is_active = (int)($_POST["student_is_active"] ?? 1);

    $s = $conn->prepare("INSERT INTO students (resident_id, grade_level, is_active) VALUES (?,?,?)");
    if ($s) {
        $s->bind_param("isi", $insId, $grade_level, $student_is_active);
        $s->execute();
        $s->close();
    }
}

write_activity_log($conn, "CREATE", "residents", $insId,
    "Added new resident: $first_name $last_name (Category: $beneficiary_category)");

go_back("Resident saved successfully!", true);