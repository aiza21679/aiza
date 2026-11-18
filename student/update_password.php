<?php
// student/update_password.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    echo json_encode(['success'=>false,'error'=>'not_logged_in']);
    exit;
}

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';

if (strlen($new) < 8) {
    echo json_encode(['success'=>false,'error'=>'new_password_too_short']);
    exit;
}

// fetch stored password hash
$stmt = $mysqli->prepare("SELECT Password FROM student WHERE Student_ID = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success'=>false,'error'=>'student_not_found']);
    exit;
}

$storedHash = $row['Password'] ?? '';

// If stored passwords are not hashed yet (older system) you must adapt. We'll assume hashed.
if (!password_verify($current, $storedHash)) {
    echo json_encode(['success'=>false,'error'=>'current_password_incorrect']);
    exit;
}

// Hash new password and update
$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE student SET Password = ? WHERE Student_ID = ?");
$stmt->bind_param('si', $newHash, $student_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success'=>false,'error'=>'db_update_failed']);
    exit;
}

echo json_encode(['success'=>true,'message'=>'password_updated']);
