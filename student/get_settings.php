<?php
// student/save_profile.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    echo json_encode(['success'=>false,'error'=>'not_logged_in']);
    exit;
}

// Accept inputs (POST)
$first = trim($_POST['firstName'] ?? '');
$last  = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$bio   = trim($_POST['bio'] ?? '');

// Basic validation (add more as needed)
if (!$first || !$last || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'error'=>'invalid_input']);
    exit;
}

// Handle photo upload (optional)
$uploadDir = __DIR__ . '/uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$profile_photo_url = null;
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['photo'];
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['success'=>false,'error'=>'invalid_image_type']);
        exit;
    }
    $newName = 'avatar_' . $student_id . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $newName;
    if (move_uploaded_file($f['tmp_name'], $dest)) {
        // store web path relative to project (adjust if necessary)
        $profile_photo_url = 'student/uploads/avatars/' . $newName;
    } else {
        // ignore upload error and continue
        $profile_photo_url = null;
    }
}

// Update student table (update Name, Email, bio, profile_photo_url)
$fullName = $first . ' ' . $last;
if ($profile_photo_url) {
    $stmt = $mysqli->prepare("UPDATE student SET Name = ?, Email = ?, bio = ?, profile_photo_url = ? WHERE Student_ID = ?");
    $stmt->bind_param('ssssi', $fullName, $email, $bio, $profile_photo_url, $student_id);
} else {
    $stmt = $mysqli->prepare("UPDATE student SET Name = ?, Email = ?, bio = ? WHERE Student_ID = ?");
    $stmt->bind_param('sssi', $fullName, $email, $bio, $student_id);
}

$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success'=>false,'error'=>'db_update_failed']);
    exit;
}

echo json_encode(['success'=>true, 'message'=>'profile_saved']);
