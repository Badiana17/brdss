<?php
require_once __DIR__ . '/auth.php';

function requireRole(array $allowedRoles)
{
    if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], $allowedRoles, true)) {
        header("Location: ../dashboard/unauthorized.php");
        exit;
    }
}