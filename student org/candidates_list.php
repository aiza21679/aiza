<?php
// candidates_list.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed', 'error' => $mysqli->connect_error]);
        exit;
    }
    $mysqli->set_charset('utf8mb4');

    // Select columns and alias them to stable names the frontend expects.
    $sql = "SELECT
                c.Candidate_ID AS Candidate_ID,
                c.FullName AS FullName,
                c.PartyList AS PartyList,
                c.Org_ID AS Org_ID,
                c.Position_ID AS Position_ID,
                c.YearLevel AS YearLevel,
                c.Program AS Program,
                c.Experience AS Experience,
                c.Avatar AS Avatar,
                c.Platform AS Platform,
                p.Position_Name AS Position_Name,
                o.Org_Name AS Org_Name,
                c.created_at AS created_at,
                c.updated_at AS updated_at
            FROM candidate c
            LEFT JOIN `position` p ON c.Position_ID = p.Position_ID
            LEFT JOIN student_organization o ON c.Org_ID = o.Org_ID
            ORDER BY c.FullName ASC";

    $res = $mysqli->query($sql);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $mysqli->error]);
        exit;
    }

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        // Turn NULL into '' for JSON clarity
        foreach ($r as $k => $v) {
            if ($v === null) $r[$k] = '';
        }
        $rows[] = $r;
    }

    echo json_encode(['success' => true, 'data' => $rows]);

    $res->free();
    $mysqli->close();

} catch (Exception $ex) {
    error_log('candidates_list exception: '.$ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server exception']);
}
