<?php
/**
 * aid/lock.php — Lock / Unlock distribution records.
 *
 * POST params:
 *   action     = "lock" | "unlock"
 *   record_id  = int
 *   csrf_token = string
 *
 * Lock: any authenticated user can lock.
 * Unlock: only super_admin.
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
$action   = trim($_POST["action"] ?? "");
$recordId = isset($_POST["record_id"]) ? (int)$_POST["record_id"] : 0;
$role     = $_SESSION["role"] ?? "admin_staff";
$userId   = $_SESSION["user_id"] ?? 0;

if (!in_array($action, ["lock", "unlock"], true)) {
    jsonOut(false, "Invalid action.", 400);
}
if ($recordId <= 0) {
    jsonOut(false, "Invalid record ID.", 400);
}

/* --- Fetch current record --- */
$stmt = $conn->prepare("SELECT id, is_locked, locked_by FROM aid_distribution WHERE id = ?");
$stmt->bind_param("i", $recordId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    jsonOut(false, "Record not found.", 404);
}

/* === LOCK === */
if ($action === "lock") {
    if ((int)$record["is_locked"] === 1) {
        jsonOut(false, "Record is already locked.", 409);
    }

    $now = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("
        UPDATE aid_distribution
        SET is_locked = 1, locked_by = ?, locked_at = ?, finalized_at = ?
        WHERE id = ? AND is_locked = 0
    ");
    $stmt->bind_param("issi", $userId, $now, $now, $recordId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        write_activity_log($conn, "LOCK", "aid_distribution", $recordId, "Locked aid distribution record #$recordId");
        jsonOut(true, "Record locked successfully.");
    }

    $stmt->close();
    jsonOut(false, "Failed to lock record — it may have been locked by someone else.", 409);
}

/* === UNLOCK === */
if ($action === "unlock") {
    /* Only super_admin can unlock */
    if ($role !== "super_admin") {
        jsonOut(false, "Only Super Admin can unlock records.", 403);
    }

    if ((int)$record["is_locked"] === 0) {
        jsonOut(false, "Record is not locked.", 409);
    }

    $stmt = $conn->prepare("
        UPDATE aid_distribution
        SET is_locked = 0, locked_by = NULL, locked_at = NULL, finalized_at = NULL
        WHERE id = ? AND is_locked = 1
    ");
    $stmt->bind_param("i", $recordId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        write_activity_log($conn, "UNLOCK", "aid_distribution", $recordId, "Unlocked aid distribution record #$recordId");
        jsonOut(true, "Record unlocked successfully.");
    }

    $stmt->close();
    jsonOut(false, "Failed to unlock record.", 500);
}
