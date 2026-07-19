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

$csrf = $_POST["csrf_token"] ?? "";
if (!validate_csrf($csrf)) go_back("Invalid CSRF token.");

$resident_id = (int)($_POST["resident_id"] ?? 0);
if ($resident_id <= 0) go_back("Invalid resident id.");

/* ── Collect & sanitize ── */
$first_name           = trim($_POST["first_name"]           ?? "");
$middle_name          = trim($_POST["middle_name"]          ?? "");
$last_name            = trim($_POST["last_name"]            ?? "");
$suffix               = trim($_POST["suffix"]               ?? "");
$birthday             = trim($_POST["birthday"]             ?? "");
$age                  = (int)($_POST["age"]                 ?? 0);
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

/* ── Normalize ── */
$allowedCats = ["Senior", "PWD", "Student", "None"];
if (!in_array($beneficiary_category, $allowedCats, true)) $beneficiary_category = "None";
$is_voter = ($is_voter === "1" || $is_voter === 1) ? 1 : 0;

/* ── Update main resident record ──
   Columns: first_name(s) middle_name(s) last_name(s) suffix(s) birthday(s)
            age(i) gender(s) civil_status(s) is_voter(i)
            address(s) barangay(s) zone(s) contact_no(s) beneficiary_category(s) status(s)
            resident_id(i)
   = 5s + i + 2s + i + 6s + i →  16 params
*/
$sql = "UPDATE residents SET
          first_name = ?, middle_name = ?, last_name = ?, suffix = ?, birthday = ?,
          age = ?, gender = ?, civil_status = ?, is_voter = ?,
          address = ?, barangay = ?, zone = ?, contact_no = ?,
          beneficiary_category = ?, status = ?
        WHERE resident_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) go_back("Prepare failed: " . $conn->error);

$t_str = str_repeat('s', 5) . 'i' . str_repeat('s', 2) . 'i' . str_repeat('s', 6) . 'i'; // 16 chars

$stmt->bind_param($t_str,
    $first_name, $middle_name, $last_name, $suffix, $birthday,
    $age, $gender, $civil_status, $is_voter,
    $address, $barangay, $zone, $contact_no,
    $beneficiary_category, $status,
    $resident_id
);

if (!$stmt->execute()) go_back("Update failed: " . $stmt->error);
$stmt->close();

/* ── Upsert category-specific details ── */
if ($beneficiary_category === "Senior") {
    $osca_id_no       = trim($_POST["osca_id_no"]       ?? "");
    $osca_issued_date = trim($_POST["osca_issued_date"] ?? "") ?: null;

    $s = $conn->prepare("
        INSERT INTO senior_citizens (resident_id, osca_id_no, osca_issued_date)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE osca_id_no=VALUES(osca_id_no), osca_issued_date=VALUES(osca_issued_date)
    ");
    if ($s) {
        $s->bind_param("iss", $resident_id, $osca_id_no, $osca_issued_date);
        $s->execute();
        $s->close();
    }

} elseif ($beneficiary_category === "PWD") {
    $disability_type = trim($_POST["disability_type"] ?? "");
    $pwd_id_no       = trim($_POST["pwd_id_no"]       ?? "");
    $guardian_name   = trim($_POST["guardian_name"]   ?? "");
    $pwd_remarks     = trim($_POST["pwd_remarks"]     ?? "");

    $s = $conn->prepare("
        INSERT INTO persons_with_disabilities (resident_id, disability_type, pwd_id_no, guardian_name, remarks)
        VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            disability_type=VALUES(disability_type),
            pwd_id_no=VALUES(pwd_id_no),
            guardian_name=VALUES(guardian_name),
            remarks=VALUES(remarks)
    ");
    if ($s) {
        $s->bind_param("issss", $resident_id, $disability_type, $pwd_id_no, $guardian_name, $pwd_remarks);
        $s->execute();
        $s->close();
    }

} elseif ($beneficiary_category === "Student") {
    $grade_level       = trim($_POST["grade_level"]       ?? "");
    $student_is_active = (int)($_POST["student_is_active"] ?? 1);

    $s = $conn->prepare("
        INSERT INTO students (resident_id, grade_level, is_active)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE grade_level=VALUES(grade_level), is_active=VALUES(is_active)
    ");
    if ($s) {
        $s->bind_param("isi", $resident_id, $grade_level, $student_is_active);
        $s->execute();
        $s->close();
    }
}

write_activity_log($conn, "UPDATE", "residents", $resident_id, "Updated resident ID: $resident_id (Category: $beneficiary_category)");

go_back("Resident updated successfully.", true);