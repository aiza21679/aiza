<?php
// php/getOrganizationVoters.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// DB config - change if necessary
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    echo json_encode(['error'=>'Database connection failed','mysqli_error'=>$mysqli->connect_error], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}
$mysqli->set_charset('utf8mb4');

// accept org_id from GET or session
$org_id = null;
if (!empty($_GET['org_id'])) $org_id = (int)$_GET['org_id'];
if (!$org_id && !empty($_GET['Org_ID'])) $org_id = (int)$_GET['Org_ID'];
if (!$org_id && !empty($_SESSION['org_id'])) $org_id = (int)$_SESSION['org_id'];
if (!$org_id && !empty($_SESSION['Org_ID'])) $org_id = (int)$_SESSION['Org_ID'];

if (!$org_id) {
    echo json_encode(['error' => 'Missing organization id. Provide ?org_id= or ensure $_SESSION[org_id] is set.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

// make sure student table exists
$hasStudent = $mysqli->query("SHOW TABLES LIKE 'student'")->num_rows > 0;
if (!$hasStudent) {
    echo json_encode(['error'=>'student table missing'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

// inspect student columns for org column
$possibleOrgCols = ['Org_ID','org_id','organization_id','OrganizationID','organizationid','organization','OrgId'];
$studentColsRes = $mysqli->query("SHOW COLUMNS FROM `student`");
$studentCols = [];
while ($c = $studentColsRes->fetch_assoc()) $studentCols[] = $c['Field'];

$orgCol = null;
foreach ($possibleOrgCols as $col) {
    if (in_array($col, $studentCols, true)) {
        $orgCol = $col;
        break;
    }
}

if ($orgCol) {
    $sql = "SELECT Student_ID, Name AS FullName, Email, Year_Level, `status` AS StudentStatus
            FROM `student`
            WHERE `$orgCol` = ?
            ORDER BY Name ASC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error'=>'Failed to prepare query (student table)','mysqli_error'=>$mysqli->error], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // fallback to join table
    $hasMembers = $mysqli->query("SHOW TABLES LIKE 'student_organization_members'")->num_rows > 0;
    if (!$hasMembers) {
        echo json_encode([
            'error' => 'No org column on student table and student_organization_members table is missing.',
            'student_columns' => $studentCols
        ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT s.Student_ID, s.Name AS FullName, s.Email, s.Year_Level, s.`status` AS StudentStatus, m.joined_at
            FROM `student` s
            INNER JOIN student_organization_members m ON m.student_id = s.Student_ID
            WHERE m.org_id = ?
            ORDER BY s.Name ASC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error'=>'Failed to prepare fallback query','mysqli_error'=>$mysqli->error], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
}

// build output
$voters = [];
$stats = ['total'=>0,'active'=>0,'pending'=>0,'inactive'=>0];

while ($r = $res->fetch_assoc()) {
    $status = $r['StudentStatus'] ?? 'Active';
    $statusNorm = ucfirst(strtolower($status));
    $voters[] = [
        'voter_id' => $r['Student_ID'],
        'name' => $r['FullName'] ?: ('Student ' . $r['Student_ID']),
        'email' => $r['Email'] ?? null,
        'student_id' => $r['Student_ID'],
        'year_level' => $r['Year_Level'] ?? null,
        'status' => $statusNorm,
        'registered_at' => $r['joined_at'] ?? null
    ];
    $stats['total']++;
    $st = strtolower($statusNorm);
    if ($st === 'active') $stats['active']++;
    elseif ($st === 'pending') $stats['pending']++;
    elseif ($st === 'inactive' || $st === 'disabled') $stats['inactive']++;
}

$stmt->close();
$mysqli->close();

echo json_encode(['stats'=>$stats,'voters'=>$voters], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
exit;
