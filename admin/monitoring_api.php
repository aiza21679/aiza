<?php
/**
 * monitoring_api.php  (v2 - matched to your eboto_db schema)
 *
 * Everything monitoring.php needs, read live from the database:
 *   - every non-draft election, registered voters, ballots cast, turnout
 *   - candidates + per-candidate votes grouped by position
 *   - issues, timeline, recent (anonymous) activity
 *
 * Matches your real tables:
 *   election.Org_ID            -> student_organization.Org_ID
 *   organization_voters.org_id -> registered voters (only status = 'Active')
 *   candidate.Election_ID      -> the election (falls back to candidate.Org_ID when Election_ID is empty)
 *   candidate.Position_ID      -> position
 *
 * Anything it cannot match is reported in the "warnings" array of the JSON.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/* Only a logged-in session may read this (same key getAdmin.php checks) */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';

$ORG_NAMES = [
  'IT'    => ['Information Technology'],
  'ESM'   => ['Energy Solution Management', 'Energy Systems and Management', 'Energy System and Management'],
  'MET'   => ['Manufacturing Engineering Technology'],
  'TCM'   => ['Technology Communication Management'],
  'NAME'  => ['Naval Architecture & Marine Engineering', 'Naval Architecture and Marine Engineering'],
  'CACHE' => ['The CACHE Organization', 'CACHE Organization', 'The Cache'],
  'USG'   => ['University Student Government'],
];
/* program names used by candidate / voter records (same map as get_candidates.php) */
$DEPT_PROGRAMS = [
  'IT'   => ['BS Information Technology', 'BS Information Technology (Day)'],
  'ESM'  => ['BS ESM', 'BS Energy Management', 'BS Energy Engineering', 'BS Energy Technology'],
  'TCM'  => ['BS TCM'],
  'MET'  => ['BS MET', 'Mechanical Engineering Technology'],
  'NAME' => ['BS NAME'],
];
$EXCLUDED_CANDIDATE_STATUS = ['pending', 'rejected', 'denied', 'disqualified', 'withdrawn', 'inactive'];

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

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
function orgCode($raw, $ORG_NAMES) {
    $v = strtoupper(trim((string)$raw));
    if ($v === '') return '';
    if (isset($ORG_NAMES[$v])) return $v;
    foreach ($ORG_NAMES as $code => $names) {
        foreach ($names as $n) if (strtoupper($n) === $v) return $code;
    }
    if (preg_match('/^([A-Z]+)\s*-/', $v, $m) && isset($ORG_NAMES[$m[1]])) return $m[1];
    return trim((string)$raw);
}
/* SQL condition: does text column $col belong to organization $code? */
function orgMatchSql($code, $col, array &$params, $pfx, $ORG_NAMES, $DEPT) {
    $exact = [$code];
    foreach (($ORG_NAMES[$code] ?? []) as $n) $exact[] = $n;
    foreach (($DEPT[$code] ?? []) as $n) $exact[] = $n;
    $conds = []; $i = 0;
    foreach (array_unique(array_map('strtoupper', $exact)) as $v) {
        $k = ":{$pfx}e{$i}"; $i++;
        $params[$k] = $v;
        $conds[] = "UPPER(TRIM($col)) = $k";
    }
    foreach (($ORG_NAMES[$code] ?? []) as $n) {
        $k = ":{$pfx}l{$i}"; $i++;
        $params[$k] = '%' . strtoupper($n) . '%';
        $conds[] = "UPPER($col) LIKE $k";
    }
    return '(' . implode(' OR ', $conds) . ')';
}
function statusFromDates($start, $end, $now) {
    $s = $start ? strtotime($start) : null;
    $e = $end ? strtotime($end) : null;
    if ($s && $now < $s) return 'upcoming';
    if ($e && $now > $e) return 'completed';
    return 'active';
}

$warnings = [];

