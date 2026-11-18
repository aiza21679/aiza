<?php
// student/getElectionCandidates.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB connection failed']);
    exit;
}

// accept either election_id or electionId for robustness
$electionId = null;
if (isset($_GET['election_id'])) {
    $electionId = (int)$_GET['election_id'];
} elseif (isset($_GET['electionId'])) {
    $electionId = (int)$_GET['electionId'];
}

if (!$electionId) {
    echo json_encode(['status'=>'error','error'=>'missing_election_id']);
    exit;
}

/*
  Approach:
  - elections link to organizations (Organization_ID).
  - candidates table uses Org_ID (based on your code).
  - We will attempt to find Organization_ID from election and then fetch candidate rows for that org.
  - If candidate table also stores Election_ID, we'll prefer that.
*/

try {
    // try to get Organization_ID for this election
    $stmt = $pdo->prepare("SELECT Organization_ID FROM election WHERE Election_ID = :eid LIMIT 1");
    $stmt->execute([':eid'=>$electionId]);
    $orgRow = $stmt->fetch();

    $candidates = [];

    if ($orgRow && !empty($orgRow['Organization_ID'])) {
        $orgId = (int)$orgRow['Organization_ID'];
        $cstmt = $pdo->prepare("
            SELECT Candidate_ID, FullName, PartyList, Avatar, Platform, Position_ID, YearLevel, Program
            FROM candidate
            WHERE Org_ID = :org
            ORDER BY FullName ASC
        ");
        $cstmt->execute([':org' => $orgId]);
        $candRows = $cstmt->fetchAll();
        foreach ($candRows as $c) {
            $candidates[] = [
                'id' => (int)$c['Candidate_ID'],
                'name' => $c['FullName'],
                'party' => $c['PartyList'],
                'avatar' => $c['Avatar'] ?: '../assests/default-avatar.jpg',
                'platform' => $c['Platform'],
                'position_id' => isset($c['Position_ID']) ? (int)$c['Position_ID'] : null,
                'year_level' => $c['YearLevel'],
                'program' => $c['Program']
            ];
        }
    }

    // ALSO try candidate rows that directly reference Election_ID (if any)
    $c2stmt = $pdo->prepare("
        SELECT Candidate_ID, FullName, PartyList, Avatar, Platform, Position_ID, YearLevel, Program
        FROM candidate
        WHERE Election_ID = :eid
        ORDER BY FullName ASC
    ");
    $c2stmt->execute([':eid' => $electionId]);
    $direct = $c2stmt->fetchAll();
    foreach ($direct as $c) {
        // append if not already present
        $exists = false;
        foreach ($candidates as $ex) if ((int)$ex['id'] === (int)$c['Candidate_ID']) { $exists = true; break; }
        if ($exists) continue;
        $candidates[] = [
            'id' => (int)$c['Candidate_ID'],
            'name' => $c['FullName'],
            'party' => $c['PartyList'],
            'avatar' => $c['Avatar'] ?: '../assests/default-avatar.jpg',
            'platform' => $c['Platform'],
            'position_id' => isset($c['Position_ID']) ? (int)$c['Position_ID'] : null,
            'year_level' => $c['YearLevel'],
            'program' => $c['Program']
        ];
    }

    echo json_encode(['status'=>'success','candidates'=>$candidates], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'server_error']);
    exit;
}
