<?php
// save_election.php
// Server-computed status + party-list sync
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *'); // restrict in production
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

/* DB config */
$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB connection failed','error'=>$ex->getMessage()]);
    exit;
}

/* helpers */
function getVal(array $a, $k, $d=null) { return array_key_exists($k,$a) ? $a[$k] : $d; }
function parseToSqlDatetime($raw, bool $isEnd=false): ?string {
    if ($raw === null || $raw === '') return null;
    if (is_numeric($raw)) {
        $s = (string)$raw;
        $ts = (strlen($s) <= 10) ? (int)$s : (int)floor((int)$s / 1000);
        return date('Y-m-d H:i:s', $ts);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$raw)) return $raw . ($isEnd ? ' 23:59:59' : ' 00:00:00');
    try { $dt = new DateTimeImmutable((string)$raw); return $dt->format('Y-m-d H:i:s'); }
    catch (Exception $e) { return null; }
}

/* party list sync (keeps your existing behavior) */
function syncElectionPartyLists(PDO $pdo, string $schema, $electionId, array $party_arr) {
    $party_arr = array_values(array_filter(array_map('trim',$party_arr)));
    if (empty($party_arr)) return;

    try {
        $plColsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :schema AND table_name = 'party_lists'");
        $plColsStmt->execute([':schema'=>$schema]); $plCols = $plColsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $eplColsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :schema AND table_name = 'election_party_list'");
        $eplColsStmt->execute([':schema'=>$schema]); $eplCols = $eplColsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Exception $e) {
        return;
    }
    if (empty($plCols) || empty($eplCols)) return;

    $plIdCol = in_array('id',$plCols,true) ? 'id' : (in_array('Party_List_ID',$plCols,true) ? 'Party_List_ID' : $plCols[0]);
    $plNameCol = null; $plShortCol = null;
    foreach (['Party_Name','party_name','name','Name'] as $c) if (in_array($c,$plCols,true)) { $plNameCol=$c; break; }
    foreach (['short_code','shortcode','code','short','Short_Code'] as $c) if (in_array($c,$plCols,true)) { $plShortCol=$c; break; }

    $eplElectionFk = in_array('election_id',$eplCols,true) ? 'election_id' : (in_array('Election_ID',$eplCols,true) ? 'Election_ID' : $eplCols[0]);
    $eplPartyFk = in_array('party_list_id',$eplCols,true) ? 'party_list_id' : (in_array('Party_List_ID',$eplCols,true) ? 'Party_List_ID' : $eplCols[0]);

    $partyIds = [];
    if ($plNameCol || $plShortCol) {
        $findByName = $plNameCol ? $pdo->prepare("SELECT `$plIdCol` FROM party_lists WHERE `$plNameCol` = :val LIMIT 1") : null;
        $findByShort = $plShortCol ? $pdo->prepare("SELECT `$plIdCol` FROM party_lists WHERE `$plShortCol` = :val LIMIT 1") : null;

        $insertCols = [];
        if ($plNameCol) $insertCols[] = "`$plNameCol`";
        if ($plShortCol) $insertCols[] = "`$plShortCol`";
        if (empty($insertCols)) $insertCols[] = "`$plNameCol`";

        $insertPlaceholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $insertSql = "INSERT INTO party_lists (" . implode(', ', $insertCols) . ") VALUES ($insertPlaceholders)";
        $insertStmt = $pdo->prepare($insertSql);

        foreach ($party_arr as $pv) {
            $pid = null;
            try {
                if ($findByShort) { $findByShort->execute([':val'=>$pv]); $pid = $findByShort->fetchColumn(); }
                if (!$pid && $findByName) { $findByName->execute([':val'=>$pv]); $pid = $findByName->fetchColumn(); }
                if (!$pid) {
                    $short = substr(preg_replace('/\s+/', '_', $pv), 0, 30);
                    $params = [];
                    if ($plNameCol && $plShortCol) $params = [$pv, $short];
                    elseif ($plNameCol) $params = [$pv];
                    else $params = [$short];
                    $insertStmt->execute($params);
                    $pid = $pdo->lastInsertId();
                }
            } catch (Exception $e) { continue; }
            if ($pid) $partyIds[] = $pid;
        }
    } else {
        return;
    }

    try {
        $existingStmt = $pdo->prepare("SELECT `$eplPartyFk` FROM election_party_list WHERE `$eplElectionFk` = :eid");
        $existingStmt->execute([':eid'=>$electionId]);
        $existing = $existingStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $del = $pdo->prepare("DELETE FROM election_party_list WHERE `$eplElectionFk` = :eid AND `$eplPartyFk` = :pid");
        foreach ($existing as $exId) {
            if (!in_array($exId, $partyIds, true)) $del->execute([':eid'=>$electionId, ':pid'=>$exId]);
        }
        $ins = $pdo->prepare("INSERT INTO election_party_list (`$eplElectionFk`,`$eplPartyFk`) VALUES (:eid, :pid)");
        foreach ($partyIds as $pid) {
            if (!in_array($pid, $existing, true)) $ins->execute([':eid'=>$electionId, ':pid'=>$pid]);
        }
    } catch (Exception $e) { return; }
}