try {
    /* "now" in the DATABASE's own clock, so dates / timestamps always compare correctly */
    $dbNow = strtotime((string)$pdo->query("SELECT NOW()")->fetchColumn());

    /* ===== election table ===== */
    $eTable = tableName($pdo, $dbName, ['election', 'elections']);
    if (!$eTable) fail(500, 'No election table found in ' . $dbName);
    $eCols  = columnsOf($pdo, $dbName, $eTable);
    $eId    = pick($eCols, ['Election_ID', 'id', 'election_id', 'electionId']);
    $eTitle = pick($eCols, ['Title', 'title', 'name', 'Name']);
    $eOrg   = pick($eCols, ['Organization', 'organization', 'organization_name', 'Organization_Name', 'Org_Code']);
    $eOrgId = pick($eCols, ['Org_ID', 'org_id']);
    $eStart = pick($eCols, ['Start_Date', 'start_date', 'startDate', 'start']);
    $eEnd   = pick($eCols, ['End_Date', 'end_date', 'endDate', 'end']);
    $eStat  = pick($eCols, ['Status', 'status']);
    $eTotal = pick($eCols, ['Total_Voters', 'total_voters', 'totalVoters']);
    if (!$eId || !$eTitle) fail(500, "Could not find id/title columns in `$eTable`");

    /* ===== student_organization (Org_ID -> name) ===== */
    $oTable = tableName($pdo, $dbName, ['student_organization']);
    $oId = $oName = null;
    if ($oTable) {
        $oc = columnsOf($pdo, $dbName, $oTable);
        $oId = pick($oc, ['Org_ID', 'id']);
        $oName = pick($oc, ['Org_Name', 'name']);
        if (!$oId || !$oName) $oTable = null;
    }

    /* ===== organization_voters (registered voters per organization) ===== */
    $ovTable = tableName($pdo, $dbName, ['organization_voters']);
    $ovOrg = $ovStat = null;
    if ($ovTable) {
        $ovc = columnsOf($pdo, $dbName, $ovTable);
        $ovOrg  = pick($ovc, ['org_id', 'Org_ID']);
        $ovStat = pick($ovc, ['status', 'Status']);
    }

    /* ===== vote table ===== */
    $vTable = tableName($pdo, $dbName, ['vote', 'votes']);
    $vElec = $vCand = $vSel = $vVoter = $vTime = null;
    if ($vTable) {
        $vCols  = columnsOf($pdo, $dbName, $vTable);
        $vElec  = pick($vCols, ['Election_ID', 'election_id', 'electionId', 'election']);
        $vCand  = pick($vCols, ['Candidate_ID', 'candidate_id']);
        $vSel   = pick($vCols, ['selections', 'Selections']);
        $vVoter = pick($vCols, ['Voter_ID', 'voter_id', 'Student_ID', 'student_id', 'Voter', 'voter']);
        $vTime  = pick($vCols, ['Timestamp', 'timestamp', 'Voted_At', 'voted_at', 'Date_Voted', 'Vote_Date', 'created_at']);
        if (!$vElec) { $warnings[] = "Vote table `$vTable` has no election column."; }
    } else {
        $warnings[] = 'No vote table found.';
    }

    /* ===== old voter table (only used as a fallback) ===== */
    $voTable = tableName($pdo, $dbName, ['voter', 'voters']);
    $voOrg = null;
    if ($voTable) $voOrg = pick(columnsOf($pdo, $dbName, $voTable), ['Organization', 'Department', 'Org_Code', 'Course', 'Program', 'Org']);

    /* ===== candidate (+ position) tables ===== */
    $cTable = tableName($pdo, $dbName, ['candidate', 'candidates']);
    $cId = $cName = $cPos = $cPosId = $cStat = $cElec = $cProg = $cOrgId = null;
    $pTable = $pId = $pName = null;
    if ($cTable) {
        $cCols  = columnsOf($pdo, $dbName, $cTable);
        $cId    = pick($cCols, ['Candidate_ID', 'candidate_id', 'id']);
        $cName  = pick($cCols, ['FullName', 'Full_Name', 'Candidate_Name', 'Name', 'name']);
        $cPos   = pick($cCols, ['Position', 'Position_Name', 'position']);
        $cPosId = pick($cCols, ['Position_ID', 'position_id']);
        $cStat  = pick($cCols, ['Status', 'status']);
        $cElec  = pick($cCols, ['Election_ID', 'election_id', 'electionId']);
        $cProg  = pick($cCols, ['Program', 'Course', 'Department']);
        $cOrgId = pick($cCols, ['Org_ID', 'org_id']);
        if (!$cPos && $cPosId) {
            $pTable = tableName($pdo, $dbName, ['position', 'positions']);
            if ($pTable) {
                $pc = columnsOf($pdo, $dbName, $pTable);
                $pId = pick($pc, ['Position_ID', 'id']);
                $pName = pick($pc, ['Position_Name', 'position_name', 'name', 'Name']);
                if (!$pId || !$pName) $pTable = null;
            }
        }
    } else {
        $warnings[] = 'No candidate table found.';
    }

    /* ===== optional issues table ===== */
    $iTable = tableName($pdo, $dbName, ['issues', 'issue']);
    $iElec = $iMsg = $iStat = null;
    if ($iTable) {
        $ic = columnsOf($pdo, $dbName, $iTable);
        $iElec = pick($ic, ['Election_ID', 'election_id']);
        $iMsg  = pick($ic, ['Description', 'description', 'Message', 'message', 'Title', 'title', 'Issue', 'issue']);
        $iStat = pick($ic, ['Status', 'status']);
    }

    /* ===== elections ===== */
    $order = $eStart ? "`$eStart` DESC, `$eId` DESC" : "`$eId` DESC";
    $rows = $pdo->query("SELECT * FROM `$eTable` ORDER BY $order")->fetchAll();

    $out = [];
    $titles = [];
    $totalBallots = 0; $activeCount = 0; $issuesTotal = 0; $turnoutSum = 0; $turnoutN = 0;
    $unfilterableWarned = false;

    foreach ($rows as $r) {
        $id    = $r[$eId];
        $start = $eStart ? $r[$eStart] : null;
        $end   = $eEnd ? $r[$eEnd] : null;
        $raw   = strtolower((string)($eStat ? $r[$eStat] : ''));
        $status = (strpos($raw, 'draft') !== false) ? 'draft' : statusFromDates($start, $end, $dbNow);
        if ($status === 'draft') continue;               // drafts are not monitored

        $orgRaw = $eOrg ? (string)$r[$eOrg] : '';
        $code   = $eOrg ? orgCode($orgRaw, $ORG_NAMES) : '';
        $title  = (string)$r[$eTitle];
        $titles[(string)$id] = $title;

        /* --- which organization record (Org_ID) does this election belong to? --- */
        $orgIdVal = null;
        if ($eOrgId && !empty($r[$eOrgId])) {
            $orgIdVal = (int)$r[$eOrgId];
        } elseif ($oTable) {
            $nm = [];
            foreach ([$orgRaw, $code] as $v) { $v = strtoupper(trim((string)$v)); if ($v !== '') $nm[$v] = true; }
            foreach (($ORG_NAMES[$code] ?? []) as $v) $nm[strtoupper($v)] = true;
            if ($nm) {
                $ph = []; $pp = []; $i = 0;
                foreach (array_keys($nm) as $v) { $k = ":on$i"; $i++; $pp[$k] = $v; $ph[] = $k; }
                $s = $pdo->prepare("SELECT `$oId` FROM `$oTable` WHERE UPPER(TRIM(`$oName`)) IN (" . implode(',', $ph) . ") LIMIT 1");
                $s->execute($pp);
                $f = $s->fetchColumn();
                if ($f) $orgIdVal = (int)$f;
            }
        }

        /* --- registered voters: ACTIVE members of the organization --- */
        $registered = 0; $regResolved = false;
        if ($ovTable && $ovOrg && $orgIdVal) {
            $q = "SELECT COUNT(*) FROM `$ovTable` WHERE `$ovOrg` = :o" . ($ovStat ? " AND LOWER(`$ovStat`) = 'active'" : '');
            $s = $pdo->prepare($q);
            $s->execute([':o' => $orgIdVal]);
            $registered = (int)$s->fetchColumn();
            $regResolved = true;
        }
        if (!$regResolved) {
            if ($voTable && $voOrg && $code !== '') {
                $p = [];
                $cond = orgMatchSql($code, "`$voOrg`", $p, 'vo', $ORG_NAMES, $DEPT_PROGRAMS);
                $s = $pdo->prepare("SELECT COUNT(*) FROM `$voTable` WHERE $cond");
                $s->execute($p);
                $registered = (int)$s->fetchColumn();
            }
            if ($registered === 0 && $eTotal) $registered = (int)$r[$eTotal];
        }

        /* --- ballots / last activity --- */
        $ballots = 0; $firstVote = null; $lastVote = null; $lastAgo = null;
        $candVotes = [];
        if ($vTable && $vElec) {
            $sel = ['COUNT(*) AS n'];
            if ($vVoter) $sel[] = "COUNT(DISTINCT `$vVoter`) AS voters";
            if ($vTime) {
                $sel[] = "MIN(`$vTime`) AS first_t";
                $sel[] = "MAX(`$vTime`) AS last_t";
                $sel[] = "TIMESTAMPDIFF(SECOND, MAX(`$vTime`), NOW()) AS ago";
            }
            $s = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM `$vTable` WHERE `$vElec` = :e");
            $s->execute([':e' => $id]);
            $a = $s->fetch();
            $n = (int)($a['n'] ?? 0);
            $ballots = $n;
            if ($vVoter && isset($a['voters']) && (int)$a['voters'] > 0) $ballots = (int)$a['voters'];
            if ($vTime) {
                $firstVote = $a['first_t'] ?? null;
                $lastVote  = $a['last_t'] ?? null;
                $lastAgo   = (isset($a['ago']) && $a['ago'] !== null) ? max(0, (int)$a['ago']) : null;
            }
            if ($n > 0) {
                if ($vCand) {
                    $s = $pdo->prepare("SELECT `$vCand` AS cid, COUNT(*) AS c FROM `$vTable` WHERE `$vElec` = :e GROUP BY `$vCand`");
                    $s->execute([':e' => $id]);
                    foreach ($s->fetchAll() as $vr) $candVotes[(int)$vr['cid']] = (int)$vr['c'];
                } elseif ($vSel) {
                    $s = $pdo->prepare("SELECT `$vSel` AS sel FROM `$vTable` WHERE `$vElec` = :e");
                    $s->execute([':e' => $id]);
                    foreach ($s->fetchAll() as $vr) {
                        $j = json_decode((string)$vr['sel'], true);
                        if (is_array($j)) {
                            array_walk_recursive($j, function ($cid) use (&$candVotes) {
                                $cid = (int)$cid;
                                if ($cid > 0) $candVotes[$cid] = ($candVotes[$cid] ?? 0) + 1;
                            });
                        }
                    }
                }
            }
        }
        $turnout = $registered > 0 ? min(100, (int)round(($ballots / $registered) * 100)) : 0;

        /* --- candidates --- */
        $cands = []; $positions = []; $pending = 0; $eligible = 0;
        if ($cTable && $cId) {
            $join = ''; $posExpr = "''"; $where = []; $cp = [];
            if ($cPos) $posExpr = "c.`$cPos`";
            elseif ($cPosId && $pTable) { $join .= " LEFT JOIN `$pTable` p ON p.`$pId` = c.`$cPosId`"; $posExpr = "p.`$pName`"; }

            if ($cElec && $cOrgId && $orgIdVal) {
                /* the candidate is in this election, OR has no election yet and belongs to this organization */
                $where[] = "(c.`$cElec` = :eid OR ((c.`$cElec` IS NULL OR c.`$cElec` = 0) AND c.`$cOrgId` = :oid))";
                $cp[':eid'] = $id; $cp[':oid'] = $orgIdVal;
            } elseif ($cElec) {
                $where[] = "c.`$cElec` = :eid"; $cp[':eid'] = $id;
            } elseif ($cOrgId && $orgIdVal) {
                $where[] = "c.`$cOrgId` = :oid"; $cp[':oid'] = $orgIdVal;
            } elseif ($code !== '' && isset($DEPT_PROGRAMS[$code]) && $cProg) {
                $where[] = orgMatchSql($code, "c.`$cProg`", $cp, 'cp', $ORG_NAMES, $DEPT_PROGRAMS);
            }

            if ($where) {
                $nameExpr = $cName ? "c.`$cName`" : "CAST(c.`$cId` AS CHAR)";
                $statExpr = $cStat ? "c.`$cStat`" : "''";
                try {
                    $s = $pdo->prepare("SELECT c.`$cId` AS cid, $nameExpr AS cname, $posExpr AS cpos, $statExpr AS cstat
                                        FROM `$cTable` c $join WHERE " . implode(' AND ', $where));
                    $s->execute($cp);
                    foreach ($s->fetchAll() as $cr) {
                        $st = strtolower(trim((string)$cr['cstat']));
                        if ($st === 'pending') $pending++;
                        if (in_array($st, $EXCLUDED_CANDIDATE_STATUS, true)) continue;
                        $eligible++;
                        $pos = trim((string)$cr['cpos']);
                        if ($pos === '') $pos = 'Candidates';
                        $positions[$pos] = true;
                        $cands[] = [
                            'id' => (int)$cr['cid'],
                            'name' => (string)$cr['cname'],
                            'position' => $pos,
                            'votes' => (int)($candVotes[(int)$cr['cid']] ?? 0),
                        ];
                    }
                } catch (Throwable $ce) {
                    $warnings[] = 'Candidate lookup failed for ' . $title . ': ' . $ce->getMessage();
                }

                /* nothing matched? explain why, so it is easy to fix */
                if (!$cands) {
                    $diag = [];
                    try {
                        if ($cElec) {
                            $s = $pdo->prepare("SELECT COUNT(*) FROM `$cTable` WHERE `$cElec` = :e");
                            $s->execute([':e' => $id]);
                            $diag[] = 'candidates with Election_ID=' . $id . ': ' . (int)$s->fetchColumn();
                        }
                        if ($cOrgId && $orgIdVal) {
                            $s = $pdo->prepare("SELECT COUNT(*) FROM `$cTable` WHERE `$cOrgId` = :o");
                            $s->execute([':o' => $orgIdVal]);
                            $diag[] = 'candidates with Org_ID=' . $orgIdVal . ': ' . (int)$s->fetchColumn();
                        }
                    } catch (Throwable $de) { /* ignore */ }
                    $warnings[] = "No approved candidates for '$title' (" . implode(', ', $diag ?: ['no link columns']) . ').';
                }
            } elseif (!$unfilterableWarned) {
                $warnings[] = 'Candidates could not be matched to an election (no usable Election_ID / Org_ID link).';
                $unfilterableWarned = true;
            }
        }
        usort($cands, function ($x, $y) {
            $c = strcmp($x['position'], $y['position']);
            return $c !== 0 ? $c : ($y['votes'] <=> $x['votes']);
        });

        /* --- timeline --- */
        $tl = [];
        if ($start) $tl[] = ['time' => $start, 'event' => ($dbNow >= strtotime($start)) ? 'Election started' : 'Election scheduled to start'];
        if ($firstVote) $tl[] = ['time' => $firstVote, 'event' => 'First vote recorded'];
        if ($lastVote && $lastVote !== $firstVote) $tl[] = ['time' => $lastVote, 'event' => 'Latest vote recorded'];
        if ($end) $tl[] = ['time' => $end, 'event' => ($dbNow > strtotime($end)) ? 'Election ended' : 'Election scheduled to end'];
        usort($tl, function ($x, $y) { return strtotime($x['time']) <=> strtotime($y['time']); });

        /* --- issues --- */
        $issues = [];
        if ($pending > 0) $issues[] = $pending . ' candidate' . ($pending === 1 ? '' : 's') . ' pending approval';
        if (($status === 'active' || $status === 'upcoming') && $eligible === 0) $issues[] = 'No approved candidates for this election yet';
        if ($registered === 0) $issues[] = 'No active registered voters found for this organization';
        if ($iTable && $iElec && $iMsg) {
            try {
                $q = "SELECT `$iMsg` AS m FROM `$iTable` WHERE `$iElec` = :e";
                if ($iStat) $q .= " AND LOWER(`$iStat`) NOT IN ('resolved','closed','done','fixed')";
                $s = $pdo->prepare($q . " LIMIT 10");
                $s->execute([':e' => $id]);
                foreach ($s->fetchAll() as $ir) if (trim((string)$ir['m']) !== '') $issues[] = (string)$ir['m'];
            } catch (Throwable $ie) { /* ignore */ }
        }

        $totalBallots += $ballots;
        $issuesTotal += count($issues);
        if ($status === 'active') $activeCount++;
        if ($status === 'active' || $status === 'completed') { $turnoutSum += $turnout; $turnoutN++; }

        $out[] = [
            'id'           => is_numeric($id) ? (int)$id : (string)$id,
            'title'        => $title,
            'organization' => $code,
            'status'       => $status,
            'start'        => $start,
            'end'          => $end,
            'voters'       => $registered,
            'voted'        => $ballots,
            'turnout'      => $turnout,
            'issues'       => count($issues),
            'issue_list'   => $issues,
            'last_activity_seconds' => $lastAgo,
            'positions'    => array_keys($positions),
            'candidates'   => $cands,
            'timeline'     => $tl,
        ];
    }

    /* ===== recent activity (anonymous) ===== */
    $activity = [];
    $votesLastHour = 0;
    if ($vTable && $vElec && $vTime) {
        try {
            $votesLastHour = (int)$pdo->query("SELECT COUNT(*) FROM `$vTable` WHERE `$vTime` >= (NOW() - INTERVAL 1 HOUR)")->fetchColumn();
            $cols = "`$vElec` AS eid, `$vTime` AS t, TIMESTAMPDIFF(SECOND, `$vTime`, NOW()) AS ago" . ($vVoter ? ", `$vVoter` AS v" : '');
            $vr = $pdo->query("SELECT $cols FROM `$vTable` ORDER BY `$vTime` DESC LIMIT 300")->fetchAll();
            $seen = [];
            foreach ($vr as $x) {
                $eid = (string)$x['eid'];
                if (!isset($titles[$eid])) continue;
                $key = $eid . '|' . ($vVoter ? $x['v'] : $x['t']);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $activity[] = ['type' => 'vote', 'text' => 'A ballot was submitted', 'election' => $titles[$eid], 'seconds_ago' => max(0, (int)$x['ago'])];
                if (count($activity) >= 12) break;
            }
        } catch (Throwable $ae) { $warnings[] = 'Activity feed failed: ' . $ae->getMessage(); }
    }
    foreach ($out as $e) {
        foreach ([['start', 'Election started'], ['end', 'Election ended']] as $pair) {
            if (!$e[$pair[0]]) continue;
            $ago = $dbNow - strtotime($e[$pair[0]]);
            if ($ago >= 0 && $ago <= 172800) {
                $activity[] = ['type' => 'system', 'text' => $pair[1], 'election' => $e['title'], 'seconds_ago' => $ago];
            }
        }
    }
    usort($activity, function ($x, $y) { return $x['seconds_ago'] <=> $y['seconds_ago']; });
    $activity = array_slice($activity, 0, 15);

    echo json_encode([
        'success'  => true,
        'stats'    => [
            'total_votes'      => $totalBallots,
            'active_elections' => $activeCount,
            'avg_turnout'      => $turnoutN ? (int)round($turnoutSum / $turnoutN) : 0,
            'issues'           => $issuesTotal,
            'votes_last_hour'  => $votesLastHour,
        ],
        'elections' => $out,
        'activity'  => $activity,
        'warnings'  => $warnings,
    ]);

} catch (PDOException $ex) {
    error_log('monitoring_api error: ' . $ex->getMessage());
    fail(500, 'Database error: ' . $ex->getMessage());
} catch (Throwable $ex) {
    error_log('monitoring_api error: ' . $ex->getMessage());
    fail(500, 'Server error: ' . $ex->getMessage());
}
