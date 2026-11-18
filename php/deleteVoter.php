<?php
header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eboto_db";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['id']) || trim($_POST['id']) === '') {
    echo json_encode(['error' => 'Missing voter ID']);
    exit;
}

$voterId = $_POST['id'];

// Step 1: Remove voter from all organization memberships
$deleteMemberships = $conn->prepare("DELETE FROM student_organization_members WHERE Student_ID = ?");
$deleteMemberships->bind_param("s", $voterId);
$deleteMemberships->execute();
$deleteMemberships->close();

// Step 2: Delete voter from main student table
$deleteVoter = $conn->prepare("DELETE FROM student WHERE Student_ID = ?");
$deleteVoter->bind_param("s", $voterId);

if ($deleteVoter->execute()) {
    echo json_encode(['success' => true, 'message' => 'Voter deleted successfully']);
} else {
    echo json_encode(['error' => 'Failed to delete voter: ' . $conn->error]);
}

$deleteVoter->close();
$conn->close();
?>
