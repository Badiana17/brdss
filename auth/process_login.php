<?php
// auth/process_login.php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: login.php?error=" . urlencode("Please enter username and password."));
    exit;
}

$stmt = $conn->prepare("
    SELECT user_id, username, password_hash, role, is_active
    FROM users
    WHERE username = ?
    LIMIT 1
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || (int)$user["is_active"] !== 1) {
    header("Location: login.php?error=" . urlencode("Invalid credentials or inactive account."));
    exit;
}

if (!password_verify($password, $user["password_hash"])) {
    header("Location: login.php?error=" . urlencode("Invalid credentials."));
    exit;
}

/* secure session */
session_regenerate_id(true);

/* session values */
$_SESSION["user_id"] = $user["user_id"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = $user["role"];
$_SESSION["logged_in"] = true;

/* role redirect */
if ($user["role"] === "super_admin") {
    header("Location: ../dashboard/super.php");
    exit;
} elseif ($user["role"] === "admin_staff") {
    header("Location: ../dashboard/super.php");
    exit;
} else {
    session_unset();
    session_destroy();
    header("Location: login.php?error=" . urlencode("Invalid user role."));
    exit;
}