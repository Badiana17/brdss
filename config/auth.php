<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
  if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
  }
}

function require_role($roles = []) {
  require_login();
  $userRole = $_SESSION["role"] ?? "";
  if (!in_array($userRole, $roles, true)) {
    http_response_code(403);
    echo "403 Forbidden - You don't have access to this page.";
    exit;
  }
}

/**
 * CSRF helpers — generate and validate tokens stored in $_SESSION.
 */
function generate_csrf(): string {
  if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
  }
  return $_SESSION["csrf_token"];
}

function validate_csrf(?string $token): bool {
  if (empty($token) || empty($_SESSION["csrf_token"])) return false;
  return hash_equals($_SESSION["csrf_token"], $token);
}

/**
 * Write a row to activity_log.
 * @param mysqli $conn
 */
function write_activity_log(mysqli $conn, string $actionType, string $tableAffected, ?int $recordId, string $activity): void {
  $userId = $_SESSION["user_id"] ?? 0;
  $ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
  $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, table_affected, record_id, activity, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ississ", $userId, $actionType, $tableAffected, $recordId, $activity, $ip);
  $stmt->execute();
  $stmt->close();
}