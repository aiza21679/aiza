<?php
// get_voting_history.php (hardened)
// returns: { success: true, data: [...] } on success
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');

require __DIR__ . '/db.php'; // creates $pdo
if (session_status() === PHP_SESSION_NONE) session_start();

// determine user id from session / cookie / optional override (for testing)
$uid = 0;
$possible_keys = ['Student_ID','student_id','user_id','id','student','uid','login_id'];
foreach ($possible_keys as $k) {
    if (!empty($_SESSION[$k])) { $uid = (int) $_SESSION[$k]; break; }
}
// allow test override via GET when needed (developer use only)
if (isset($_GET['uid']) && $_GET['uid']) $uid = (int) $_GET['uid'];
if (!$uid && isset($_COOKIE['student_id'])) $uid = (int) $_COOKIE['student_id'];

try {
    // 1) Load all elections (existing rows)
    $sql = "SELECT Election_ID, Election_Code, Title, Organization, Start_Date, End_Date, Total_Voters, Status, published_at
            FROM election
            ORDER BY Start_Date DESC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // prepared statements used repeatedly
    $voteByUserForElectionStmt = $pdo->prepare("
        SELECT v.Vote_ID, v.Candidate_ID, v.Election_ID, c.FullName, c.Position_ID, p.Position_Name
        FROM `vote` v
        LEFT JOIN `candidate` c ON v.Candidate_ID = c.Candidate_ID
        LEFT JOIN `position` p ON c.Position_ID = p.Position_ID
        WHERE v.Election_ID = :eid AND v.Student_ID = :uid
    ");

    $resultsStmt = $pdo->prepare("
        SELECT c.Candidate_ID, c.FullName, c.PartyList, c.Position_ID, p.Position_Name, COUNT(v.Vote_ID) AS votes
        FROM candidate c
        LEFT JOIN vote v ON c.Candidate_ID = v.Candidate_ID
        LEFT JOIN position p ON c.Position_ID = p.Position_ID
        WHERE c.Election_ID = :eid
        GROUP BY c.Candidate_ID
        ORDER BY p.Position_Name, votes DESC
    ");

    $elections = [];
    $existingElectionIds = [];

    // build response for existing elections
    foreach ($rows as $r) {
        $eid = (int)$r['Election_ID'];
        $existingElectionIds[] = $eid;

        // user's selections for this election
        $userSelections = [];
        $hasVoted = false;
        if ($uid) {
            $voteByUserForElectionStmt->execute([':eid' => $eid, ':uid' => $uid]);
            $voteRows = $voteByUserForElectionStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($voteRows && count($voteRows) > 0) {
                $hasVoted = true;
                foreach ($voteRows as $vr) {
                    $pos = $vr['Position_Name'] ?? ('Position ' . ($vr['Position_ID'] ?? ''));
                    $userSelections[$pos] = $vr['FullName'] ?? null;
                }
            }
        }

        // results aggregation for this election
        $results = [];
        $resultsStmt->execute([':eid' => $eid]);
        $resRows = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($resRows) {
            foreach ($resRows as $rr) {
                $results[] = [
                    'name' => $rr['FullName'],
                    'position' => $rr['Position_Name'] ?? ('Position ' . ($rr['Position_ID'] ?? '')),
                    'party' => $rr['PartyList'] ?? '',
                    'votes' => (int)$rr['votes']
                ];
            }
        }

        $elections[] = [
            'id' => $eid,
            'code' => $r['Election_Code'],
            'name' => $r['Title'],
            'details' => '',
            'date' => formatDateRange($r['Start_Date'], $r['End_Date']),
            'organization' => $r['Organization'],
            'status' => mapStatusToLower($r['Status']),
            'voted' => (bool)$hasVoted,
            'results' => $results,
            'userSelections' => (object)$userSelections,
            'raw' => $r
        ];
    }

    // 2) Find election IDs that the user voted in but which no longer exist in 'election' table
    if ($uid) {
        if (count($existingElectionIds) > 0) {
            // prepare placeholder list for NOT IN
            $placeholders = implode(',', array_fill(0, count($existingElectionIds), '?'));
            $sqlMissing = "SELECT DISTINCT Election_ID FROM `vote` WHERE Student_ID = ? AND Election_ID NOT IN ($placeholders)";
            $params = array_merge([$uid], $existingElectionIds);
        } else {
            $sqlMissing = "SELECT DISTINCT Election_ID FROM `vote` WHERE Student_ID = ?";
            $params = [$uid];
        }

        $stmtMissing = $pdo->prepare($sqlMissing);
        $stmtMissing->execute($params);
        $missingRows = $stmtMissing->fetchAll(PDO::FETCH_COLUMN);

        foreach ($missingRows as $missingEidRaw) {
            $missingEid = (int)$missingEidRaw;
            if (!$missingEid) continue;

            // build user selections for this missing election
            $voteByUserForElectionStmt->execute([':eid' => $missingEid, ':uid' => $uid]);
            $voteRows = $voteByUserForElectionStmt->fetchAll(PDO::FETCH_ASSOC);

            $userSelections = [];
            $hasVoted = false;
            if ($voteRows && count($voteRows) > 0) {
                $hasVoted = true;
                foreach ($voteRows as $vr) {
                    $pos = $vr['Position_Name'] ?? ('Position ' . ($vr['Position_ID'] ?? ''));
                    $userSelections[$pos] = $vr['FullName'] ?? null;
                }
            }

            // attempt to build results if candidate rows still exist for that election
            $results = [];
            $resultsStmt->execute([':eid' => $missingEid]);
            $resRows = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($resRows) {
                foreach ($resRows as $rr) {
                    $results[] = [
                        'name' => $rr['FullName'],
                        'position' => $rr['Position_Name'] ?? ('Position ' . ($rr['Position_ID'] ?? '')),
                        'party' => $rr['PartyList'] ?? '',
                        'votes' => (int)$rr['votes']
                    ];
                }
            }

            // add a synthetic "deleted" record so frontend can show the user's saved vote
            $elections[] = [
                'id' => null,
                'deleted_election_id' => $missingEid,
                'code' => null,
                'name' => "Deleted Election (ID {$missingEid})",
                'details' => 'This election record was removed from the system, but your vote is preserved.',
                'date' => '',
                'organization' => '',
                'status' => 'deleted',
                'voted' => (bool)$hasVoted,
                'results' => $results,
                'userSelections' => (object)$userSelections,
                'raw' => ['deleted' => true, 'election_id' => $missingEid]
            ];
        }
    }

    // return combined list
    echo json_encode(['success' => true, 'data' => $elections], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'error' => 'Server error', 'detail' => $e->getMessage()]);
    exit;
}

/* helpers */
function formatDateRange($start, $end) {
    if (!$start && !$end) return '';
    $s = $start ? date('M d, Y H:i', strtotime($start)) : '';
    $e = $end ? date('M d, Y H:i', strtotime($end)) : '';
    return trim($s . ($s && $e ? ' — ' : '') . $e);
}
function mapStatusToLower($status) {
    if (!$status) return 'upcoming';
    $s = strtolower($status);
    if (strpos($s, 'ongoing') !== false || strpos($s, 'active') !== false) return 'ongoing';
    if (strpos($s, 'end') !== false || strpos($s, 'ended') !== false || strpos($s, 'completed') !== false) return 'completed';
    return 'upcoming';
}
