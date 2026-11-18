<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student_organization') {
    header("Location: ../login.html");
    exit;
}

$org_name = $_SESSION['user_name'] ?? 'Organization';
$org_email = $_SESSION['user_id'] ?? '';
$org_id = $_SESSION['org_id'] ?? '';
$org_logo = $_SESSION['org_logo'] ?? '../assests/default-avatar.jpg';
?>