/* read input (JSON or form) */
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST ?? [];
if (empty($input) && !empty($_GET)) $input = $_GET;
$debug = filter_var(getVal($input,'debug',false), FILTER_VALIDATE_BOOLEAN);

/* tolerant mapping */
$in_electionId = trim((string)getVal($input,'electionId', getVal($input,'id','')));
$in_name = trim((string)getVal($input,'name', getVal($input,'Title', getVal($input,'title', getVal($input,'electionName','')))));
$in_orgName = trim((string)getVal($input,'organization', getVal($input,'Organization','')));
$in_org_id_raw = getVal($input,'organization_id', getVal($input,'Org_ID', getVal($input,'org_id', null)));
$in_startRaw = getVal($input,'startDate', getVal($input,'start_date', null));
$in_endRaw = getVal($input,'endDate', getVal($input,'end_date', null));
$in_totalV = intval(getVal($input,'totalVoters', getVal($input,'total_voters', 0)));
$in_partyArr = getVal($input,'partyLists', null);
$in_partyCsv = trim((string)getVal($input,'party_lists',''));
$in_partyJson = getVal($input,'partyListsJson', null);
// intentionally ignore client-sent status; server will compute

/* prepare party lists */
$party_arr = [];
if (is_array($in_partyArr)) $party_arr = array_values(array_filter(array_map('trim',$in_partyArr)));
elseif (!empty($in_partyJson)) {
    $tmp = json_decode($in_partyJson, true);
    if (is_array($tmp)) $party_arr = array_values(array_filter(array_map('trim',$tmp)));
}
if (empty($party_arr) && $in_partyCsv !== '') $party_arr = array_values(array_filter(array_map('trim', explode(',', $in_partyCsv))));
$party_lists_json = $party_arr ? json_encode($party_arr, JSON_UNESCAPED_UNICODE) : null;
$party_lists_csv  = $party_arr ? implode(',', $party_arr) : ($in_partyCsv ?: null);

/* parse dates */
$start_sql = parseToSqlDatetime($in_startRaw, false);
$end_sql   = parseToSqlDatetime($in_endRaw, true);

/* compute status SERVER-SIDE (client can't override) */
$statusLower = '';
$now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
if ($start_sql && $end_sql) {
    try {
        $s = new DateTimeImmutable($start_sql, new DateTimeZone(date_default_timezone_get()));
        $e = new DateTimeImmutable($end_sql, new DateTimeZone(date_default_timezone_get()));
        if ($now < $s) $statusLower = 'upcoming';
        elseif ($now >= $s && $now <= $e) $statusLower = 'ongoing';
        else $statusLower = 'completed';
    } catch (Exception $ex) {
        $statusLower = 'upcoming';
    }
} else {
    // fallback when dates missing
    $statusLower = 'ongoing';
}

