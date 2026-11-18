<?php
// addOrganization.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// DB credentials - adjust if needed
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Collect input (trimmed)
$orgCode = isset($_POST['orgCode']) ? trim($_POST['orgCode']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$orgDescription = isset($_POST['orgDescription']) ? trim($_POST['orgDescription']) : '';
$orgEmail = isset($_POST['orgEmail']) ? trim($_POST['orgEmail']) : '';
$orgImage = isset($_POST['orgImage']) ? trim($_POST['orgImage']) : '';

// Basic validation
if ($orgCode === '' || $course === '' || $orgDescription === '' || $orgEmail === '') {
    echo json_encode(['error' => 'Please fill all required fields.']);
    exit;
}
if (!filter_var($orgEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email address.']);
    exit;
}

// Build description: include course so users can search both
$fullDescription = $orgDescription . ' — ' . $course;

// Normalize logo path (if empty use default)
$logoToSave = ($orgImage !== '') ? $orgImage : '../assets/default-logo.jpg';

// Prepare insert
$sql = "INSERT INTO student_organization (Org_Name, Description, Email, Logo_Url, Registered_At)
        VALUES (?, ?, ?, ?, NOW())";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('ssss', $orgCode, $fullDescription, $orgEmail, $logoToSave);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    echo json_encode(['success' => true, 'Org_ID' => $newId]);
} else {
    echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
exit;
