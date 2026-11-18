<?php
/**
 * electionsApi.php  -  list / save (create+update) / delete elections in eboto_db
 *
 *   GET   electionsApi.php?action=list
 *   POST  electionsApi.php?action=save    JSON: {id?, title, organization, description, startDate, endDate, status}
 *   POST  electionsApi.php?action=delete  JSON: {id}
 *
 * Column names are auto-detected (same approach as get_elections.php).
 * Writes require a logged-in session.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';

/* code => names an organization might be stored under */
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
function fail($code, $msg, $extra = []) { out(array_merge(['success' => false, 'message' => $msg], $extra), $code); }
function requireLogin() { if (empty($_SESSION)) fail(401, 'Your session has expired. Please log in again.'); }

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
    foreach (array_values($vals) as $i => $v) { $k = ":{$prefix}{$i}"; $params[$k] = $v; $ph[] = $k; }
    return implode(',', $ph);
}
function upperUnique(array $vals) {
    $out = [];
    foreach ($vals as $v) { $v = strtoupper(trim((string)$v)); if ($v !== '') $out[$v] = true; }
    return array_keys($out);
}
function orgCode($value) {
    global $ORG_NAMES;
    $v = trim((string)$value);
    $u = strtoupper($v);
    if ($u === '') return '';
    if (isset($ORG_NAMES[$u])) return $u;
    foreach ($ORG_NAMES as $code => $names) foreach ($names as $n) if (strtoupper($n) === $u) return $code;
    if (preg_match('/^([A-Z0-9_]+)\s*-\s*/', $u, $m) && isset($ORG_NAMES[$m[1]])) return $m[1];
    return $v;
}
function toIso($v) { if (!$v) return null; $ts = strtotime($v); return $ts ? date('Y-m-d\TH:i:s', $ts) : null; }
function toSql($v) { if (!$v) return null; $ts = strtotime($v); return $ts ? date('Y-m-d H:i:s', $ts) : null; }

/* ---------- election table ---------- */
$eTable = tableName($pdo, $dbName, ['elections', 'election']);
if (!$eTable) fail(500, 'No election table found in ' . $dbName);
$eCols = columnsOf($pdo, $dbName, $eTable);
$E = [
  'id'     => pick($eCols, ['Election_ID', 'id', 'election_id', 'electionId']),
  'title'  => pick($eCols, ['Title', 'title', 'name', 'Name']),
  'org'    => pick($eCols, ['Organization', 'organization', 'organization_name', 'Organization_Name', 'Org_Code']),
  'start'  => pick($eCols, ['Start_Date', 'start_date', 'startDate', 'start']),
  'end'    => pick($eCols, ['End_Date', 'end_date', 'endDate', 'end']),
  'status' => pick($eCols, ['Status', 'status']),
  'total'  => pick($eCols, ['Total_Voters', 'total_voters', 'totalVoters']),
  'desc'   => pick($eCols, ['Description', 'description']),
];
if (!$E['id']) fail(500, "Could not find the id column in `$eTable`");

/* ---------- request ---------- */
$method = $_SERVER['REQUEST_METHOD'];
$input = [];
if ($method === 'POST') {
    $j = json_decode(file_get_contents('php://input'), true);
    $input = is_array($j) ? $j : $_POST;
}
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