/* detect table and columns */
try {
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = :schema AND TABLE_NAME IN ('election','elections') LIMIT 1");
    $stmt->execute([':schema'=>$dbName]); $found = $stmt->fetchColumn();
    $table = $found ?: 'election';
} catch (Exception $e) { $table = 'election'; }

try {
    $cstmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table");
    $cstmt->execute([':schema'=>$dbName, ':table'=>$table]); $cols = $cstmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) { $cols = []; }

function hasCol(array $cols, $names) {
    foreach ((array)$names as $n) if (in_array($n,$cols,true)) return $n;
    return false;
}

/* resolve organization id if possible */
$resolvedOrgId = null;
if (!empty($in_org_id_raw)) {
    $resolvedOrgId = trim((string)$in_org_id_raw);
} elseif ($in_orgName !== '') {
    try {
        $s = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = :schema AND table_name = 'student_organization'");
        $s->execute([':schema'=>$dbName]); $orgCols = $s->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $orgNameCol = hasCol($orgCols, ['Org_Name','org_name','Organization','organization','Name','name']);
        $orgIdCol   = hasCol($orgCols, ['Org_ID','org_id','id','ID']);
        if ($orgNameCol && $orgIdCol) {
            $q = $pdo->prepare("SELECT `$orgIdCol` FROM student_organization WHERE LOWER(`$orgNameCol`) = LOWER(:nm) LIMIT 1");
            $q->execute([':nm' => $in_orgName]); $val = $q->fetchColumn();
            if ($val) $resolvedOrgId = $val;
            else {
                $q2 = $pdo->prepare("SELECT `$orgIdCol` FROM student_organization WHERE LOWER(`$orgNameCol`) LIKE LOWER(:nm) LIMIT 1");
                $q2->execute([':nm' => '%' . $in_orgName . '%']); $val2 = $q2->fetchColumn();
                if ($val2) $resolvedOrgId = $val2;
            }
        }
    } catch (Exception $e) { /* ignore */ }
}

/* map incoming values to detected columns */
$fieldMap = [];
if ($col = hasCol($cols,['Title','title','name','Name','Election_Title','election_title'])) $fieldMap[$col] = $in_name;
if ($col = hasCol($cols,['Organization','organization','organization_name','Organization_Name'])) $fieldMap[$col] = $in_orgName;
if ($resolvedOrgId !== null) {
    if ($col = hasCol($cols, ['Org_ID','org_id','organization_id','OrgId','Organization_ID','orgid'])) $fieldMap[$col] = $resolvedOrgId;
    else if ($col = hasCol($cols,['Organization','organization','organization_name','Organization_Name'])) $fieldMap[$col] = $in_orgName;
} elseif (!empty($in_org_id_raw)) {
    if ($col = hasCol($cols, ['Org_ID','org_id','organization_id','OrgId','Organization_ID','orgid'])) $fieldMap[$col] = trim((string)$in_org_id_raw);
}

if ($col = hasCol($cols,['start_date','Start_Date','StartDate','startDate'])) $fieldMap[$col] = $start_sql;
if ($col = hasCol($cols,['end_date','End_Date','EndDate','endDate'])) $fieldMap[$col] = $end_sql;
if ($col = hasCol($cols,['total_voters','Total_Voters','totalVoters'])) $fieldMap[$col] = $in_totalV;
if ($col = hasCol($cols,['party_lists','party_lists_csv','partyLists','Party_Lists'])) $fieldMap[$col] = $party_lists_csv;
if ($col = hasCol($cols,['party_lists_json','partyListsJson','party_lists_json'])) $fieldMap[$col] = $party_lists_json;

