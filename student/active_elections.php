<?php
// active_elections.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $mysqli = new mysqli('localhost','root','','eboto_db');
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'DB connection failed']);
        exit;
    }
    $mysqli->set_charset('utf8mb4');

    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

    // 1) Fetch ongoing elections
    $sql = "SELECT Election_ID, Election_Code, Title, Organization, Org_ID, Start_Date, End_Date, Total_Voters, Status
            FROM election
            WHERE Status = 'Ongoing' OR Status = 'ongoing' OR Status = 'Ongoing'"; // normalize possible values
    $res = $mysqli->query($sql);
    if (!$res) {
        throw new Exception($mysqli->error);
    }

    $elections = [];
    while ($e = $res->fetch_assoc()) {
        $eid = (int)$e['Election_ID'];

        // 2) Fetch positions used in this election (assuming position table is global)
        // If you have election_position join table, use it instead.
        $posStmt = $mysqli->prepare("SELECT Position_ID, Position_Name FROM position ORDER BY Position_Name ASC");
        $posStmt->execute();
        $posRes = $posStmt->get_result();
        $positions = [];
        while ($p = $posRes->fetch_assoc()) {
            $positions[] = $p;
        }
        $posStmt->close();

        // 3) Fetch candidates for this election.
        // IMPORTANT: Your candidate table doesn't contain Election_ID column in screenshot.
        // If your candidates belong to an election via Org_ID or election_position table, adjust the WHERE clause.
        // Here we assume all approved candidates are eligible for the election (filter by status = 'approved').
        $candStmt = $mysqli->prepare("
            SELECT c.Candidate_ID, c.FullName, c.Avatar, c.Platform, c.Position_ID, p.Position_Name, c.PartyList, c.Org_ID
            FROM candidate c
            LEFT JOIN position p ON c.Position_ID = p.Position_ID
            WHERE c.status = 'approved'
            ORDER BY p.Position_Name, c.FullName
        ");
        $candStmt->execute();
        $candRes = $candStmt->get_result();
        $candidates = [];
        while ($c = $candRes->fetch_assoc()) {
            $candidates[] = $c;
        }
        $candStmt->close();

        // 4) Check if this student already voted for this election (check vote_master)
        $voted = false;
        if ($student_id > 0) {
            $chk = $mysqli->prepare("SELECT 1 FROM vote_master WHERE Student_ID = ? AND Election_ID = ?");
            $chk->bind_param("ii",$student_id,$eid);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) $voted = true;
            $chk->close();
        }

        $elections[] = [
            'Election_ID' => $eid,
            'Election_Code' => $e['Election_Code'],
            'Title' => $e['Title'],
            'Organization' => $e['Organization'],
            'Org_ID' => $e['Org_ID'],
            'Start_Date' => $e['Start_Date'],
            'End_Date' => $e['End_Date'],
            'Total_Voters' => $e['Total_Voters'],
            'Status' => $e['Status'],
            'positions' => $positions,
            'candidates' => $candidates,
            'userVoted' => $voted
        ];
    }

    echo json_encode(['success'=>true,'data'=>$elections]);

    $mysqli->close();
} catch (Exception $ex) {
    error_log('active_elections exception: '.$ex->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
