<?php
// php/fetchOrganizations.php
// Returns JSON array of organizations with Total_Voters and Pending counts.
// Uses only `student` table (no student_organization_members).
// Optional debug: ?debug=1

header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'eboto_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed: '.$mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

$debug = (!empty($_GET['debug']) && $_GET['debug'] == '1');

// fetch organizations
$orgs = [];
$res = $mysqli->query("SELECT Org_ID, Org_Name, Description, Logo_Url, Email FROM student_organization ORDER BY Org_Name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $orgs[] = $r;
    $res->close();
}

// check student columns
$studentCols = [];
$cRes = $mysqli->query("SHOW COLUMNS FROM `student`");
if ($cRes) {
    while ($c = $cRes->fetch_assoc()) $studentCols[] = $c['Field'];
    $cRes->close();
}
$hasOrgCol = in_array('Org_ID', $studentCols, true) || in_array('org_id', $studentCols, true);
$orgCol = in_array('Org_ID', $studentCols, true) ? 'Org_ID' : (in_array('org_id', $studentCols, true) ? 'org_id' : null);

// prepare statements (we bind as strings to be tolerant of INT/VARCHAR storage)
$countStmt = null;
$pendingStmt = null;

if ($hasOrgCol && $orgCol !== null) {
    // total count - using parameter as string to be tolerant if Org_ID stored as varchar
    $countSql = "SELECT COUNT(*) AS c FROM `student` WHERE `$orgCol` = ?";
    $countStmt = $mysqli->prepare($countSql);

    // pending (case-insensitive)
    $pendingSql = "SELECT COUNT(*) AS c FROM `student` WHERE `$orgCol` = ? AND LOWER(COALESCE(`status`, '')) = 'pending'";
    $pendingStmt = $mysqli->prepare($pendingSql);
}

// build output
$out = [];
foreach ($orgs as $org) {
    $orgId = (string)$org['Org_ID'];
    $total = 0;
    $pending = 0;
    $debug_info = null;

    if ($hasOrgCol && $countStmt) {
        // bind as string for safety
        $countStmt->bind_param('s', $orgId);
        $countStmt->execute();
        $g = $countStmt->get_result();
        if ($g && $row = $g->fetch_assoc()) $total = (int)$row['c'];
        if ($g) $g->free();

        $pendingStmt->bind_param('s', $orgId);
        $pendingStmt->execute();
        $g2 = $pendingStmt->get_result();
        if ($g2 && $row2 = $g2->fetch_assoc()) $pending = (int)$row2['c'];
        if ($g2) $g2->free();

        if ($debug) {
            // also return sample rows for debugging (first matching student rows)
            $d = $mysqli->prepare("SELECT Student_ID, `status`, `$orgCol` AS org_col_value FROM `student` WHERE `$orgCol` = ? LIMIT 5");
            if ($d) {
                $d->bind_param('s', $orgId);
                $d->execute();
                $r = $d->get_result();
                $sample = [];
                if ($r) {
                    while ($row = $r->fetch_assoc()) $sample[] = $row;
                    $r->free();
                }
                $d->close();
                $debug_info = [
                    'sample_students' => $sample
                ];
            }
        }
    } else {
        $total = 0;
        $pending = 0;
    }

    $item = [
        'Org_ID'       => is_numeric($org['Org_ID']) ? (int)$org['Org_ID'] : $org['Org_ID'],
        'Org_Name'     => $org['Org_Name'],
        'Description'  => $org['Description'],
        'Logo_Url'     => $org['Logo_Url'],
        'Email'        => $org['Email'],
        'Total_Voters' => $total,
        'Pending'      => $pending
    ];
    if ($debug) $item['_debug'] = $debug_info;

    $out[] = $item;
}

if ($countStmt) $countStmt->close();
if ($pendingStmt) $pendingStmt->close();

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$mysqli->close();
exit;
