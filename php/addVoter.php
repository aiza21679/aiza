<?php
// php/addVoter.php
session_start();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/register-voters.php');
    exit;
}

// DB connection
$mysqli = new mysqli('localhost','root','','eboto_db');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed: '.$mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Read POST values (names match the form in register-voters.php)
$student_id_raw = trim($_POST['student_id'] ?? '');
$student_id = $student_id_raw === '' ? null : (is_numeric($student_id_raw) ? (int)$student_id_raw : (int)$student_id_raw);
$full_name  = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$course     = trim($_POST['course'] ?? '');
$year_level_raw = trim($_POST['year_level'] ?? '');
$year_level = $year_level_raw === '' ? null : (is_numeric($year_level_raw) ? (int)$year_level_raw : null);
$org_id     = isset($_POST['Org_ID']) && $_POST['Org_ID'] !== '' ? (int)$_POST['Org_ID'] : null;
$status     = trim($_POST['status'] ?? 'Active');
$orgs_submitted = $_POST['organizations'] ?? [];
$orgs_submitted = array_values(array_filter(array_map('intval', (array)$orgs_submitted)));

// Basic validation
$errors = [];
if ($student_id_raw === '') $errors[] = 'Student ID required';
if ($full_name === '') $errors[] = 'Full name required';
if ($email === '') $errors[] = 'Email required';
if ($year_level === null) $errors[] = 'Year Level required';

if (!empty($errors)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(400);
        echo json_encode(['error' => implode('; ', $errors)]);
        exit;
    } else {
        $msg = urlencode(implode('; ', $errors));
        header("Location: ../admin/register-voters.php?error={$msg}");
        exit;
    }
}

// Build INSERT SQL dynamically to omit Org_ID when null
$cols = ['Student_ID','Name','Email','Course','Year_Level','status'];
$params = [$student_id, $full_name, $email, $course, $year_level, $status];
$types = 'isssis'; // placeholder, will be rebuilt

if ($org_id !== null && $org_id !== 0) {
    // insert Org_ID after Student_ID to keep your column ordering if you like
    $cols = ['Student_ID','Org_ID','Name','Email','Course','Year_Level','status'];
    $params = [$student_id, $org_id, $full_name, $email, $course, $year_level, $status];
}

// Build types dynamically
$types = '';
foreach ($params as $p) {
    if (is_int($p) || is_float($p)) $types .= 'i';
    else $types .= 's';
}

$colSql = implode(', ', array_map(function($c){ return "`$c`"; }, $cols));
$placeholders = implode(', ', array_fill(0, count($cols), '?'));
$sql = "INSERT INTO `student` ({$colSql}) VALUES ({$placeholders})";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: '.$mysqli->error, 'sql'=>$sql]);
    exit;
}

// bind params by reference
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

if (!$stmt->execute()) {
    // if duplicate primary key or other DB error, send it back
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed: '.$stmt->error]);
    $stmt->close();
    $mysqli->close();
    exit;
}

// Insert succeeded — map to student_organization_members if boxes checked
if (!empty($orgs_submitted)) {
    $tblCheck = $mysqli->query("SHOW TABLES LIKE 'student_organization_members'");
    if ($tblCheck && $tblCheck->num_rows > 0) {
        $insertMap = $mysqli->prepare("INSERT IGNORE INTO student_organization_members (org_id, student_id) VALUES (?, ?)");
        if ($insertMap) {
            foreach ($orgs_submitted as $oid) {
                $oidInt = (int)$oid;
                $sidStr = (string)$student_id; // mapping may store student_id as string
                $insertMap->bind_param('is', $oidInt, $sidStr);
                @ $insertMap->execute();
            }
            $insertMap->close();
        }
    }
}

$stmt->close();
$mysqli->close();

// Success response: return JSON for AJAX or redirect back
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['success' => true, 'student_id' => $student_id, 'org_id' => $org_id]);
    exit;
} else {
    // preserve ?org=... if present
    $redir = '../admin/register-voters.php' . ($org_id ? '?org=' . (int)$org_id : '');
    header('Location: ' . $redir);
    exit;
}
