<?php
/**
 * aid/update_record.php — Update status/remarks on a distribution record.
 *
 * POST params:
 *   record_id  = int
 *   field      = "status" | "remarks"
 *   value      = string
 *   csrf_token = string
 *
 * Only unlocked records can be updated.
 * Returns JSON response.
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function jsonOut(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(["success" => $ok, "message" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/* --- Only POST --- */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonOut(false, "Method not allowed.", 405);
}

/* --- CSRF --- */
$csrfToken = $_POST["csrf_token"] ?? "";
if (!validate_csrf($csrfToken)) {
    jsonOut(false, "Invalid CSRF token. Please refresh and try again.", 403);
}

/* --- Input --- */
$recordId = isset($_POST["record_id"]) ? (int)$_POST["record_id"] : 0;
$field    = trim($_POST["field"] ?? "");
$value    = trim($_POST["value"] ?? "");
$userId   = $_SESSION["user_id"] ?? 0;

if ($recordId <= 0) {
    jsonOut(false, "Invalid record ID.", 400);
}

$allowedFields = ["status", "remarks"];
if (!in_array($field, $allowedFields, true)) {
    jsonOut(false, "Invalid field.", 400);
}

/* --- Validate field values --- */
if ($field === "status") {
    $allowedStatus = ["Pending", "Received", "Cancelled"];
    if (!in_array($value, $allowedStatus, true)) {
        jsonOut(false, "Invalid status value.", 400);
    }
}

if ($field === "remarks") {
    $value = mb_substr($value, 0, 255);
}

/* --- Fetch current record --- */
$stmt = $conn->prepare("SELECT id, is_locked, status, remarks FROM aid_distribution WHERE id = ?");
$stmt->bind_param("i", $recordId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    jsonOut(false, "Record not found.", 404);
}

if ((int)$record["is_locked"] === 1) {
    jsonOut(false, "Cannot update a locked record. Unlock it first.", 403);
}

/* --- Update --- */
if ($field === "status") {
    $stmt = $conn->prepare("UPDATE aid_distribution SET status = ? WHERE id = ? AND is_locked = 0");
    $stmt->bind_param("si", $value, $recordId);
} else {
    $stmt = $conn->prepare("UPDATE aid_distribution SET remarks = ? WHERE id = ? AND is_locked = 0");
    $stmt->bind_param("si", $value, $recordId);
}

$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    $oldValue = $record[$field] ?? "";
    write_activity_log(
        $conn,
        "UPDATE",
        "aid_distribution",
        $recordId,
        "Updated $field on record #$recordId: '$oldValue' → '$value'"
    );
    jsonOut(true, ucfirst($field) . " updated successfully.");
}

$stmt->close();
jsonOut(true, "No changes made (value may already be the same).");
