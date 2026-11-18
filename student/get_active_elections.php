<?php
// get_active_elections.php
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

    // Select elections whose status is Ongoing (case-insensitive)
    // Optionally you can also check dates: Start_Date <= NOW() AND End_Date >= NOW()
    $sql = "SELECT Election_ID, Election_Code, Title, Organization, Org_ID, Start_Date, End_Date, Total_Voters, Status, published_at, created_by
            FROM election
            WHERE LOWER(Status) = 'ongoing'";

    $res = $mysqli->query($sql);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $mysqli->error, 'sql' => $sql]);
        exit;
    }

    $elections = [];
    while ($row = $res->fetch_assoc()) {
        // prepare basic election object
        $e = [
            'id' => (int)$row['Election_ID'],
            'code' => $row['Election_Code'],
            'title' => $row['Title'],
            'organization' => $row['Organization'],
            'Org_ID' => $row['Org_ID'] !== null ? (int)$row['Org_ID'] : null,
            'startDate' => $row['Start_Date'],
            'endDate' => $row['End_Date'],
            'totalVoters' => (int)$row['Total_Voters'],
            'status' => $row['Status'],
            'published_at' => $row['published_at'],
            'created_by' => $row['created_by'],
            'candidates' => [] // fill below
        ];
        $elections[$e['id']] = $e; // keep keyed by id for easier attach
    }
    $res->free();

    // If no elections found, return empty array
    if (count($elections) === 0) {
        echo json_encode(['success' => true, 'data' => []]);
        $mysqli->close();
        exit;
    }

    // Load approved candidates that we can map to elections by Org_ID (or fallback: all approved)
    // We try to attach candidates whose Org_ID matches election Org_ID
    $sql2 = "SELECT Candidate_ID, Student_ID, Org_ID, PartyList, FullName, Email, YearLevel, Program, Experience, Avatar, Platform, Position_ID, status
             FROM candidate
             WHERE LOWER(status) = 'approved'";

    $res2 = $mysqli->query($sql2);
    if ($res2) {
        while ($c = $res2->fetch_assoc()) {
            $candidateObj = [
                'id' => (int)$c['Candidate_ID'],
                'student_id' => $c['Student_ID'] !== null ? (int)$c['Student_ID'] : null,
                'Org_ID' => $c['Org_ID'] !== null ? (int)$c['Org_ID'] : null,
                'party' => $c['PartyList'],
                'name' => $c['FullName'],
                'email' => $c['Email'],
                'yearLevel' => $c['YearLevel'],
                'program' => $c['Program'],
                'experience' => $c['Experience'],
                'avatar' => $c['Avatar'],
                'platform' => $c['Platform'],
                'position_id' => $c['Position_ID']
            ];

            // Attach by Org_ID match if possible
            $attached = false;
            if (!empty($candidateObj['Org_ID'])) {
                foreach ($elections as $eid => &$ev) {
                    if ($ev['Org_ID'] !== null && intval($ev['Org_ID']) === intval($candidateObj['Org_ID'])) {
                        $ev['candidates'][] = $candidateObj;
                        $attached = true;
                        break;
                    }
                }
            }

            // If not attached (no Org_ID or no matching election org), attach to any election whose org name matches candidate raw fields (fallback)
            if (!$attached) {
                // simple fallback: attach candidate to first election (developer choice) OR skip
                // We'll attach to all elections that do not have an org_id defined so smaller chance to miss
                foreach ($elections as $eid => &$ev) {
                    if ($ev['Org_ID'] === null) {
                        $ev['candidates'][] = $candidateObj;
                        $attached = true;
                        break;
                    }
                }
            }
            unset($ev);
        }
        $res2->free();
    } else {
        // If candidate query failed, still return elections without candidates
        // (we don't abort)
        // log error server-side
        error_log("get_active_elections: candidate query failed: " . $mysqli->error);
    }

    // convert elections back to numeric-indexed array to send JSON
    $out = array_values($elections);

    echo json_encode(['success' => true, 'data' => $out]);
    $mysqli->close();

} catch (Exception $ex) {
    error_log('get_active_elections exception: '.$ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server exception', 'error' => $ex->getMessage()]);
}
