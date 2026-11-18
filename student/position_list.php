<?php
// position_list.php
header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed', 'error' => $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

$sql = "SELECT Position_ID, Position_Name FROM position ORDER BY Position_ID ASC";
if (!$result = $mysqli->query($sql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $mysqli->error]);
    $mysqli->close();
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$result->free();
$mysqli->close();

echo json_encode($rows);
