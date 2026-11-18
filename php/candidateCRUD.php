<?php
// candidateCRUD.php

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$db   = 'your_database';
$user = 'your_username';
$pass = 'your_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ["Database connection failed: ".$e->getMessage()]]);
    exit;
}

// Determine action
$mode = $_GET['mode'] ?? $_POST['mode'] ?? 'fetch';

switch($mode) {
    case 'add':
        $student_id = intval($_POST['student_id'] ?? 0);
        $org_id = intval($_POST['org_id'] ?? 0);
        $platform = trim($_POST['platform'] ?? '');
        $position_id = intval($_POST['position_id'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if (!$student_id || !$org_id || !$position_id) {
            echo json_encode(['success' => false, 'errors' => ['Student, Organization, and Position are required.']]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO candidate (Student_ID, Org_ID, Platform, Position_ID, status) VALUES (:student_id,:org_id,:platform,:position_id,:status)");
        $stmt->execute([
            ':student_id' => $student_id,
            ':org_id' => $org_id,
            ':platform' => $platform,
            ':position_id' => $position_id,
            ':status' => $status
        ]);
        echo json_encode(['success' => true, 'message' => 'Candidate added successfully.']);
        break;

    case 'update':
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        $student_id = intval($_POST['student_id'] ?? 0);
        $org_id = intval($_POST['org_id'] ?? 0);
        $platform = trim($_POST['platform'] ?? '');
        $position_id = intval($_POST['position_id'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if (!$candidate_id || !$student_id || !$org_id || !$position_id) {
            echo json_encode(['success' => false, 'errors' => ['All required fields are missing.']]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE candidate SET Student_ID=:student_id, Org_ID=:org_id, Platform=:platform, Position_ID=:position_id, status=:status WHERE Candidate_ID=:candidate_id");
        $stmt->execute([
            ':student_id' => $student_id,
            ':org_id' => $org_id,
            ':platform' => $platform,
            ':position_id' => $position_id,
            ':status' => $status,
            ':candidate_id' => $candidate_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Candidate updated successfully.']);
        break;

    case 'delete':
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        if (!$candidate_id) {
            echo json_encode(['success' => false, 'errors' => ['Candidate ID is required.']]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM candidate WHERE Candidate_ID=:candidate_id");
        $stmt->execute([':candidate_id' => $candidate_id]);
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully.']);
        break;

    case 'fetch':
    default:
        $stmt = $pdo->query("SELECT * FROM candidate ORDER BY Candidate_ID ASC");
        $candidates = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $candidates]);
        break;
}
