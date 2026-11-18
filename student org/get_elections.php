<?php
/**
 * getElectionStats.php  (matched to your eboto_db conventions from get_elections.php)
 *
 * Usage:
 *   getElectionStats.php?id=5        -> one election by Election_ID  (preferred)
 *   getElectionStats.php?org=IT      -> latest election of that organization
 *
 * Like your get_elections.php, it detects column-name variants automatically.
 * If something can't be matched, it tells you in the "warnings" array of the JSON.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';

/* Organization code -> names it might be stored as in the database */
$ORG_NAMES = [
  'IT'    => ['Information Technology'],
  'ESM'   => ['Energy Solution Management', 'Energy Systems and Management', 'Energy System and Management'],
  'MET'   => ['Manufacturing Engineering Technology'],
  'TCM'   => ['Technology Communication Management'],
  'NAME'  => ['Naval Architecture & Marine Engineering', 'Naval Architecture and Marine Engineering'],
  'CACHE' => ['The CACHE Organization', 'CACHE Organization', 'The Cache'],
  'USG'   => ['University Student Government'],
];

function fail($code, $msg, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $msg], $extra));
    exit;
}

/* ---------- inputs ---------- */
$idParam  = isset($_GET['id'])  ? trim($_GET['id'])  : null;
$orgParam = isset($_GET['org']) ? trim($_GET['org']) : null;

if (($idParam === null || $idParam === '') && ($orgParam === null || $orgParam === '')) {
    fail(400, 'Provide ?id=<Election_ID> or ?org=<code>, e.g. getElectionStats.php?org=IT');
}
if ($idParam !== null && $idParam !== '' && !preg_match('/^[A-Za-z0-9_\-]{1,50}$/', $idParam)) {
    fail(400, 'Invalid id');
}
if ($orgParam !== null && $orgParam !== '' && !preg_match('/^[A-Za-z0-9_\-& ]{1,80}$/', $orgParam)) {
    fail(400, 'Invalid org');
}

/* ---------- DB ---------- */
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    fail(500, 'DB connection failed');
}

/* ---------- helpers ---------- */
function tableName($pdo, $schema, array $variants) {
    $s = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = :s AND LOWER(TABLE_NAME) = LOWER(:t) LIMIT 1");
    foreach ($variants as $v) {
        $s->execute([':s' => $schema, ':t' => $v]);
        $found = $s->fetchColumn();
        if ($found) return $found;
    }
    return null;
}
function columnsOf($pdo, $schema, $table) {
    $s = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :s AND table_name = :t");
    $s->execute([':s' => $schema, ':t' => $table]);
    return $s->fetchAll(PDO::FETCH_COLUMN) ?: [];
}
function pick(array $cols, array $variants) {
    $lower = [];
    foreach ($cols as $c) $lower[strtolower($c)] = $c;
    foreach ($variants as $v) if (isset($lower[strtolower($v)])) return $lower[strtolower($v)];
    return null;
}
function inClause($prefix, array $vals, array &$params) {
    $ph = [];
    foreach (array_values($vals) as $i => $v) {
        $k = ":{$prefix}{$i}";
        $params[$k] = $v;
        $ph[] = $k;
    }
    return implode(',', $ph);
}
function upperUnique(array $vals) {
    $out = [];
    foreach ($vals as $v) {
        $v = strtoupper(trim((string)$v));
        if ($v !== '') $out[$v] = true;
    }
    return array_keys($out);
}
function statusFromDates($start, $end) {
    $now = time();
    $s = $start ? strtotime($start) : null;
    $e = $end ? strtotime($end) : null;
    if ($s && $now < $s) return 'Upcoming';
    if ($e && $now > $e) return 'Completed';
    return 'Active';
}

$warnings = [];