try {

/* =================== LIST =================== */
if ($action === 'list') {
    $order = $E['start'] ? "`{$E['start']}` DESC, `{$E['id']}` DESC" : "`{$E['id']}` DESC";
    $rows = $pdo->query("SELECT * FROM `$eTable` ORDER BY $order")->fetchAll();

    /* voters */
    $voterTable = tableName($pdo, $dbName, ['voter', 'voters']);
    $voOrg = null;
    if ($voterTable) $voOrg = pick(columnsOf($pdo, $dbName, $voterTable), ['Organization', 'Department', 'Org_Code', 'Course', 'Program', 'Org']);

    /* votes */
    $vTable = tableName($pdo, $dbName, ['vote', 'votes']);
    $vElec = $vVoter = null;
    if ($vTable) {
        $vCols  = columnsOf($pdo, $dbName, $vTable);
        $vElec  = pick($vCols, ['Election_ID', 'election_id', 'electionId', 'election']);
        $vVoter = pick($vCols, ['Voter_ID', 'voter_id', 'Student_ID', 'student_id', 'Voter', 'voter']);
    }

    $list = [];
    foreach ($rows as $r) {
        $eid  = $r[$E['id']];
        $code = $E['org'] ? orgCode($r[$E['org']]) : '';
        $names = upperUnique(array_merge([$E['org'] ? $r[$E['org']] : '', $code], $GLOBALS['ORG_NAMES'][$code] ?? []));

        /* registered voters */
        $voters = 0;
        if ($voterTable && $voOrg && $names) {
            $p = [];
            $st = $pdo->prepare("SELECT COUNT(*) FROM `$voterTable` WHERE UPPER(TRIM(`$voOrg`)) IN (" . inClause('v', $names, $p) . ")");
            $st->execute($p);
            $voters = (int)$st->fetchColumn();
        } elseif ($E['total']) {
            $voters = (int)$r[$E['total']];
        }

        /* voters who already voted */
        $voted = 0;
        if ($vTable && $vElec) {
            $sql = $vVoter ? "SELECT COUNT(DISTINCT `$vVoter`) FROM `$vTable` WHERE `$vElec` = :e"
                           : "SELECT COUNT(*) FROM `$vTable` WHERE `$vElec` = :e";
            $st = $pdo->prepare($sql);
            $st->execute([':e' => $eid]);
            $voted = (int)$st->fetchColumn();
        }

        $rawStatus = $E['status'] ? strtolower((string)$r[$E['status']]) : '';
        $list[] = [
            'id'           => is_numeric($eid) ? (int)$eid : $eid,
            'title'        => $E['title'] ? $r[$E['title']] : '',
            'organization' => $code,
            'description'  => $E['desc'] ? (string)$r[$E['desc']] : '',
            'startDate'    => $E['start'] ? toIso($r[$E['start']]) : null,
            'endDate'      => $E['end'] ? toIso($r[$E['end']]) : null,
            'status'       => (strpos($rawStatus, 'draft') !== false) ? 'draft' : 'published',
            'voters'       => $voters,
            'voted'        => $voted,
        ];
    }
    out(['success' => true, 'elections' => $list]);
}

/* =================== SAVE (create / update) =================== */
if ($action === 'save') {
    requireLogin();
    $title = trim((string)($input['title'] ?? ''));
    $org   = trim((string)($input['organization'] ?? ''));
    $desc  = trim((string)($input['description'] ?? ''));
    $start = toSql($input['startDate'] ?? '');
    $end   = toSql($input['endDate'] ?? '');
    $status = (($input['status'] ?? '') === 'draft') ? 'Draft' : 'Published';
    $id    = $input['id'] ?? null;

    if ($title === '' || $org === '') fail(400, 'Title and organization are required');
    if (!$start || !$end) fail(400, 'Valid start and end dates are required');
    if (strtotime($end) <= strtotime($start)) fail(400, 'End date must be after the start date');
    foreach (['title', 'org', 'start', 'end'] as $need) {
        if (!$E[$need]) fail(500, "The `$eTable` table has no recognisable '$need' column");
    }

    $data = [
        $E['title'] => $title,
        $E['org']   => $org,
        $E['start'] => $start,
        $E['end']   => $end,
    ];
    if ($E['status']) $data[$E['status']] = $status;
    if ($E['desc'])   $data[$E['desc']] = $desc;

    if ($id !== null && $id !== '') {
        $sets = []; $p = [];
        $i = 0;
        foreach ($data as $col => $val) { $k = ":p$i"; $sets[] = "`$col` = $k"; $p[$k] = $val; $i++; }
        $p[':id'] = $id;
        $st = $pdo->prepare("UPDATE `$eTable` SET " . implode(', ', $sets) . " WHERE `{$E['id']}` = :id");
        $st->execute($p);
        out(['success' => true, 'id' => $id, 'message' => 'Election updated']);
    } else {
        if ($E['total']) $data[$E['total']] = 0;
        $cols = []; $ph = []; $p = [];
        $i = 0;
        foreach ($data as $col => $val) { $k = ":p$i"; $cols[] = "`$col`"; $ph[] = $k; $p[$k] = $val; $i++; }
        $st = $pdo->prepare("INSERT INTO `$eTable` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $ph) . ")");
        $st->execute($p);
        out(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Election created']);
    }
}

/* =================== DELETE =================== */
if ($action === 'delete') {
    requireLogin();
    $id = $input['id'] ?? null;
    if ($id === null || $id === '') fail(400, 'Missing election id');
    try {
        $st = $pdo->prepare("DELETE FROM `$eTable` WHERE `{$E['id']}` = :id");
        $st->execute([':id' => $id]);
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') {
            fail(409, 'This election has related records (votes, positions or party lists) and cannot be deleted.');
        }
        throw $ex;
    }
    out(['success' => true, 'message' => 'Election deleted']);
}

fail(400, 'Unknown action');

} catch (Throwable $e) {
    error_log('electionsApi error: ' . $e->getMessage());
    fail(500, 'Server error', ['error' => $e->getMessage()]);
}
