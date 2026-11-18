<?php
header('Content-Type: application/json; charset=utf-8');

// DB config - change to your own
$DB_HOST = 'localhost';
$DB_NAME = 'eboto_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Helper
function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Required fields — adapt names to your form 'name' attributes
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$year_level = trim($_POST['year_level'] ?? '');
$position = trim($_POST['position'] ?? '');
$organization = trim($_POST['organization'] ?? '');
$party = trim($_POST['party'] ?? '');
$program = trim($_POST['program'] ?? '');
$platform = trim($_POST['platform'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$status = trim($_POST['status'] ?? 'pending'); // default

if (!$name || !$email || !$student_id || !$position || !$organization || !$party) {
    json_error('Missing required fields');
}

// Handle avatar upload if present
$avatar_path = null;
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/candidates/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $tmp = $_FILES['avatar']['tmp_name'];
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif'];
    if (!in_array(strtolower($ext), $allowed)) {
        json_error('Invalid avatar file type. Allowed: ' . implode(',', $allowed));
    }

    $basename = uniqid('cand_', true) . '.' . $ext;
    $dest = $uploadDir . $basename;

    if (!move_uploaded_file($tmp, $dest)) {
        json_error('Failed to save avatar file', 500);
    }

    // store web path (adjust to your project structure)
    $avatar_path = 'uploads/candidates/' . $basename;
} else {
    // default avatar
    $avatar_path = 'assests/default-avatar.jpg'; // change if your path differs
}

// Insert into DB — adapt column names to your table
try {
    $sql = "INSERT INTO candidate 
        (name, email, Student_ID, year_level, position, organization, partyList, program, Platform, Experience, avatar, status)
        VALUES (:name, :email, :student_id, :year_level, :position, :organization, :party, :program, :platform, :experience, :avatar, :status)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':student_id' => $student_id,
        ':year_level' => $year_level,
        ':position' => $position,
        ':organization' => $organization,
        ':party' => $party,
        ':program' => $program,
        ':platform' => $platform,
        ':experience' => $experience,
        ':avatar' => $avatar_path,
        ':status' => $status
    ]);

    echo json_encode(['success' => true, 'message' => 'Candidate added', 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    // For production, don't echo $e->getMessage()
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB insert failed']);
}
