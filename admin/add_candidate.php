<?php
// /eBotoni/eBotoni/student org/get_candidates.php
// Returns JSON array of candidates used by the student/candidates.php frontend.
// Supports optional filtering by ?program=<program name> or ?department=<IT|ESM|...>

// Basic safety: respond JSON and set header
header('Content-Type: application/json; charset=utf-8');

// DB connection — adjust if you keep db.php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'eboto_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed', 'detail' => $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

/*
 Accept optional query parameters:
  - program=BS%20INFORMATION%20TECHNOLOGY  (exact program name)
  - department=IT  (alias mapping to a set of programs)
 If none provided -> return all candidates.
*/

// canonical list / mapping (uppercase keys for robust comparison)
$allowed_programs_master = [
    'BS INFORMATION TECHNOLOGY',
    'BS ESM',
    'BS TCM',
    'BS MET',
    'BS NAME'
];

// map department aliases to program names — extend as needed
$department_map = [
    'IT'  => ['BS INFORMATION TECHNOLOGY'],
    'ESM' => ['BS ESM', 'BS ENERGY MANAGEMENT', 'BS ENERGY ENGINEERING'], // example extras if you use them
    // add other mappings if needed
];

// read and normalize query params
$program_q = isset($_GET['program']) ? trim($_GET['program']) : '';
$department_q = isset($_GET['department']) ? strtoupper(trim($_GET['department'])) : '';

// build where clause parts
$where_clauses = [];
// parameters not used with prepared stmt here — using escaping below
if ($program_q !== '') {
    // match exact program (case-insensitive)
    $p = $mysqli->real_escape_string($program_q);
    // comparing uppercased program in SQL for case-insensitive match
    $where_clauses[] = "UPPER(TRIM(c.Program)) = UPPER(TRIM('{$p}'))";
} elseif ($department_q !== '') {
    if (array_key_exists($department_q, $department_map)) {
        $programs_for_dept = $department_map[$department_q];
        // build IN (...) list, escaping each value
        $escaped = array_map(function($v) use ($mysqli){ return "'".$mysqli->real_escape_string($v)."'"; }, $programs_for_dept);
        $inlist = implode(',', $escaped);
        $where_clauses[] = "UPPER(TRIM(c.Program)) IN (" . implode(',', array_map(function($v){ return "UPPER(TRIM({$v}))"; }, $escaped)) . ")";
    } else {
        // if unknown department, try matching department term inside program name
        $dept_esc = $mysqli->real_escape_string($department_q);
        // keep records where program contains department term (case-insensitive)
        $where_clauses[] = "UPPER(c.Program) LIKE '%" . strtoupper($dept_esc) . "%'";
    }
}

// (Optional) you can add other filters later (position, yearlevel, etc.)

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

/*
 Select columns and alias them so frontend normalization is straightforward.
 Adjust column names if your schema differs.
 Note: we apply the $where_sql here so the DB returns only matching rows.
*/
$sql = "
SELECT
  c.Candidate_ID AS id,
  c.FullName,
  c.Email,
  c.Student_ID,
  c.YearLevel,
  c.Position_ID,
  COALESCE(p.Position_Name, '') AS Position_Name,
  c.Program,
  COALESCE(c.Platform,'') AS Platform,
  COALESCE(c.Experience,'') AS Experience,
  COALESCE(c.Avatar,'') AS Avatar,
  COALESCE(c.status,'') AS Status,
  COALESCE(c.PartyList,'') AS PartyList,
  c.Org_ID,
  COALESCE(o.Org_Name,'') AS Org_Name
FROM candidate c
LEFT JOIN position p ON c.Position_ID = p.Position_ID
LEFT JOIN student_organization o ON c.Org_ID = o.Org_ID
{$where_sql}
ORDER BY c.FullName
";

$result = $mysqli->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query failed', 'detail' => $mysqli->error, 'sql' => $sql]);
    exit;
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    // Normalize avatar to be a usable path by the frontend:
    // If it's a filename (no leading slash and not a URL) prepend the uploads folder.
    // Adjust $AVATAR_BASE to your uploads folder.
    $AVATAR_BASE = '/eBotoni/uploads/avatars/'; // change if needed

    $avatar = trim((string)($r['Avatar'] ?? ''));
    if ($avatar !== '') {
        if (!preg_match('#^https?://#i', $avatar) && strpos($avatar, '/') !== 0) {
            $r['Avatar'] = $AVATAR_BASE . $avatar;
        } else {
            // keep as is if absolute path or URL
            $r['Avatar'] = $avatar;
        }
    } else {
        $r['Avatar'] = ''; // frontend will supply default image
    }

    // ensure Program has consistent casing (optional)
    $r['Program'] = trim($r['Program']);

    $rows[] = $r;
}

$result->free();
$mysqli->close();

// output rows (frontend expects an array)
echo json_encode($rows);
exit;
