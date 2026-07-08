<?php
/**
 * aid/bulk_lock.php — Bulk lock/unlock multiple distribution records.
 *
 * POST params:
 *   action     = "lock" | "unlock"
 *   record_ids = array of int
 *   csrf_token = string
 *
 * Lock: any authenticated user.
 * Unlock: only super_admin.
 * Returns JSON with count of affected records.
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function jsonOut(bool $ok, string $msg, int $code = 200, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(["success" => $ok, "message" => $msg], $extra), JSON_UNESCAPED_UNICODE);
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
$action    = trim($_POST["action"] ?? "");
$rawIds    = $_POST["record_ids"] ?? [];
$role      = $_SESSION["role"] ?? "admin_staff";
$userId    = $_SESSION["user_id"] ?? 0;

if (!in_array($action, ["lock", "unlock"], true)) {
    jsonOut(false, "Invalid action.", 400);
}

if ($action === "unlock" && $role !== "super_admin") {
    jsonOut(false, "Only Super Admin can unlock records.", 403);
}

if (!is_array($rawIds) || count($rawIds) === 0) {
    jsonOut(false, "No records selected.", 400);
}

/* --- Sanitize IDs --- */
$cleanIds = [];
foreach ($rawIds as $raw) {
    $id = (int)$raw;
    if ($id > 0) $cleanIds[] = $id;
}

if (count($cleanIds) === 0) {
    jsonOut(false, "No valid record IDs.", 400);
}

/* Limit bulk operations to 100 at a time */
if (count($cleanIds) > 100) {
    $cleanIds = array_slice($cleanIds, 0, 100);
}

$conn->begin_transaction();

try {
    $affected = 0;
    $now = date("Y-m-d H:i:s");

    if ($action === "lock") {
        $stmt = $conn->prepare("UPDATE aid_distribution SET is_locked = 1, locked_by = ?, locked_at = ?, finalized_at = ? WHERE id = ? AND is_locked = 0");
        foreach ($cleanIds as $id) {
            $stmt->bind_param("issi", $userId, $now, $now, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $affected++;
                write_activity_log($conn, "LOCK", "aid_distribution", $id, "Bulk locked aid distribution record #$id");
            }
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("UPDATE aid_distribution SET is_locked = 0, locked_by = NULL, locked_at = NULL, finalized_at = NULL WHERE id = ? AND is_locked = 1");
        foreach ($cleanIds as $id) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $affected++;
                write_activity_log($conn, "UNLOCK", "aid_distribution", $id, "Bulk unlocked aid distribution record #$id");
            }
        }
        $stmt->close();
    }

    $conn->commit();

    $label = $action === "lock" ? "locked" : "unlocked";
    jsonOut(true, "Successfully $label $affected record(s).", 200, ["affected" => $affected]);

} catch (Exception $e) {
    $conn->rollback();
    jsonOut(false, "Bulk operation failed: " . $e->getMessage(), 500);
}