/* ensure computed status always saved (server-side) */
if ($col = hasCol($cols,['status','Status'])) {
    $fieldMap[$col] = $statusLower;
}

/* validate title */
$titleValue = null;
foreach (['Title','title','name','Name','Election_Title','election_title'] as $t) if (isset($fieldMap[$t])) { $titleValue = $fieldMap[$t]; break; }
if ($titleValue === null || trim((string)$titleValue) === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Election title/name is required']);
    exit;
}

$idCol = hasCol($cols, ['id','ID','Election_ID','election_id','electionID']) ?: 'id';

/* insert / update */
try {
    $pdo->beginTransaction();

    if (!empty($in_electionId)) {
        // UPDATE
        $setParts = []; $params = [':id'=>$in_electionId];
        foreach ($fieldMap as $col => $val) {
            $ph = ':col_' . preg_replace('/[^a-zA-Z0-9_]/','', $col);
            $setParts[] = "`$col` = $ph";
            $params[$ph] = $val;
        }
        if (in_array('updated_at',$cols,true)) $setParts[] = "`updated_at` = NOW()";
        if (count($setParts) === 0) { $pdo->commit(); echo json_encode(['success'=>true,'message'=>'Nothing to update','electionId'=>$in_electionId]); exit; }
        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE `$idCol` = :id LIMIT 1";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);

        if (!empty($party_arr)) {
            try { syncElectionPartyLists($pdo, $dbName, $in_electionId, $party_arr); } catch (Exception $e) { /* ignore */ }
        }

        $fetch = $pdo->prepare("SELECT * FROM `$table` WHERE `$idCol` = :id LIMIT 1");
        $fetch->execute([':id'=>$in_electionId]); $row = $fetch->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        $out = ['success'=>true,'message'=>'Election updated','data'=>$row];
        if ($debug) $out['debug'] = ['table'=>$table,'detected_columns'=>$cols,'fieldMap'=>$fieldMap,'resolvedOrgId'=>$resolvedOrgId ?? null,'computed_status'=>$statusLower];
        echo json_encode($out);
        exit;
    } else {
        // INSERT
        $colsInsert = []; $placeholders = []; $params = [];
        foreach ($fieldMap as $col => $val) {
            $colsInsert[] = "`$col`";
            $ph = ':col_' . preg_replace('/[^a-zA-Z0-9_]/','', $col);
            $placeholders[] = $ph;
            $params[$ph] = $val;
        }
        if (in_array('created_at',$cols,true) && in_array('updated_at',$cols,true)) {
            $colsInsert[] = "`created_at`"; $colsInsert[] = "`updated_at`";
            $placeholders[] = "NOW()"; $placeholders[] = "NOW()";
        }
        if (count($colsInsert) === 0) {
            http_response_code(400); echo json_encode(['success'=>false,'message'=>'No writable columns found on target table']); exit;
        }
        $sql = "INSERT INTO `$table` (" . implode(', ', $colsInsert) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $newId = $pdo->lastInsertId();

        if (!empty($party_arr) && !empty($newId)) {
            try { syncElectionPartyLists($pdo, $dbName, $newId, $party_arr); } catch (Exception $e) { /* ignore */ }
        }

        $fetch = $pdo->prepare("SELECT * FROM `$table` WHERE `$idCol` = :id LIMIT 1");
        $fetch->execute([':id'=>$newId]); $row = $fetch->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        $out = ['success'=>true,'message'=>'Election created','data'=>$row,'newId'=>$newId];
        if ($debug) $out['debug'] = ['table'=>$table,'detected_columns'=>$cols,'fieldMap'=>$fieldMap,'resolvedOrgId'=>$resolvedOrgId ?? null,'computed_status'=>$statusLower,'party_lists'=>$party_arr];
        echo json_encode($out);
        exit;
    }

} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("save_election.php error: " . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error while saving election','error'=>$ex->getMessage()]);
    exit;
}
