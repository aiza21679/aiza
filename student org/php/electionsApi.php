<?php
/**
 * elections_api.php
 *
 *   GET  elections_api.php?action=list
 *   POST elections_api.php   action=create  title, organization, description, start_date, end_date, status
 *   POST elections_api.php   action=update  id + same fields
 *   POST elections_api.php   action=delete  id
 *
 * Column names are detected automatically (same idea as getElectionStats.php),
 * and every error comes back as JSON {success:false, message:"..."}.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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
function columnMeta($pdo, $schema, $table) {
    $s = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                        FROM information_schema.columns WHERE table_schema = :s AND table_name = :t ORDER BY ORDINAL_POSITION");
    $s->execute([':s' => $schema, ':t' => $table]);
    $out = [];
    foreach ($s->fetchAll() as $r) $out[$r['COLUMN_NAME']] = $r;
    return $out;
}
function pick(array $cols, array $variants) {
    $lower = [];
    foreach ($cols as $c) $lower[strtolower($c)] = $c;
    foreach ($variants as $v) if (isset($lower[strtolower($v)])) return $lower[strtolower($v)];
    return null;
}
function enumOptions($meta) {
    if (strtolower($meta['DATA_TYPE']) !== 'enum') return [];
    preg_match_all("/'((?:[^']|'')*)'/", $meta['COLUMN_TYPE'], $m);
    return array_map(function ($x) { return str_replace("''", "'", $x); }, $m[1]);
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
function toLocalInput($v) {
    if (!$v) return '';
    $t = strtotime($v);
    return $t ? date('Y-m-d\TH:i', $t) : '';
}
function statusFromDates($start, $end) {
    $now = time();
    $s = $start ? strtotime($start) : null;
    $e = $end ? strtotime($end) : null;
    if ($s && $now < $s) return 'upcoming';
    if ($e && $now > $e) return 'completed';
    return 'active';
}
function storedDate($input, $meta) {
    $t = strtotime(str_replace('T', ' ', (string)$input));
    if (!$t) return null;
    return strtolower($meta['DATA_TYPE']) === 'date' ? date('Y-m-d', $t) : date('Y-m-d H:i:s', $t);
}
function statusToStore($meta, $wantDraft, $start, $end) {
    $label = $wantDraft ? 'Draft' : ucfirst(statusFromDates($start, $end));
    $opts = enumOptions($meta);
    if (!$opts) return $label;
    foreach ($opts as $o) if (strcasecmp($o, $label) === 0) return $o;
    foreach ($opts as $o) {
        $isDraft = stripos($o, 'draft') !== false;
        if ($wantDraft === $isDraft) return $o;
    }
    return $opts[0];
}

/* ---------- schema ---------- */
$eTable = tableName($pdo, $dbName, ['elections', 'election']);
if (!$eTable) fail(500, 'No election table found in ' . $dbName);
$meta = columnMeta($pdo, $dbName, $eTable);
$cols = array_keys($meta);

$eId     = pick($cols, ['Election_ID', 'id', 'election_id', 'electionId']);
$eTitle  = pick($cols, ['Title', 'title', 'name', 'Name']);
$eOrg    = pick($cols, ['Organization', 'organization', 'organization_name', 'Organization_Name', 'Org_Code']);
$eStart  = pick($cols, ['Start_Date', 'start_date', 'startDate', 'start']);
$eEnd    = pick($cols, ['End_Date', 'end_date', 'endDate', 'end']);
$eStatus = pick($cols, ['Status', 'status']);
$eDesc   = pick($cols, ['Description', 'description']);
if (!$eId)    fail(500, "Could not find the id column in `$eTable`");
if (!$eTitle) fail(500, "Could not find the title column in `$eTable`");

$action = $_REQUEST['action'] ?? 'list';