try {
    /* ===== 1) ELECTION ===== */
    $eTable = tableName($pdo, $dbName, ['elections', 'election']);
    if (!$eTable) fail(500, 'No election table found');
    $eCols = columnsOf($pdo, $dbName, $eTable);

    $eId     = pick($eCols, ['Election_ID', 'id', 'election_id', 'electionId']);
    $eTitle  = pick($eCols, ['Title', 'title', 'name', 'Name']);
    $eOrg    = pick($eCols, ['Organization', 'organization', 'organization_name', 'Organization_Name', 'Org_Code']);
    $eStart  = pick($eCols, ['Start_Date', 'start_date', 'startDate', 'start']);
    $eEnd    = pick($eCols, ['End_Date', 'end_date', 'endDate', 'end']);
    $eStatus = pick($eCols, ['Status', 'status']);
    $eTotal  = pick($eCols, ['Total_Voters', 'total_voters', 'totalVoters']);
    $eDesc   = pick($eCols, ['Description', 'description']);
    $eType   = pick($eCols, ['Election_Type', 'election_type', 'Type', 'type']);
    if (!$eId) fail(500, "Could not find the id column in `$eTable`");

    $params = [];
    if ($idParam !== null && $idParam !== '') {
        $sql = "SELECT * FROM `$eTable` WHERE `$eId` = :id LIMIT 1";
        $params[':id'] = $idParam;
    } else {
        if (!$eOrg) fail(500, "`$eTable` has no organization column, so ?org= can't be used. Use ?id= instead.");
        $code  = strtoupper($orgParam);
        $names = array_merge([$code], $ORG_NAMES[$code] ?? []);
        $in    = inClause('o', upperUnique($names), $params);
        $params[':pref'] = $code . ' -%';
        $order = $eStart ? "`$eStart` DESC, `$eId` DESC" : "`$eId` DESC";
        $sql = "SELECT * FROM `$eTable`
                WHERE UPPER(TRIM(`$eOrg`)) IN ($in) OR UPPER(`$eOrg`) LIKE :pref
                ORDER BY $order LIMIT 1";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $election = $stmt->fetch();
    if (!$election) {
        fail(404, $idParam ? "No election with id $idParam" : "No election found for $orgParam");
    }

    $electionId = $election[$eId];
    $orgValue   = $eOrg ? trim((string)$election[$eOrg]) : '';

    /* organization code + every name this org might be stored under */
    $code = $orgParam ? strtoupper($orgParam) : '';
    if ($code === '' && isset($ORG_NAMES[strtoupper($orgValue)])) $code = strtoupper($orgValue);
    $orgNames = upperUnique(array_merge([$orgValue, $code], $ORG_NAMES[$code] ?? []));

    $startVal = $eStart ? $election[$eStart] : null;
    $endVal   = $eEnd ? $election[$eEnd] : null;

    /* status: respect "draft", otherwise compute from the dates (same idea as get_elections.php) */
    $rawStatus = $eStatus ? strtolower((string)$election[$eStatus]) : '';
    if (strpos($rawStatus, 'draft') !== false) $status = 'Draft';
    else $status = statusFromDates($startVal, $endVal);

    /* ===== 2) CANDIDATES ===== */
    $cTable = tableName($pdo, $dbName, ['candidate', 'candidates']);
    $candidates = [];
    $voteCounts = [];     // candidate id => votes

    if (!$cTable) {
        $warnings[] = 'No candidate table found.';
    } else {
        $cCols   = columnsOf($pdo, $dbName, $cTable);
        $cId     = pick($cCols, ['Candidate_ID', 'candidate_id', 'id']);
        $cName   = pick($cCols, ['Full_Name', 'Candidate_Name', 'Name', 'FullName', 'name']);
        $cFirst  = pick($cCols, ['First_Name', 'firstname', 'first_name']);
        $cLast   = pick($cCols, ['Last_Name', 'lastname', 'last_name']);
        $cPos    = pick($cCols, ['Position', 'Position_Name', 'position']);
        $cPosId  = pick($cCols, ['Position_ID', 'position_id']);
        $cStatus = pick($cCols, ['Status', 'status']);
        $cElec   = pick($cCols, ['Election_ID', 'election_id', 'electionId']);
        $cOrg    = pick($cCols, ['Organization', 'Department', 'Org_Code', 'Course', 'Program', 'Org']);

        if (!$cId) {
            $warnings[] = "Candidate table `$cTable` has no id column.";
        } else {
            $nameExpr = $cName ? "c.`$cName`"
                      : (($cFirst && $cLast) ? "CONCAT_WS(' ', c.`$cFirst`, c.`$cLast`)" : "CAST(c.`$cId` AS CHAR)");
            if (!$cName && !($cFirst && $cLast)) $warnings[] = 'No candidate name column recognised; showing IDs.';

            $join = '';
            $posExpr = "''";
            if ($cPos) {
                $posExpr = "c.`$cPos`";
            } elseif ($cPosId) {
                $pTable = tableName($pdo, $dbName, ['position', 'positions']);
                if ($pTable) {
                    $pCols = columnsOf($pdo, $dbName, $pTable);
                    $pId   = pick($pCols, ['Position_ID', 'id']);
                    $pName = pick($pCols, ['Position_Name', 'position_name', 'name', 'Name']);
                    if ($pId && $pName) {
                        $join = "LEFT JOIN `$pTable` p ON p.`$pId` = c.`$cPosId`";
                        $posExpr = "p.`$pName`";
                    }
                }
            }
            if ($posExpr === "''") $warnings[] = 'Could not resolve candidate positions.';

            $where = []; $cParams = [];
            if ($cStatus) $where[] = "LOWER(c.`$cStatus`) = 'approved'";
            if ($cElec) {
                $where[] = "c.`$cElec` = :eid";
                $cParams[':eid'] = $electionId;
            } elseif ($cOrg && $orgNames) {
                $where[] = "UPPER(TRIM(c.`$cOrg`)) IN (" . inClause('co', $orgNames, $cParams) . ")";
            } else {
                $warnings[] = 'Candidates could not be filtered by election or organization; showing all approved candidates.';
            }
            $sql = "SELECT c.`$cId` AS cid, $nameExpr AS cname, $posExpr AS cpos
                    FROM `$cTable` c $join" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
                    ORDER BY cpos ASC, cname ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($cParams);
            foreach ($stmt->fetchAll() as $r) {
                $candidates[] = ['id' => (int)$r['cid'], 'name' => (string)$r['cname'], 'position' => (string)($r['cpos'] ?? ''), 'votes' => 0];
                $voteCounts[(int)$r['cid']] = 0;
            }
        }
    }

    /* ===== 3) VOTES ===== */
    $votesCast = 0;
    $vTable = tableName($pdo, $dbName, ['vote', 'votes']);
    if (!$vTable) {
        $warnings[] = 'No vote table found.';
    } else {
        $vCols  = columnsOf($pdo, $dbName, $vTable);
        $vElec  = pick($vCols, ['Election_ID', 'election_id', 'electionId', 'election']);
        $vCand  = pick($vCols, ['Candidate_ID', 'candidate_id']);
        $vSel   = pick($vCols, ['selections', 'Selections']);
        $vVoter = pick($vCols, ['Voter_ID', 'voter_id', 'Student_ID', 'student_id', 'Voter', 'voter']);

        if (!$vElec) {
            $warnings[] = "Vote table `$vTable` has no election column; votes can't be matched to this election.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `$vTable` WHERE `$vElec` = :eid");
            $stmt->execute([':eid' => $electionId]);
            $voters = [];
            $rowCount = 0;
            foreach ($stmt->fetchAll() as $vr) {
                $rowCount++;
                if ($vVoter && $vr[$vVoter] !== null && $vr[$vVoter] !== '') $voters[(string)$vr[$vVoter]] = true;

                if ($vCand && !empty($vr[$vCand])) {
                    $cid = (int)$vr[$vCand];
                    if (isset($voteCounts[$cid])) $voteCounts[$cid]++;
                } elseif ($vSel && !empty($vr[$vSel])) {
                    $sel = json_decode($vr[$vSel], true);
                    if (is_array($sel)) {
                        array_walk_recursive($sel, function ($cid) use (&$voteCounts) {
                            $cid = (int)$cid;
                            if (isset($voteCounts[$cid])) $voteCounts[$cid]++;
                        });
                    }
                }
            }
            $votesCast = $vVoter ? count($voters) : $rowCount;
            if (!$vCand && !$vSel) $warnings[] = "Vote table `$vTable` has no candidate_id or selections column; per-candidate votes are 0.";
        }
    }

    /* attach votes + per-position percentage */
    $posTotals = [];
    foreach ($candidates as &$c) {
        $c['votes'] = $voteCounts[$c['id']] ?? 0;
        $posTotals[$c['position']] = ($posTotals[$c['position']] ?? 0) + $c['votes'];
    }
    unset($c);
    foreach ($candidates as &$c) {
        $t = $posTotals[$c['position']] ?? 0;
        $c['percent'] = $t > 0 ? round(($c['votes'] / $t) * 100, 1) : 0;
    }
    unset($c);

    /* ===== 4) REGISTERED VOTERS ===== */
    $registered = 0;
    $registeredResolved = false;
    $voterTable = tableName($pdo, $dbName, ['voter', 'voters']);
    if ($voterTable) {
        $voCols = columnsOf($pdo, $dbName, $voterTable);
        $voOrg  = pick($voCols, ['Organization', 'Department', 'Org_Code', 'Course', 'Program', 'Org']);
        if ($voOrg && $orgNames) {
            $p = [];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$voterTable` WHERE UPPER(TRIM(`$voOrg`)) IN (" . inClause('vo', $orgNames, $p) . ")");
            $stmt->execute($p);
            $registered = (int)$stmt->fetchColumn();
            $registeredResolved = true;
        }
    }
    if (!$registeredResolved && $eTotal) {
        $registered = (int)$election[$eTotal];
        $registeredResolved = true;
    }
    if (!$registeredResolved) $warnings[] = 'Could not determine registered voters (no matching voter column or Total_Voters).';

    $turnout = $registered > 0 ? min(100, round(($votesCast / $registered) * 100)) : 0;

    /* ===== OUTPUT ===== */
    echo json_encode([
        'success'  => true,
        'election' => [
            'id'           => is_numeric($electionId) ? (int)$electionId : $electionId,
            'title'        => $eTitle ? $election[$eTitle] : '',
            'description'  => $eDesc ? $election[$eDesc] : '',
            'status'       => $status,
            'start_date'   => $startVal,
            'end_date'     => $endVal,
            'type'         => $eType ? $election[$eType] : '',
            'organization' => $orgValue,
        ],
        'stats' => [
            'registered_voters' => $registered,
            'candidates'        => count($candidates),
            'votes_cast'        => $votesCast,
            'turnout'           => $turnout,
        ],
        'candidates' => $candidates,
        'warnings'   => $warnings,
    ]);

} catch (Throwable $e) {
    error_log('getElectionStats error: ' . $e->getMessage());
    fail(500, 'Server error', ['error' => $e->getMessage()]);
}
