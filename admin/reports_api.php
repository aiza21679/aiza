<?php
/**
 * reports_api.php  -  live election reports straight from eboto_db
 *
 *   reports_api.php?action=list                      -> every election with live summary numbers
 *   reports_api.php?action=report&election_id=ID     -> full report data for one election:
 *        results, turnout, candidates, demographics, audit checks
 *
 * Tables used (matched to your database):
 *   election, candidate, vote, position, student_organization, organization_voters, student
 * Column names are auto-detected. Whatever cannot be matched is listed in "warnings".
 * Ballots are anonymous: no voter names or IDs are ever returned.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user_id'])) {          // same session key getAdmin.php checks
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

function out($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }
function fail($code, $msg) { out(['success' => false, 'message' => $msg], $code); }

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
        $f = $s->fetchColumn();
        if ($f) return $f;
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
    foreach ($ORG_NAMES as $code => $names) foreach ($names as $n) if (strtoupper($n) === $v) return $code;
    if (preg_match('/^([A-Z]+)\s*-/', $v, $m) && isset($ORG_NAMES[$m[1]])) return $m[1];
    return trim((string)$raw);
}
function statusFromDates($start, $end, $now) {
    $s = $start ? strtotime($start) : null;
    $e = $end ? strtotime($end) : null;
    if ($s && $now < $s) return 'upcoming';
    if ($e && $now > $e) return 'completed';
    return 'active';
}

/* ---------- schema detection ---------- */
function detectSchema($pdo, $db) {
    $S = [];

    $t = tableName($pdo, $db, ['election', 'elections']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['e'] = ['t' => $t,
        'id' => pick($cols, ['Election_ID', 'id', 'election_id']),
        'title' => pick($cols, ['Title', 'title', 'name', 'Name']),
        'org' => pick($cols, ['Organization', 'organization', 'Org_Code']),
        'orgId' => pick($cols, ['Org_ID', 'org_id']),
        'start' => pick($cols, ['Start_Date', 'start_date', 'startDate']),
        'end' => pick($cols, ['End_Date', 'end_date', 'endDate']),
        'status' => pick($cols, ['Status', 'status']),
        'total' => pick($cols, ['Total_Voters', 'total_voters']),
    ];

    $t = tableName($pdo, $db, ['student_organization']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['o'] = ['t' => $t, 'id' => pick($cols, ['Org_ID', 'id']), 'name' => pick($cols, ['Org_Name', 'name'])];
    if (!$S['o']['id'] || !$S['o']['name']) $S['o']['t'] = null;

    $t = tableName($pdo, $db, ['organization_voters']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['ov'] = ['t' => $t, 'org' => pick($cols, ['org_id', 'Org_ID']), 'stat' => pick($cols, ['status', 'Status']),
                'student' => pick($cols, ['student_id', 'Student_ID'])];

    $t = tableName($pdo, $db, ['vote', 'votes']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['v'] = ['t' => $t,
        'elec' => pick($cols, ['Election_ID', 'election_id', 'election']),
        'cand' => pick($cols, ['Candidate_ID', 'candidate_id']),
        'sel' => pick($cols, ['selections', 'Selections']),
        'voter' => pick($cols, ['Voter_ID', 'voter_id', 'Student_ID', 'student_id', 'Voter', 'voter']),
        'time' => pick($cols, ['Timestamp', 'timestamp', 'Voted_At', 'voted_at', 'Date_Voted', 'Vote_Date', 'created_at']),
    ];

    $t = tableName($pdo, $db, ['candidate', 'candidates']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['c'] = ['t' => $t,
        'id' => pick($cols, ['Candidate_ID', 'candidate_id', 'id']),
        'name' => pick($cols, ['FullName', 'Full_Name', 'Candidate_Name', 'Name']),
        'pos' => pick($cols, ['Position', 'Position_Name']),
        'posId' => pick($cols, ['Position_ID', 'position_id']),
        'stat' => pick($cols, ['status', 'Status']),
        'elec' => pick($cols, ['Election_ID', 'election_id']),
        'orgId' => pick($cols, ['Org_ID', 'org_id']),
        'party' => pick($cols, ['PartyList', 'Party_List', 'party']),
        'prog' => pick($cols, ['Program', 'Course', 'Department']),
        'year' => pick($cols, ['YearLevel', 'Year_Level', 'Year']),
    ];

    $t = tableName($pdo, $db, ['position', 'positions']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['p'] = ['t' => $t, 'id' => pick($cols, ['Position_ID', 'id']), 'name' => pick($cols, ['Position_Name', 'position_name', 'name', 'Name'])];
    if (!$S['p']['id'] || !$S['p']['name']) $S['p']['t'] = null;

    $t = tableName($pdo, $db, ['student', 'students']);
    $cols = $t ? columnsOf($pdo, $db, $t) : [];
    $S['s'] = ['t' => $t,
        'id' => pick($cols, ['Student_ID', 'id', 'student_id']),
        'prog' => pick($cols, ['Program', 'Course', 'Department']),
        'year' => pick($cols, ['YearLevel', 'Year_Level', 'Year']),
    ];
    return $S;
}

/* ---------- per-election data ---------- */
function resolveOrg($pdo, $S, $r, $ORG_NAMES) {
    $E = $S['e'];
    $raw = $E['org'] ? (string)$r[$E['org']] : '';
    $code = orgCode($raw, $ORG_NAMES);
    $orgIdVal = null;
    if ($E['orgId'] && !empty($r[$E['orgId']])) {
        $orgIdVal = (int)$r[$E['orgId']];
    } elseif ($S['o']['t']) {
        $nm = [];
        foreach ([$raw, $code] as $v) { $v = strtoupper(trim((string)$v)); if ($v !== '') $nm[$v] = true; }
        foreach (($ORG_NAMES[$code] ?? []) as $v) $nm[strtoupper($v)] = true;
        if ($nm) {
            $ph = []; $pp = []; $i = 0;
            foreach (array_keys($nm) as $v) { $k = ":on$i"; $i++; $pp[$k] = $v; $ph[] = $k; }
            $s = $pdo->prepare("SELECT `{$S['o']['id']}` FROM `{$S['o']['t']}` WHERE UPPER(TRIM(`{$S['o']['name']}`)) IN (" . implode(',', $ph) . ") LIMIT 1");
            $s->execute($pp);
            $f = $s->fetchColumn();
            if ($f) $orgIdVal = (int)$f;
        }
    }
    return [$code, $raw, $orgIdVal];
}

function registeredFor($pdo, $S, $orgIdVal, $r) {
    $ov = $S['ov'];
    if ($ov['t'] && $ov['org'] && $orgIdVal) {
        $q = "SELECT COUNT(*) FROM `{$ov['t']}` WHERE `{$ov['org']}` = :o" . ($ov['stat'] ? " AND LOWER(`{$ov['stat']}`) = 'active'" : '');
        $s = $pdo->prepare($q);
        $s->execute([':o' => $orgIdVal]);
        return (int)$s->fetchColumn();
    }
    if ($S['e']['total']) return (int)$r[$S['e']['total']];
    return 0;
}

function voteStats($pdo, $S, $eid) {
    $v = $S['v'];
    $res = ['n' => 0, 'ballots' => 0, 'first' => null, 'last' => null, 'cand' => []];
    if (!$v['t'] || !$v['elec']) return $res;

    $sel = ['COUNT(*) AS n'];
    if ($v['voter']) $sel[] = "COUNT(DISTINCT `{$v['voter']}`) AS voters";
    if ($v['time']) { $sel[] = "MIN(`{$v['time']}`) AS first_t"; $sel[] = "MAX(`{$v['time']}`) AS last_t"; }
    $s = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM `{$v['t']}` WHERE `{$v['elec']}` = :e");
    $s->execute([':e' => $eid]);
    $a = $s->fetch();
    $res['n'] = (int)($a['n'] ?? 0);
    $res['ballots'] = ($v['voter'] && (int)($a['voters'] ?? 0) > 0) ? (int)$a['voters'] : $res['n'];
    $res['first'] = $a['first_t'] ?? null;
    $res['last'] = $a['last_t'] ?? null;

    if ($res['n'] > 0) {
        if ($v['cand']) {
            $s = $pdo->prepare("SELECT `{$v['cand']}` AS cid, COUNT(*) AS c FROM `{$v['t']}` WHERE `{$v['elec']}` = :e GROUP BY `{$v['cand']}`");
            $s->execute([':e' => $eid]);
            foreach ($s->fetchAll() as $x) $res['cand'][(int)$x['cid']] = (int)$x['c'];
        } elseif ($v['sel']) {
            $s = $pdo->prepare("SELECT `{$v['sel']}` AS sel FROM `{$v['t']}` WHERE `{$v['elec']}` = :e");
            $s->execute([':e' => $eid]);
            foreach ($s->fetchAll() as $x) {
                $j = json_decode((string)$x['sel'], true);
                if (is_array($j)) {
                    array_walk_recursive($j, function ($cid) use (&$res) {
                        $cid = (int)$cid;
                        if ($cid > 0) $res['cand'][$cid] = ($res['cand'][$cid] ?? 0) + 1;
                    });
                }
            }
        }
    }
    return $res;
}

/* every candidate linked to this election (all statuses) */
function candidatesFor($pdo, $S, $eid, $orgIdVal, &$note) {
    $c = $S['c'];
    if (!$c['t'] || !$c['id']) { $note = 'No candidate table found.'; return []; }
    $join = ''; $posExpr = "''";
    if ($c['pos']) $posExpr = "c.`{$c['pos']}`";
    elseif ($c['posId'] && $S['p']['t']) {
        $join = " LEFT JOIN `{$S['p']['t']}` p ON p.`{$S['p']['id']}` = c.`{$c['posId']}`";
        $posExpr = "p.`{$S['p']['name']}`";
    }
    $where = ''; $par = [];
    if ($c['elec'] && $c['orgId'] && $orgIdVal) {
        $where = "(c.`{$c['elec']}` = :eid OR ((c.`{$c['elec']}` IS NULL OR c.`{$c['elec']}` = 0) AND c.`{$c['orgId']}` = :oid))";
        $par = [':eid' => $eid, ':oid' => $orgIdVal];
    } elseif ($c['elec']) {
        $where = "c.`{$c['elec']}` = :eid"; $par = [':eid' => $eid];
    } elseif ($c['orgId'] && $orgIdVal) {
        $where = "c.`{$c['orgId']}` = :oid"; $par = [':oid' => $orgIdVal];
    } else { $note = 'Candidates cannot be linked to this election.'; return []; }

    $col = function ($k) use ($c) { return $c[$k] ? "c.`{$c[$k]}`" : "''"; };
    $nameExpr = $c['name'] ? "c.`{$c['name']}`" : "CAST(c.`{$c['id']}` AS CHAR)";
    $sql = "SELECT c.`{$c['id']}` AS cid, $nameExpr AS cname, $posExpr AS cpos, " . $col('stat') . " AS cstat, "
         . $col('party') . " AS cparty, " . $col('prog') . " AS cprog, " . $col('year') . " AS cyear
            FROM `{$c['t']}` c $join WHERE $where";
    $s = $pdo->prepare($sql);
    $s->execute($par);
    $rows = [];
    foreach ($s->fetchAll() as $x) {
        $pos = trim((string)$x['cpos']);
        $rows[] = [
            'id' => (int)$x['cid'],
            'name' => (string)$x['cname'],
            'position' => $pos !== '' ? $pos : 'Candidates',
            'status' => strtolower(trim((string)$x['cstat'])),
            'party' => (string)$x['cparty'],
            'program' => (string)$x['cprog'],
            'year' => (string)$x['cyear'],
        ];
    }
    return $rows;
}

function buildCore($pdo, $S, $r, $ORG_NAMES, $dbNow, array &$warn) {
    $E = $S['e'];
    $id = $r[$E['id']];
    $start = $E['start'] ? $r[$E['start']] : null;
    $end = $E['end'] ? $r[$E['end']] : null;
    list($code, $raw, $orgIdVal) = resolveOrg($pdo, $S, $r, $ORG_NAMES);
    $raws = strtolower((string)($E['status'] ? $r[$E['status']] : ''));
    $status = (strpos($raws, 'draft') !== false) ? 'draft' : statusFromDates($start, $end, $dbNow);

    $registered = registeredFor($pdo, $S, $orgIdVal, $r);
    $vs = voteStats($pdo, $S, $id);
    $note = '';
    try { $cands = candidatesFor($pdo, $S, $id, $orgIdVal, $note); }
    catch (Throwable $e) { $cands = []; $note = $e->getMessage(); }
    if ($note !== '') $warn[] = $note;

    foreach ($cands as &$c) $c['votes'] = (int)($vs['cand'][$c['id']] ?? 0);
    unset($c);

    return [
        'id' => is_numeric($id) ? (int)$id : (string)$id,
        'title' => (string)$r[$E['title']],
        'organization' => $code,
        'org_name' => $raw,
        'org_id' => $orgIdVal,
        'status' => $status,
        'start' => $start, 'end' => $end,
        'registered' => $registered,
        'vs' => $vs,
        'cands' => $cands,
    ];
}

function loadElections($pdo, $S) {
    $E = $S['e'];
    if (!$E['t'] || !$E['id'] || !$E['title']) fail(500, 'Could not find the election table / columns.');
    $order = $E['start'] ? "`{$E['start']}` DESC, `{$E['id']}` DESC" : "`{$E['id']}` DESC";
    return $pdo->query("SELECT * FROM `{$E['t']}` ORDER BY $order")->fetchAll();
}

$warnings = [];

try {
    $dbNow = strtotime((string)$pdo->query("SELECT NOW()")->fetchColumn());
    $S = detectSchema($pdo, $dbName);
    $action = $_GET['action'] ?? 'list';

    /* ================= LIST ================= */
    if ($action === 'list') {
        $list = [];
        foreach (loadElections($pdo, $S) as $r) {
            $core = buildCore($pdo, $S, $r, $ORG_NAMES, $dbNow, $warnings);
            if ($core['status'] === 'draft') continue;
            $approved = 0; $pending = 0;
            foreach ($core['cands'] as $c) {
                if ($c['status'] === 'approved') $approved++;
                if ($c['status'] === 'pending') $pending++;
            }
            $voted = $core['vs']['ballots'];
            $list[] = [
                'id' => $core['id'], 'title' => $core['title'], 'organization' => $core['organization'],
                'status' => $core['status'], 'start' => $core['start'], 'end' => $core['end'],
                'voters' => $core['registered'], 'voted' => $voted,
                'turnout' => $core['registered'] > 0 ? min(100, (int)round($voted / $core['registered'] * 100)) : 0,
                'candidates' => $approved, 'pending' => $pending,
            ];
        }
        out(['success' => true, 'elections' => $list, 'warnings' => array_values(array_unique($warnings))]);
    }

    /* ================= REPORT (one election) ================= */
    if ($action === 'report') {
        $eid = isset($_GET['election_id']) ? trim($_GET['election_id']) : '';
        if ($eid === '' || !preg_match('/^[A-Za-z0-9_\-]{1,50}$/', $eid)) fail(400, 'Invalid election_id');
        $E = $S['e'];
        $st = $pdo->prepare("SELECT * FROM `{$E['t']}` WHERE `{$E['id']}` = :id LIMIT 1");
        $st->execute([':id' => $eid]);
        $r = $st->fetch();
        if (!$r) fail(404, 'Election not found');

        $core = buildCore($pdo, $S, $r, $ORG_NAMES, $dbNow, $warnings);
        $v = $S['v']; $c = $S['c']; $ov = $S['ov'];
        $voted = $core['vs']['ballots'];
        $registered = $core['registered'];
        $turnout = $registered > 0 ? min(100, (int)round($voted / $registered * 100)) : 0;

        /* ---- results (approved candidates, grouped by position) ---- */
        $approved = array_values(array_filter($core['cands'], function ($x) { return $x['status'] === 'approved'; }));
        $byPos = [];
        foreach ($approved as $x) $byPos[$x['position']][] = $x;
        ksort($byPos);
        $results = [];
        foreach ($byPos as $pos => $list) {
            usort($list, function ($a, $b) { return $b['votes'] <=> $a['votes']; });
            $total = array_sum(array_column($list, 'votes'));
            $max = $list ? $list[0]['votes'] : 0;
            $leaders = 0;
            foreach ($list as $x) if ($x['votes'] === $max) $leaders++;
            $rank = 0; $rows = [];
            foreach ($list as $x) {
                $rank++;
                $rows[] = [
                    'id' => $x['id'], 'name' => $x['name'], 'party' => $x['party'], 'votes' => $x['votes'],
                    'percent' => $total > 0 ? round($x['votes'] / $total * 100, 1) : 0,
                    'rank' => $rank,
                    'winner' => ($max > 0 && $leaders === 1 && $x['votes'] === $max),
                    'tie' => ($max > 0 && $leaders > 1 && $x['votes'] === $max),
                ];
            }
            $results[] = ['position' => $pos, 'total_votes' => $total, 'candidates' => $rows];
        }

        /* ---- candidate performance (every status) ---- */
        $posTotals = [];
        foreach ($approved as $x) $posTotals[$x['position']] = ($posTotals[$x['position']] ?? 0) + $x['votes'];
        $perf = [];
        foreach ($core['cands'] as $x) {
            $isApproved = $x['status'] === 'approved';
            $t = $posTotals[$x['position']] ?? 0;
            $perf[] = [
                'name' => $x['name'], 'position' => $x['position'], 'party' => $x['party'],
                'program' => $x['program'], 'year' => $x['year'], 'status' => $x['status'],
                'votes' => $isApproved ? $x['votes'] : null,
                'percent' => ($isApproved && $t > 0) ? round($x['votes'] / $t * 100, 1) : null,
            ];
        }
        usort($perf, function ($a, $b) {
            $c = strcmp($a['position'], $b['position']);
            return $c !== 0 ? $c : (($b['votes'] ?? -1) <=> ($a['votes'] ?? -1));
        });

        /* ---- turnout over time ---- */
        $byDay = []; $byHour = [];
        if ($v['t'] && $v['elec'] && $v['time']) {
            $cnt = $v['voter'] ? "COUNT(DISTINCT `{$v['voter']}`)" : "COUNT(*)";
            $s = $pdo->prepare("SELECT DATE(`{$v['time']}`) AS d, $cnt AS c FROM `{$v['t']}` WHERE `{$v['elec']}` = :e GROUP BY DATE(`{$v['time']}`) ORDER BY d");
            $s->execute([':e' => $eid]);
            foreach ($s->fetchAll() as $x) $byDay[] = ['label' => $x['d'], 'count' => (int)$x['c']];
            $s = $pdo->prepare("SELECT HOUR(`{$v['time']}`) AS h, $cnt AS c FROM `{$v['t']}` WHERE `{$v['elec']}` = :e GROUP BY HOUR(`{$v['time']}`) ORDER BY h");
            $s->execute([':e' => $eid]);
            foreach ($s->fetchAll() as $x) $byHour[] = ['hour' => (int)$x['h'], 'count' => (int)$x['c']];
        }
        $peak = $byHour;
        usort($peak, function ($a, $b) { return $b['count'] <=> $a['count']; });
        $peak = array_slice(array_filter($peak, function ($x) { return $x['count'] > 0; }), 0, 3);

        /* ---- voter demographics (registered voters of the organization) ---- */
        $demo = ['by_status' => [], 'by_program' => [], 'by_year' => []];
        if ($ov['t'] && $ov['org'] && $core['org_id']) {
            if ($ov['stat']) {
                $s = $pdo->prepare("SELECT `{$ov['stat']}` AS k, COUNT(*) AS c FROM `{$ov['t']}` WHERE `{$ov['org']}` = :o GROUP BY `{$ov['stat']}`");
                $s->execute([':o' => $core['org_id']]);
                foreach ($s->fetchAll() as $x) $demo['by_status'][] = ['label' => (string)$x['k'], 'count' => (int)$x['c']];
            }
            $sd = $S['s'];
            if ($sd['t'] && $sd['id'] && $ov['student']) {
                foreach ([['prog', 'by_program'], ['year', 'by_year']] as $pair) {
                    if (!$sd[$pair[0]]) continue;
                    $q = "SELECT s2.`{$sd[$pair[0]]}` AS k, COUNT(*) AS c
                          FROM `{$ov['t']}` o2 JOIN `{$sd['t']}` s2 ON s2.`{$sd['id']}` = o2.`{$ov['student']}`
                          WHERE o2.`{$ov['org']}` = :o" . ($ov['stat'] ? " AND LOWER(o2.`{$ov['stat']}`) = 'active'" : '') . "
                          GROUP BY s2.`{$sd[$pair[0]]}` ORDER BY c DESC LIMIT 25";
                    try {
                        $s = $pdo->prepare($q);
                        $s->execute([':o' => $core['org_id']]);
                        foreach ($s->fetchAll() as $x) $demo[$pair[1]][] = ['label' => trim((string)$x['k']) !== '' ? (string)$x['k'] : 'Not specified', 'count' => (int)$x['c']];
                    } catch (Throwable $de) { $warnings[] = 'Demographics by ' . $pair[0] . ' unavailable: ' . $de->getMessage(); }
                }
            }
        } else {
            $warnings[] = 'Voter demographics need organization_voters linked to the election organization.';
        }

        /* ---- audit checks ---- */
        $checks = [];
        $add = function ($name, $status, $detail) use (&$checks) { $checks[] = ['name' => $name, 'status' => $status, 'detail' => $detail]; };

        $add('Registered voters', $registered > 0 ? 'pass' : 'warn',
             $registered > 0 ? "$registered active registered voters" : 'No active registered voters found for this organization');

        $nApproved = count($approved);
        $nPending = count(array_filter($core['cands'], function ($x) { return $x['status'] === 'pending'; }));
        $add('Approved candidates', $nApproved > 0 ? 'pass' : 'fail', $nApproved > 0 ? "$nApproved approved candidates" : 'No approved candidates linked to this election');
        $add('Pending candidates', $nPending === 0 ? 'pass' : 'warn', $nPending === 0 ? 'No candidates waiting for approval' : "$nPending candidate(s) still pending approval");

        $add('Turnout sanity', ($registered > 0 && $voted > $registered) ? 'fail' : 'pass',
             ($registered > 0 && $voted > $registered) ? "More voters ($voted) than registered voters ($registered)" : "$voted voter(s) of $registered registered");

        if ($v['t'] && $v['elec'] && $v['time'] && $core['start'] && $core['end']) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM `{$v['t']}` WHERE `{$v['elec']}` = :e AND (`{$v['time']}` < :s OR `{$v['time']}` > :en)");
            $s->execute([':e' => $eid, ':s' => $core['start'], ':en' => $core['end']]);
            $n = (int)$s->fetchColumn();
            $add('Votes inside election period', $n === 0 ? 'pass' : 'fail', $n === 0 ? 'Every vote was recorded between the start and end time' : "$n vote record(s) fall outside the election period");
        } else {
            $add('Votes inside election period', 'info', 'Not checked (no vote timestamp column)');
        }

        if ($v['t'] && $v['elec'] && $v['voter']) {
            $q = null;
            if ($v['cand'] && $c['t'] && $c['id'] && $c['posId']) {
                $q = "SELECT COUNT(*) FROM (SELECT vt.`{$v['voter']}` FROM `{$v['t']}` vt JOIN `{$c['t']}` cc ON cc.`{$c['id']}` = vt.`{$v['cand']}`
                      WHERE vt.`{$v['elec']}` = :e GROUP BY vt.`{$v['voter']}`, cc.`{$c['posId']}` HAVING COUNT(*) > 1) x";
            } elseif (!$v['cand']) {
                $q = "SELECT COUNT(*) FROM (SELECT `{$v['voter']}` FROM `{$v['t']}` WHERE `{$v['elec']}` = :e GROUP BY `{$v['voter']}` HAVING COUNT(*) > 1) x";
            }
            if ($q) {
                try {
                    $s = $pdo->prepare($q);
                    $s->execute([':e' => $eid]);
                    $n = (int)$s->fetchColumn();
                    $add('Duplicate votes', $n === 0 ? 'pass' : 'fail', $n === 0 ? 'No voter voted twice for the same position' : "$n case(s) of a voter voting more than once for a position");
                } catch (Throwable $de) { $add('Duplicate votes', 'info', 'Could not be verified'); }
            } else {
                $add('Duplicate votes', 'info', 'Not checked');
            }
        } else {
            $add('Duplicate votes', 'info', 'Not checked (votes have no voter column)');
        }

        if ($core['vs']['cand']) {
            $okIds = array_column($approved, 'id');
            $bad = 0;
            foreach ($core['vs']['cand'] as $cid => $n) if (!in_array($cid, $okIds, true)) $bad += $n;
            $add('Votes for valid candidates', $bad === 0 ? 'pass' : 'fail',
                 $bad === 0 ? 'Every vote went to an approved candidate of this election' : "$bad vote(s) went to candidates that are not approved for this election");
        } else {
            $add('Votes for valid candidates', 'info', $core['vs']['n'] > 0 ? 'Not checked (votes do not list candidates)' : 'No votes recorded yet');
        }

        $overall = 'pass';
        foreach ($checks as $ck) { if ($ck['status'] === 'fail') { $overall = 'fail'; break; } if ($ck['status'] === 'warn') $overall = 'warn'; }

        out([
            'success' => true,
            'generated_at' => date('Y-m-d H:i:s'),
            'election' => [
                'id' => $core['id'], 'title' => $core['title'], 'organization' => $core['organization'],
                'status' => $core['status'], 'start' => $core['start'], 'end' => $core['end'],
            ],
            'summary' => [
                'registered' => $registered, 'voted' => $voted, 'not_voted' => max(0, $registered - $voted), 'turnout' => $turnout,
                'candidates' => $nApproved, 'positions' => count($results), 'ballot_records' => $core['vs']['n'],
            ],
            'results' => $results,
            'turnout' => ['by_day' => $byDay, 'by_hour' => $byHour, 'peak_hours' => array_values($peak)],
            'candidates' => $perf,
            'demographics' => $demo,
            'audit' => ['overall' => $overall, 'checks' => $checks],
            'warnings' => array_values(array_unique($warnings)),
        ]);
    }

    fail(400, 'Unknown action');

} catch (PDOException $ex) {
    error_log('reports_api error: ' . $ex->getMessage());
    fail(500, 'Database error: ' . $ex->getMessage());
} catch (Throwable $ex) {
    error_log('reports_api error: ' . $ex->getMessage());
    fail(500, 'Server error: ' . $ex->getMessage());
}
