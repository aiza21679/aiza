<?php
// student/get_user_votes.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once('db.php');

$student_id = 0;
if (!empty($_SESSION['Student_ID'])) $student_id = (int)$_SESSION['Student_ID'];
elseif (!empty($_GET['student_id'])) $student_id = (int)$_GET['student_id'];

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Missing student_id']);
    exit;
}

$mysqli = new mysqli($host, $user, $pass, $db);
$mysqli->set_charset('utf8mb4');

// Group votes per election for this student
$sql = "
  SELECT v.Election_ID, e.Title AS election_title, GROUP_CONCAT(v.Candidate_ID) AS selections, MIN(v.Timestamp) AS first_voted_at
  FROM vote v
  LEFT JOIN election e ON e.Election_ID = v.Election_ID
  WHERE v.Student_ID = ?
  GROUP BY v.Election_ID
  ORDER BY first_voted_at DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = [
        'election_id' => (int)$r['Election_ID'],
        'election_title' => $r['election_title'],
        'selections' => $r['selections'] ? array_map('intval', explode(',', $r['selections'])) : [],
        'timestamp' => $r['first_voted_at']
    ];
}
$stmt->close();
echo json_encode(['status'=>'success','data'=>$rows]);
exit;
