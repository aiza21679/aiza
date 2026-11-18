<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* DB CONFIG - update only if different on your system */
$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed', 'detail' => $ex->getMessage()]);
    exit;
}

/* Helper: safe column detection for party_lists table */
function detectPartyListColumn(PDO $pdo, $tableName = 'party_lists') {
    // list of candidate column names that commonly store a party list display name
    $candidates = ['list_name','name','party_list','partyname','party_name','list','title'];

    try {
        // If the table doesn't exist, bail
        $check = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
        if ($check->rowCount() === 0) return null;

        $cols = $pdo->query("SHOW COLUMNS FROM `$tableName`")->fetchAll();
        $existing = array_column($cols, 'Field');

        foreach ($candidates as $c) {
            if (in_array($c, $existing)) return $c;
        }

        // fallback: return first text-like column if nothing matched
        foreach ($cols as $col) {
            $type = strtolower($col['Type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false || strpos($type, 'varchar') !== false) {
                return $col['Field'];
            }
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

/* 1) Votes in last hour */
$votesLastHour = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `vote` WHERE `Timestamp` >= (NOW() - INTERVAL 1 HOUR)");
    $stmt->execute();
    $votesLastHour = (int)($stmt->fetch()['c'] ?? 0);
} catch (Exception $e) {
    $votesLastHour = 0;
}

/* 2) Optional issues count */
$issuesReported = 0;
try {
    $tbl = $pdo->query("SHOW TABLES LIKE 'issues'")->fetch();
    if ($tbl) {
        // try common date columns
        $colInfo = $pdo->query("SHOW COLUMNS FROM `issues`")->fetchAll();
        $cols = array_column($colInfo, 'Field');
        $dateCol = null;
        foreach (['created_at','Timestamp','reported_at','created','date'] as $c) {
            if (in_array($c, $cols)) { $dateCol = $c; break; }
        }
        if ($dateCol) {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `issues` WHERE `$dateCol` >= (NOW() - INTERVAL 1 HOUR)");
            $stmt->execute();
            $issuesReported = (int)($stmt->fetch()['c'] ?? 0);
        }
    }
} catch (Exception $e) {
    $issuesReported = 0;
}

/* 3) Fetch elections and compute party results */
$elections = [];
try {
    $sql = "
      SELECT 
        Election_ID,
        Title,
        Organization,
        Start_Date,
        End_Date,
        Total_Voters,
        Status
      FROM `election`
      ORDER BY Start_Date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // detect party_lists column name once
    $partyListCol = detectPartyListColumn($pdo, 'party_lists'); // may be null

    foreach ($rows as $r) {
        $eid = $r['Election_ID'];

        // votes cast for election
        try {
            $s = $pdo->prepare("SELECT COUNT(*) AS c FROM `vote` WHERE `Election_ID` = :eid");
            $s->execute([':eid' => $eid]);
            $votesCast = (int)($s->fetch()['c'] ?? 0);
        } catch (Exception $ex) {
            $votesCast = 0;
        }

        // party results by joining candidate
// Example: build partyResults by counting votes per party (adjust table/column names)
$partyResults = []; // associative

$sqlVotes = "SELECT pl.name AS party, COUNT(v.Vote_ID) AS votes
             FROM vote v
             JOIN candidate c ON v.Candidate_ID = c.Candidate_ID
             LEFT JOIN party_lists pl ON pl.id = c.PartyList
             WHERE v.Election_ID = :eid
             GROUP BY pl.name";
$stmt2 = $pdo->prepare($sqlVotes);
$stmt2->execute([':eid' => $eid]);
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $partyName = $r['party'] ?: 'Independent';
    $partyResults[$partyName] = ['votes' => (int)$r['votes']];
}

// then include $partyResults in your response for that election
$election['partyResults'] = $partyResults;


        // party lists from election_party_list -> party_lists if exists
        $partyLists = [];
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'election_party_list'")->fetch();
            if ($check) {
                if ($partyListCol !== null) {
                    // use alias pl.$partyListCol
                    $q = "
                      SELECT pl.`" . str_replace("`","", $partyListCol) . "` AS list_name
                      FROM `election_party_list` epl
                      JOIN `party_lists` pl ON pl.id = epl.party_list_id
                      WHERE epl.election_id = :eid
                    ";
                    $s2 = $pdo->prepare($q);
                    $s2->execute([':eid' => $eid]);
                    $plrows = $s2->fetchAll();
                    foreach ($plrows as $prr) {
                        $val = trim($prr['list_name'] ?? '');
                        if ($val !== '' && !in_array($val, $partyLists)) $partyLists[] = $val;
                    }
                } else {
                    // no detected column: try selecting any VARCHAR/text column (first) from party_lists
                    $cols = $pdo->query("SHOW COLUMNS FROM `party_lists`")->fetchAll();
                    if (!empty($cols)) {
                        // take first text-like column (besides id)
                        $pick = null;
                        foreach ($cols as $cinfo) {
                            if (in_array($cinfo['Field'], ['id','ID','party_list_id'])) continue;
                            $typ = strtolower($cinfo['Type']);
                            if (strpos($typ,'char')!==false || strpos($typ,'text')!==false || strpos($typ,'varchar')!==false) { $pick = $cinfo['Field']; break; }
                        }
                        if ($pick) {
                            $q = "SELECT `$pick` as list_name FROM `election_party_list` epl JOIN `party_lists` pl ON pl.id = epl.party_list_id WHERE epl.election_id = :eid";
                            $s2 = $pdo->prepare($q);
                            $s2->execute([':eid' => $eid]);
                            foreach ($s2->fetchAll() as $prr) {
                                $val = trim($prr['list_name'] ?? '');
                                if ($val !== '' && !in_array($val, $partyLists)) $partyLists[] = $val;
                            }
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            // ignore party list fetch errors
        }

        // ensure parties that have votes are included
        foreach ($partyResults as $pname => $_) {
            if (!in_array($pname, $partyLists)) $partyLists[] = $pname;
        }

        $elections[] = [
            'id' => $eid,
            'name' => $r['Title'] ?? '',
            'organization' => $r['Organization'] ?? '',
            'startDate' => $r['Start_Date'] ?? null,
            'endDate' => $r['End_Date'] ?? null,
            'totalVoters' => (int)($r['Total_Voters'] ?? 0),
            'status' => isset($r['Status']) ? strtolower($r['Status']) : '',
            'votesCast' => $votesCast,
            'partyLists' => $partyLists,
            'partyResults' => $partyResults
        ];
    }

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch elections', 'detail' => $ex->getMessage()]);
    exit;
}

/* 4) compute overall leading party */
$partyTotals = [];
foreach ($elections as $ev) {
    foreach (($ev['partyResults'] ?? []) as $pname => $info) {
        $votes = (int)($info['votes'] ?? 0);
        if (!isset($partyTotals[$pname])) $partyTotals[$pname] = 0;
        $partyTotals[$pname] += $votes;
    }
}
$leadingParty = '';
if (!empty($partyTotals)) {
    arsort($partyTotals);
    $leadingParty = array_key_first($partyTotals);
}

/* 5) return JSON */
echo json_encode([
    'success' => true,
    'timestamp' => date('c'),
    'votesLastHour' => $votesLastHour,
    'issuesReported' => $issuesReported,
    'leadingParty' => $leadingParty,
    'elections' => $elections
], JSON_PRETTY_PRINT);
