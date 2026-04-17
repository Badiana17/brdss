<?php
// config/auth.php
session_start();

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