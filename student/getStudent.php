<?php
// getStudent.php
header('Content-Type: application/json; charset=utf-8');
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// DB config - adjust if you use a shared db.php instead
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "eboto_db";

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// Determine student id from session (try a few common keys)
$studentId = null;
$possibleKeys = ['Student_ID', 'student_id', 'user_id', 'user', 'id'];
foreach ($possibleKeys as $k) {
    if (!empty($_SESSION[$k])) {
        $studentId = $_SESSION[$k];
        break;
    }
}

// fallback to cookie (if you use it)
if (!$studentId && !empty($_COOKIE['student_id'])) {
    $studentId = $_COOKIE['student_id'];
}

if (!$studentId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    $conn->close();
    exit;
}

// Query student row (explicitly select profile_photo_url)
$q = "SELECT `Student_ID`, `Name`, `Email`, `Course`, `bio`, `profile_photo_url` FROM `student` WHERE `Student_ID` = ? LIMIT 1";
$stmt = $conn->prepare($q);
$stmt->bind_param("s", $studentId);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    $conn->close();
    exit;
}

// Normalize output keys for frontend tolerance
$out = [
    'Student_ID' => isset($student['Student_ID']) ? (string)$student['Student_ID'] : null,
    'StudentID'  => isset($student['Student_ID']) ? (string)$student['Student_ID'] : null,
    'id'         => isset($student['Student_ID']) ? (string)$student['Student_ID'] : null,
    'Name'       => $student['Name'] ?? '',
    'Email'      => $student['Email'] ?? '',
    'Course'     => $student['Course'] ?? '',
    'bio'        => $student['bio'] ?? '',
    // ensure we always send profile_photo_url key (empty string if NULL)
    'profile_photo_url' => $student['profile_photo_url'] ?? ''
];

echo json_encode(['status' => 'success', 'student' => $out]);
$conn->close();
exit;
