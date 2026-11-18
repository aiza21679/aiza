<?php
// saveOrganization.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';
session_start();

function resp($arr){ echo json_encode($arr); exit; }

// Determine org id (session preferred)
$orgId = $_SESSION['org_id'] ?? ($_POST['orgId'] ?? null);
if (!$orgId) resp(['success' => false, 'error' => 'orgId missing']);

$name = trim($_POST['orgName'] ?? '');
$desc = trim($_POST['description'] ?? '');
$email = trim($_POST['email'] ?? '');

// Basic validation
if ($name === '' || $email === '') {
    resp(['success' => false, 'error' => 'Name and email are required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(['success' => false, 'error' => 'Invalid email']);
}

// Handle logo upload (optional)
$uploadsDir = __DIR__ . '/uploads/org_logos';
$publicPrefix = 'uploads/org_logos'; // path to store in DB
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

$logo_path = null;
if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['logo'];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed, true)) {
        resp(['success' => false, 'error' => 'Invalid logo file type']);
    }
    if ($file['size'] > 6 * 1024 * 1024) resp(['success' => false, 'error' => 'Logo too large']);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'org_'.$orgId.'_'.bin2hex(random_bytes(6)).'.'.$ext;
    $target = $uploadsDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        resp(['success' => false, 'error' => 'Failed to save uploaded logo']);
    }
    // optional: you can resize here
    $logo_path = $publicPrefix . '/' . $filename;
}

// Build SQL and execute prepared statement
$updateSql = "UPDATE student_organization
              SET Org_Name = ?, Description = ?, Email = ?" 
            . ($logo_path ? ", Logo_Url = ?" : "")
            . " WHERE Org_ID = ? LIMIT 1";

if ($stmt = $mysqli->prepare($updateSql)) {
    if ($logo_path) {
        $stmt->bind_param('ssssi', $name, $desc, $email, $logo_path, $orgId);
    } else {
        $stmt->bind_param('sssi', $name, $desc, $email, $orgId);
    }
    if (!$stmt->execute()) {
        resp(['success' => false, 'error' => 'DB update failed: '.$stmt->error]);
    }
    // Fetch updated row to return
    $sel = $mysqli->prepare("SELECT Org_ID, Org_Name, Description, Email, Logo_Url, Registered_At FROM student_organization WHERE Org_ID = ? LIMIT 1");
    $sel->bind_param('i', $orgId);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    $row['Logo_Url'] = $row['Logo_Url'] ?: ($logo_path ?: 'uploads/org-default.png');

    resp(['success' => true, 'organization' => $row, 'profile_photo_url' => $row['Logo_Url']]);
} else {
    resp(['success' => false, 'error' => 'Prepare failed: '.$mysqli->error]);
}
