<?php
// student/get_results.php
// Returns per-election positions, candidates (with votes), party_totals, votes_cast and turnout_percent
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'db_connect','detail'=>$ex->getMessage()]);
    exit;
}

$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
if ($electionId <= 0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'invalid_election_id']);
    exit;
}

// helpers
function build_avatar_url($avatar) {
    if (empty($avatar)) return '../assests/default-avatar.jpg';
    if (preg_match('#^https?://#i', $avatar)) return $avatar;
    return $avatar;
}

// 1) fetch election row (for Total_Voters, title)
try {
    $s = $pdo->prepare("SELECT * FROM election WHERE Election_ID = :eid LIMIT 1");
    $s->execute([':eid' => $electionId]);
    $election = $s->fetch();
    if (!$election) {
        http_response_code(404);
        echo json_encode(['status'=>'error','error'=>'election_not_found']);
        exit;
    }
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'db_error','detail'=>$ex->getMessage()]);
    exit;
}

// 2) collect vote rows for this election
try {
    $vStmt = $pdo->prepare("SELECT * FROM vote WHERE Election_ID = :eid");
    $vStmt->execute([':eid' => $electionId]);
    $voteRows = $vStmt->fetchAll();
} catch (Exception $ex) {
    $voteRows = [];
}

// 3) build candidate vote counts
$votesCast = 0;                // distinct votes count (count rows or distinct Student_ID depending on your schema)
$voteCountsByCandidate = [];   // candidate_id => votes
$selectionsList = [];          // all candidate IDs referenced by selection JSON

foreach ($voteRows as $vr) {
    $votesCast++; // count each vote row as one cast (manage-elections uses votes.length for votesCast)
    // case A: single candidate column
    if (!empty($vr['Candidate_ID'])) {
        $cid = (int)$vr['Candidate_ID'];
        if ($cid > 0) $voteCountsByCandidate[$cid] = ($voteCountsByCandidate[$cid] ?? 0) + 1;
    }
    // case B: selections column (JSON array or object)
    if (!empty($vr['selections'])) {
        $sel = json_decode($vr['selections'], true);
        if (is_array($sel)) {
            // selections may be an object (position->candidate) or an array of candidate ids
            if (array_keys($sel) !== range(0, count($sel)-1)) {
                // associative -> values are candidate ids
                $vals = array_values($sel);
            } else {
                $vals = $sel;
            }
            foreach ($vals as $val) {
                $cid = (int)$val;
                if ($cid > 0) {
                    $voteCountsByCandidate[$cid] = ($voteCountsByCandidate[$cid] ?? 0) + 1;
                    $selectionsList[$cid] = $cid;
                }
            }
        }
    }
    // case C: maybe vote stores candidate IDs in a column named candidate_id or CandidateId
    if (!empty($vr['candidate_id']) && empty($vr['Candidate_ID'])) {
        $cid = (int)$vr['candidate_id'];
        if ($cid>0) $voteCountsByCandidate[$cid] = ($voteCountsByCandidate[$cid] ?? 0) + 1;
    }
}

// 4) load candidates relevant to this election:
// prefer candidate rows with Election_ID = this election OR candidate ids referenced by votes
$candidateRows = [];
try {
    // fetch by Election_ID first
    $cstmt = $pdo->prepare("SELECT * FROM candidate WHERE Election_ID = :eid");
    $cstmt->execute([':eid' => $electionId]);
    $candidateRows = $cstmt->fetchAll();

    // if no candidate rows by election, but votes referenced candidates by id, fetch those ids
    if (count($candidateRows) === 0 && count($voteCountsByCandidate) > 0) {
        $ids = array_keys($voteCountsByCandidate);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt2 = $pdo->prepare("SELECT * FROM candidate WHERE Candidate_ID IN ($placeholders)");
        $stmt2->execute($ids);
        $candidateRows = $stmt2->fetchAll();
    } else {
        // there may be more candidates referenced by votes but not in Election_ID
        if (count($voteCountsByCandidate) > 0) {
            $ids = array_diff(array_keys($voteCountsByCandidate), array_map(function($r){ return (int)$r['Candidate_ID']; }, $candidateRows));
            if (count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt3 = $pdo->prepare("SELECT * FROM candidate WHERE Candidate_ID IN ($placeholders)");
                $stmt3->execute(array_values($ids));
                $extra = $stmt3->fetchAll();
                foreach ($extra as $x) $candidateRows[] = $x;
            }
        }
    }
} catch (Exception $ex) {
    // ignore candidate load errors; continue with what we have
}

