<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "eboto_db");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch student info including profile photo
$stmt = $conn->prepare("SELECT Name, Email, Course, Year_Level, profile_photo_url FROM student WHERE Student_ID = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();
$conn->close();

// Return student data as JSON
echo json_encode([
    'status' => 'success',
    'student' => $student
]);
?>
