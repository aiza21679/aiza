<?php
// student/castVote.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

try {
    /* ------------------ DB CONNECTION ------------------ */
    require_once('db.php');
    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
        exit;
    }
    $mysqli->set_charset('utf8mb4');


    /* ------------------ VALIDATE USER SESSION ------------------ */
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Only students can vote'
        ]);
        exit;
    }

    // Student_ID is guaranteed numeric
    if (!empty($_SESSION['Student_ID']) && is_numeric($_SESSION['Student_ID'])) {
        $student_id = (int)$_SESSION['Student_ID'];
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Student session not found'
        ]);
        exit;
    }


    /* ------------------ READ JSON BODY ------------------ */
    $rawBody = file_get_contents("php://input");
    $data = json_decode($rawBody, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
        exit;
    }

    /* ------------------ READ FIELDS ------------------ */
    $election_id = isset($data['election_id']) ? (int)$data['election_id'] : 0;
    $selections  = isset($data['selections']) && is_array($data['selections']) ? $data['selections'] : [];

    if (!$election_id || empty($selections)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing election_id or selections'
        ]);
        exit;
    }


    /* ------------------ CHECK ELECTION STATUS ------------------ */
    $stmt = $mysqli->prepare("SELECT Status FROM election WHERE Election_ID = ? LIMIT 1");
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Election not found']);
        exit;
    }

    $status = strtolower($row['Status']);
    if ($status !== 'ongoing' && $status !== 'active') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Election is not active']);
        exit;
    }


    /* ------------------ PREVENT DUPLICATE VOTES ------------------ */
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM vote WHERE Student_ID = ? AND Election_ID = ?");
    $stmt->bind_param("ii", $student_id, $election_id);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    if ($dup > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You already voted in this election']);
        exit;
    }


    /* ------------------ VALIDATE CANDIDATE IDs ------------------ */
    $placeholders = implode(',', array_fill(0, count($selections), '?'));
    $types = str_repeat('i', count($selections));

    $sql = "SELECT Candidate_ID FROM candidate WHERE Candidate_ID IN ($placeholders)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$selections);
    $stmt->execute();
    $result = $stmt->get_result();

    $valid = [];
    while ($r = $result->fetch_assoc()) {
        $valid[] = (int)$r['Candidate_ID'];
    }
    $stmt->close();

    $invalid = array_diff($selections, $valid);
    if (!empty($invalid)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid candidate selected',
            'invalid_ids' => array_values($invalid)
        ]);
        exit;
    }


    /* ------------------ INSERT VOTES ------------------ */
    $mysqli->begin_transaction();
    $stmt = $mysqli->prepare("INSERT INTO vote (Student_ID, Election_ID, Candidate_ID, Timestamp) VALUES (?,?,?,NOW())");

    foreach ($selections as $cid) {
        $stmt->bind_param("iii", $student_id, $election_id, $cid);
        if (!$stmt->execute()) {
            $mysqli->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to record vote']);
            exit;
        }
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['status' => 'success', 'message' => 'Vote recorded']);
    exit;


} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error',
        'detail' => $e->getMessage()
    ]);
    exit;
}