// build map candidate_id -> candidate
$candidateMap = [];
foreach ($candidateRows as $c) {
    $cid = (int)($c['Candidate_ID'] ?? $c['id'] ?? 0);
    if ($cid <= 0) continue;
    $candidateMap[$cid] = [
        'id' => $cid,
        'name' => $c['FullName'] ?? $c['Fullname'] ?? $c['Full_Name'] ?? '',
        'party' => $c['PartyList'] ?? $c['partyList'] ?? $c['Party'] ?? $c['party'] ?? 'Independent',
        'avatar' => $c['Avatar'] ?? $c['avatar'] ?? '',
        'position_id' => $c['Position_ID'] ?? $c['position_id'] ?? null,
        'position_name' => null,
        'platform' => $c['Platform'] ?? $c['platform'] ?? ''
    ];
}

// 5) resolve position names: try join position table
$positionMap = []; // position_id => name
try {
    $posRows = $pdo->query("SELECT Position_ID, Position_Name FROM position")->fetchAll();
    foreach ($posRows as $pr) {
        $positionMap[(int)$pr['Position_ID']] = $pr['Position_Name'];
    }
} catch (Exception $ex) {
    // ignore; positionMap stays empty
}
foreach ($candidateMap as $cid => &$c) {
    if (!empty($c['position_id']) && isset($positionMap[(int)$c['position_id']])) {
        $c['position_name'] = $positionMap[(int)$c['position_id']];
    } else {
        $c['position_name'] = $c['position_name'] ?: 'Unknown';
    }
}
unset($c);

// 6) prepare positions collection grouping candidates by position_name
$positions = [];
foreach ($candidateMap as $cid => $c) {
    $posName = $c['position_name'] ?? 'All Candidates';
    if (!isset($positions[$posName])) $positions[$posName] = [];
    $positions[$posName][] = $c;
}

// 7) compute candidate votes, percentages per position
$responsePositions = [];
foreach ($positions as $posName => $candList) {
    $totalForPosition = 0;
    foreach ($candList as $cand) {
        $cid = (int)$cand['id'];
        $v = $voteCountsByCandidate[$cid] ?? 0;
        $totalForPosition += $v;
    }

    $cPayload = [];
    foreach ($candList as $cand) {
        $cid = (int)$cand['id'];
        $votes = $voteCountsByCandidate[$cid] ?? 0;
        $pct = $totalForPosition > 0 ? round(($votes / $totalForPosition) * 100, 2) : 0.00;
        $cPayload[] = [
            'id' => $cid,
            'name' => $cand['name'],
            'party' => $cand['party'],
            'avatar' => $cand['avatar'],
            'avatar_url' => build_avatar_url($cand['avatar']),
            'platform' => $cand['platform'] ?? '',
            'votes' => $votes,
            'percentage' => $pct
        ];
    }
    // sort by votes desc
    usort($cPayload, function($a,$b){ return ($b['votes'] ?? 0) - ($a['votes'] ?? 0); });

    $responsePositions[] = [
        'position' => $posName,
        'total_votes' => $totalForPosition,
        'candidates' => $cPayload
    ];
}

// 8) compute party_totals by summing candidate parties from vote rows (join-like)
$partyTotals = [];
try {
    $pstmt = $pdo->prepare("
        SELECT COALESCE(NULLIF(TRIM(c.PartyList),''), 'Independent') AS party, COUNT(v.Vote_ID) AS votes
        FROM vote v
        LEFT JOIN candidate c ON c.Candidate_ID = v.Candidate_ID
        WHERE v.Election_ID = :eid
        GROUP BY COALESCE(NULLIF(TRIM(c.PartyList),''), 'Independent')
        ORDER BY votes DESC
    ");
    $pstmt->execute([':eid' => $electionId]);
    $partRows = $pstmt->fetchAll();
    foreach ($partRows as $pr) {
        $partyTotals[] = ['party' => $pr['party'], 'votes' => (int)$pr['votes']];
    }
} catch (Exception $ex) {
    // fallback: compute from candidateMap and voteCountsByCandidate
    foreach ($voteCountsByCandidate as $cid => $v) {
        $party = ($candidateMap[$cid]['party'] ?? 'Independent');
        $partyTotals[$party] = ($partyTotals[$party] ?? 0) + $v;
    }
    // convert to array
    $tmp = [];
    foreach ($partyTotals as $k=>$v) $tmp[] = ['party'=>$k,'votes'=>$v];
    $partyTotals = $tmp;
}

// 9) turnout percent: use election.Total_Voters if present, and votesCast (we counted vote rows as votes cast)
$totalVotersField = (isset($election['Total_Voters']) && (int)$election['Total_Voters'] > 0) ? (int)$election['Total_Voters'] : null;
$turnoutPercent = null;
if ($totalVotersField !== null && $totalVotersField > 0) {
    $turnoutPercent = round(($votesCast / $totalVotersField) * 100, 2);
}

// 10) build response
$out = [
    'status' => 'success',
    'election' => [
        'id' => (int)$election['Election_ID'],
        'title' => $election['Title'] ?? '',
        'total_voters_field' => $totalVotersField,
        'votes_cast' => $votesCast,
        'turnout_percent' => $turnoutPercent
    ],
    'positions' => $responsePositions,
    'party_totals' => $partyTotals
];

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
exit;
