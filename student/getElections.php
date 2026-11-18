<?php
// php/getElections.php
ini_set('display_errors',1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$userId = isset($_SESSION['Student_ID']) ? (int)$_SESSION['Student_ID'] : null;

try {
    $stmt = $pdo->query("
        SELECT Election_ID, Election_Code, Title, Organization, Organization_ID, Start_Date, End_Date, Total_Voters, Status, published_at, created_by
        FROM election
        ORDER BY Start_Date DESC
    ");
    $rows = $stmt->fetchAll();

    $result = [];

    foreach ($rows as $e) {
        $eid = (int)$e['Election_ID'];

        // votes count
        $vStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM vote WHERE Election_ID = ?");
        $vStmt->execute([$eid]);
        $votedCount = (int)$vStmt->fetchColumn();

        // user voted?
        $userVoted = false;
        if ($userId) {
            $uv = $pdo->prepare("SELECT 1 FROM vote WHERE Election_ID = ? AND Student_ID = ? LIMIT 1");
            $uv->execute([$eid, $userId]);
            $userVoted = (bool)$uv->fetchColumn();
        }

        // compute progress and timeRemaining
        $now = new DateTime('now');
        $progress = 0;
        $timeRemText = '';
        $startRaw = $e['Start_Date'];
        $endRaw = $e['End_Date'];

        if ($startRaw && $endRaw) {
            $startDT = new DateTime($startRaw);
            $endDT = new DateTime($endRaw);
            if ($now < $startDT) {
                $progress = 0;
                $timeRemText = 'Upcoming';
                $status = 'scheduled';
            } elseif ($now > $endDT) {
                $progress = 100;
                $timeRemText = 'Finished';
                $status = 'finished';
            } else {
                $total = $endDT->getTimestamp() - $startDT->getTimestamp();
                $elapsed = $now->getTimestamp() - $startDT->getTimestamp();
                $progress = $total > 0 ? (int)round(($elapsed / $total) * 100) : 0;
                $diff = $endDT->diff($now);
                $days = $diff->days;
                $timeRemText = $days > 0 ? "Approximately {$days} day" . ($days>1?'s':'') . " remaining" : "Less than a day remaining";
                $status = 'ongoing';
            }
        } else {
            $status = strtolower($e['Status'] ?? 'scheduled');
        }

        // fetch candidates by Org_ID (your schema links candidate -> Org_ID)
        $candidates = [];
        $cStmt = $pdo->prepare("SELECT Candidate_ID, FullName, PartyList, Avatar, Platform, Position_ID, YearLevel, Program FROM candidate WHERE Org_ID = ?");
        $cStmt->execute([(int)$e['Organization_ID']]);
        $candRows = $cStmt->fetchAll();
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

        $prettyStart = $startRaw ? (new DateTime($startRaw))->format('Y-m-d H:i:s') : '';
        $prettyEnd = $endRaw ? (new DateTime($endRaw))->format('Y-m-d H:i:s') : '';

        $result[] = [
            'id' => $eid,
            'code' => $e['Election_Code'],
            'title' => $e['Title'],
            'department' => $e['Organization'],
            'organization_id' => (int)$e['Organization_ID'],
            'status' => strtolower($status),
            'startDate' => $prettyStart,
            'endDate' => $prettyEnd,
            'description' => $e['Title'],
            'totalVoters' => (int)$e['Total_Voters'],
            'votedCount' => $votedCount,
            'candidates' => $candidates,
            'userVoted' => $userVoted,
            'progress' => (int)$progress,
            'timeRemaining' => $timeRemText
        ];
    }

    echo json_encode(['status'=>'success','elections'=>$result], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'server_error','message'=>$ex->getMessage()]);
    exit;
}
