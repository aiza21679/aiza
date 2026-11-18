<?php
// get_election_positions.php
header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([]); exit;
}
$mysqli->set_charset('utf8mb4');

$eid = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;
if ($eid <= 0) { echo json_encode([]); $mysqli->close(); exit; }

// Option A: use election_position mapping table (uncomment if you have it)
/*
$sql = "
  SELECT ep.Position_ID, IFNULL(p.Position_Name,'') AS Position_Name
  FROM election_position ep
  LEFT JOIN position p ON ep.Position_ID = p.Position_ID
  WHERE ep.Election_ID = ?
  ORDER BY ep.Position_Order ASC, p.Position_Name ASC
";
$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $res = $stmt->get_result();
    $positions = [];
    while ($r = $res->fetch_assoc()) {
        $positions[] = $r;
    }
    $stmt->close();
    echo json_encode($positions);
    $mysqli->close();
    exit;
}
*/

// Option B: fallback - derive distinct positions from candidates
$sql = "
  SELECT DISTINCT c.Position_ID, IFNULL(p.Position_Name,'') AS Position_Name
  FROM candidate c
  LEFT JOIN position p ON c.Position_ID = p.Position_ID
  WHERE c.Election_ID = ?
  ORDER BY p.Position_Name ASC
";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([]); $mysqli->close(); exit;
}
$stmt->bind_param('i', $eid);
$stmt->execute();
$res = $stmt->get_result();

$positions = [];
while ($r = $res->fetch_assoc()) {
    $positions[] = $r;
}

$stmt->close();
$mysqli->close();
echo json_encode($positions);