try {

/* =========================== LIST =========================== */
if ($action === 'list') {
    $order = $eStart ? "`$eStart` DESC, `$eId` DESC" : "`$eId` DESC";
    $rows = $pdo->query("SELECT * FROM `$eTable` ORDER BY $order")->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $start = $eStart ? $r[$eStart] : null;
        $end   = $eEnd ? $r[$eEnd] : null;
        $raw   = $eStatus ? strtolower((string)$r[$eStatus]) : '';
        $status = (strpos($raw, 'draft') !== false) ? 'draft' : statusFromDates($start, $end);
        $out[] = [
            'id'           => is_numeric($r[$eId]) ? (int)$r[$eId] : $r[$eId],
            'title'        => (string)$r[$eTitle],
            'organization' => $eOrg ? orgCode($r[$eOrg], $ORG_NAMES) : '',
            'description'  => $eDesc ? (string)$r[$eDesc] : '',
            'start'        => toLocalInput($start),
            'end'          => toLocalInput($end),
            'status'       => $status,
        ];
    }
    echo json_encode(['success' => true, 'elections' => $out]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Use POST for this action');

/* =========================== DELETE =========================== */
if ($action === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id === '') fail(400, 'Missing id');
    $stmt = $pdo->prepare("DELETE FROM `$eTable` WHERE `$eId` = :id");
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action !== 'create' && $action !== 'update') fail(400, 'Unknown action');

/* =========================== validate input =========================== */
$title = trim($_POST['title'] ?? '');
$org   = strtoupper(trim($_POST['organization'] ?? ''));
$desc  = trim($_POST['description'] ?? '');
$startIn = trim($_POST['start_date'] ?? '');
$endIn   = trim($_POST['end_date'] ?? '');
$wantDraft = (($_POST['status'] ?? 'draft') === 'draft');

if ($title === '' || strlen($title) > 255) fail(400, 'Title is required (max 255 characters)');
if (!isset($ORG_NAMES[$org])) fail(400, 'Invalid organization');
$ts = strtotime(str_replace('T', ' ', $startIn));
$te = strtotime(str_replace('T', ' ', $endIn));
if (!$ts || !$te) fail(400, 'Start and end date are required');
if ($te <= $ts) fail(400, 'End date must be after the start date');

$fields = [];
$fields[$eTitle] = $title;
if ($eOrg)   $fields[$eOrg] = $org;
if ($eDesc)  $fields[$eDesc] = $desc;
if ($eStart) $fields[$eStart] = storedDate($startIn, $meta[$eStart]);
if ($eEnd)   $fields[$eEnd] = storedDate($endIn, $meta[$eEnd]);
if ($eStatus) $fields[$eStatus] = statusToStore($meta[$eStatus], $wantDraft, $startIn, $endIn);

/* =========================== UPDATE =========================== */
if ($action === 'update') {
    $id = trim($_POST['id'] ?? '');
    if ($id === '') fail(400, 'Missing id');
    $chk = $pdo->prepare("SELECT COUNT(*) FROM `$eTable` WHERE `$eId` = :id");
    $chk->execute([':id' => $id]);
    if (!(int)$chk->fetchColumn()) fail(404, 'Election not found');

    $set = []; $params = [':__id' => $id]; $i = 0;
    foreach ($fields as $col => $val) {
        $k = ":f$i"; $i++;
        $set[] = "`$col` = $k";
        $params[$k] = $val;
    }
    $pdo->prepare("UPDATE `$eTable` SET " . implode(', ', $set) . " WHERE `$eId` = :__id")->execute($params);
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

/* =========================== CREATE =========================== */
/* fill any REQUIRED column we don't know about, so the INSERT doesn't fail */
foreach ($meta as $name => $m) {
    if (array_key_exists($name, $fields)) continue;
    $auto = stripos($m['EXTRA'], 'auto_increment') !== false;
    if ($name === $eId) {
        if (!$auto) {
            $max = $pdo->query("SELECT COALESCE(MAX(`$eId`), 0) + 1 FROM `$eTable`")->fetchColumn();
            $fields[$eId] = $max;
        }
        continue;
    }
    if ($m['IS_NULLABLE'] === 'YES' || $m['COLUMN_DEFAULT'] !== null) continue;
    if (stripos($m['EXTRA'], 'generated') !== false) continue;
    $dt = strtolower($m['DATA_TYPE']);
    if (in_array($dt, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double'])) $fields[$name] = 0;
    elseif (in_array($dt, ['datetime', 'timestamp'])) $fields[$name] = date('Y-m-d H:i:s');
    elseif ($dt === 'date') $fields[$name] = date('Y-m-d');
    elseif ($dt === 'enum') { $o = enumOptions($m); $fields[$name] = $o ? $o[0] : ''; }
    else $fields[$name] = '';
}

$colList = []; $phList = []; $params = []; $i = 0;
foreach ($fields as $col => $val) {
    $k = ":f$i"; $i++;
    $colList[] = "`$col`";
    $phList[] = $k;
    $params[$k] = $val;
}
$pdo->prepare("INSERT INTO `$eTable` (" . implode(', ', $colList) . ") VALUES (" . implode(', ', $phList) . ")")->execute($params);
$newId = isset($fields[$eId]) ? $fields[$eId] : $pdo->lastInsertId();
echo json_encode(['success' => true, 'id' => $newId]);

} catch (PDOException $ex) {
    error_log('elections_api error: ' . $ex->getMessage());
    fail(500, 'Database error: ' . $ex->getMessage());
} catch (Throwable $ex) {
    error_log('elections_api error: ' . $ex->getMessage());
    fail(500, 'Server error: ' . $ex->getMessage());
}
