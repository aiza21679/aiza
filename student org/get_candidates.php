<?php
// /eBotoni/eBotoni/student org/get_candidates.php
// Returns JSON array of candidates used by the student/candidates.php frontend.
// Supports optional filtering by ?program=<program name> or ?department=<IT|ESM|...>

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

/* ------------------- Avatar resolver (non-invasive) ------------------- */
function svg_initials_datauri(string $initials): string {
    $bg = '#2D9CDB';
    $fg = '#ffffff';
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='256' height='256'>"
         . "<rect width='100%' height='100%' fill='{$bg}' rx='40' ry='40'/>"
         . "<text x='50%' y='50%' font-family='Segoe UI, Roboto, Arial, sans-serif' font-size='96' fill='{$fg}' dominant-baseline='middle' text-anchor='middle'>{$initials}</text>"
         . "</svg>";
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function resolve_avatar_url(?string $dbPath, ?string $email = null, ?string $name = null): string {
    $projectPrefix = '/eBotoni/eBotoni/';
    $defaultAvatar = $projectPrefix . 'assests/default-avatar.jpg';
    $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

    $p = trim((string)($dbPath ?? ''));

    if ($p !== '' && preg_match('#^https?://#i', $p)) {
        return $p;
    }

    if ($p !== '' && isset($p[0]) && $p[0] === '/') {
        $fs = $docroot . $p;
        if (@file_exists($fs)) return $p;
        return $p;
    }

    $avatarBase = '/eBotoni/uploads/avatars/';
    if ($p !== '') {
        $candidate1 = rtrim($avatarBase, '/') . '/' . ltrim($p, '/');
        $fs1 = $docroot . $candidate1;
        if (@file_exists($fs1)) return $candidate1;

        $candidate2 = rtrim($projectPrefix, '/') . '/' . ltrim($p, '/');
        $fs2 = $docroot . $candidate2;
        if (@file_exists($fs2)) return $candidate2;

        $fs3 = $docroot . '/' . ltrim($p, '/');
        if (@file_exists($fs3)) return '/' . ltrim($p, '/');
    }

    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=256&d=identicon&r=g";
    }

    if ($name && trim($name) !== '') {
        $u = rawurlencode($name);
        return "https://ui-avatars.com/api/?name={$u}&size=256&rounded=true&format=png";
    }

    $initials = 'U';
    if ($name && trim($name) !== '') {
        $parts = preg_split('/\s+/', trim($name));
        $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
    return svg_initials_datauri($initials);
}

/* ------------------- DB connection ------------------- */
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

/* ------------------- department / program filtering ------------------- */
$department_map = [
    'IT'  => ['BS INFORMATION TECHNOLOGY','BS Information Technology','BS INFORMATION TECHNOLOGY (DAY)'],
    'ESM' => ['BS ESM','BS ENERGY MANAGEMENT','BS ENERGY ENGINEERING','BS ENERGY TECHNOLOGY','Energy Systems and Management','Energy Systems & Management'],
    'TCM' => ['BS TCM','Technology Communication Management','TCM'],
    'MET' => ['BS MET','Mechanical Engineering Technology','Manufacturing Engineering Technology','MET'],
    'NAME'=> ['BS NAME','Naval Architecture and Marine Engineering','NAME']
];

$program_q = isset($_GET['program']) ? trim($_GET['program']) : '';
$department_q = isset($_GET['department']) ? strtoupper(trim($_GET['department'])) : '';

$where_clauses = [];
if ($program_q !== '') {
    $p = $mysqli->real_escape_string($program_q);
    $where_clauses[] = "UPPER(TRIM(c.Program)) = UPPER(TRIM('{$p}'))";
} elseif ($department_q !== '') {
    if (array_key_exists($department_q, $department_map) && count($department_map[$department_q])>0) {
        $programs_for_dept = $department_map[$department_q];
        $escaped = array_map(function($v) use ($mysqli){ return $mysqli->real_escape_string($v); }, $programs_for_dept);
        $exact_conditions = array_map(function($v){ return "UPPER(TRIM(c.Program)) = UPPER(TRIM('{$v}'))"; }, $escaped);
        $like_token = $mysqli->real_escape_string($department_q);
        $where_clauses[] = '(' . implode(' OR ', $exact_conditions) . " OR UPPER(c.Program) LIKE '%" . strtoupper($like_token) . "%')";
    } else {
        $dept_esc = $mysqli->real_escape_string($department_q);
        $where_clauses[] = "UPPER(c.Program) LIKE '%" . strtoupper($dept_esc) . "%'";
    }
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

/* ------------------- main SQL ------------------- */
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
    echo json_encode([
        'success' => false,
        'error' => 'Query failed',
        'detail' => $mysqli->error,
        'sql' => $sql
    ]);
    exit;
}

/* ------------------- produce rows, but resolve Avatar safely and add PartyKey ------------------- */
$rows = [];
$AVATAR_BASE = '/eBotoni/uploads/avatars/';
$ALT_AVATAR_BASE = '/eBotoni/eBotoni/student org/uploads/candidates/';
$DOCROOT = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

while ($r = $result->fetch_assoc()) {
    $r['Program'] = trim((string)($r['Program'] ?? ''));
    $rawAvatar = trim((string)($r['Avatar'] ?? ''));

    if ($rawAvatar !== '' && !preg_match('#^https?://#i', $rawAvatar) && strpos($rawAvatar, '/') !== 0) {
        $candidateA = rtrim($AVATAR_BASE, '/') . '/' . ltrim($rawAvatar, '/');
        $fsA = $DOCROOT . $candidateA;

        if (@file_exists($fsA)) {
            $rawAvatar = $candidateA;
        } else {
            $candidateB = rtrim($ALT_AVATAR_BASE, '/') . '/' . ltrim($rawAvatar, '/');
            $fsB = $DOCROOT . $candidateB;

            if (@file_exists($fsB)) {
                $rawAvatar = $candidateB;
            } else {
                $rawAvatar = $candidateA;
            }
        }
    }

    $resolved = resolve_avatar_url($rawAvatar, $r['Email'] ?? null, $r['FullName'] ?? null);

    // Normalized party key: lowercase, remove common suffix words, collapse spaces
    $r['PartyList'] = $r['PartyList'] ?? '';
    $partyRaw = trim((string)$r['PartyList']);
    $partyNorm = mb_strtolower($partyRaw, 'UTF-8');
    $partyNorm = preg_replace('/\b(party|alliance|coalition)\b/ui', '', $partyNorm);
    $partyNorm = preg_replace('/\s+/', ' ', $partyNorm);
    $partyNorm = trim($partyNorm);
    if ($partyNorm === '') $partyNorm = 'independent';

    // put normalized key and resolved avatar in the output
    $r['Avatar'] = $resolved;
    $r['PartyKey'] = $partyNorm;

    $rows[] = $r;
}

$result->free();
$mysqli->close();

echo json_encode($rows);
exit;
