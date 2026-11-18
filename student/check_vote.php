<?php
// check_vote.php
header('Content-Type: application/json; charset=utf-8');
try {
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    $election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;
    if (!$student_id || !$election_id) {
        echo json_encode(['success'=>false,'message'=>'Missing parameters']);
        exit;
    }

    $mysqli = new mysqli('localhost','root','','eboto_db');
    if ($mysqli->connect_errno) {
        echo json_encode(['success'=>false,'message'=>'DB connection failed']);
        exit;
    }
    $mysqli->set_charset('utf8mb4');

    $stmt = $mysqli->prepare("SELECT 1 FROM vote_master WHERE Student_ID = ? AND Election_ID = ?");
    $stmt->bind_param("ii", $student_id, $election_id);
    $stmt->execute();
    $stmt->store_result();
    $voted = $stmt->num_rows > 0;
    $stmt->close();
    $mysqli->close();

    echo json_encode(['success'=>true,'voted'=>$voted]);
} catch (Exception $ex) {
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
