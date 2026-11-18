<?php
// php/getAdmin.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Candidate db.php locations - adjust if your layout differs */
$dbCandidates = [
    __DIR__ . '/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../db.php',
    __DIR__ . '/db.inc.php',
    rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/eBotoni/eBotoni/student/db.php',
    __DIR__ . '/../student/db.php'
];

$included = false;
foreach ($dbCandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        $included = true;
        break;
    }
}

if (!$included) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration: db.php not found',
        'tried_paths' => $dbCandidates
    ]);
    exit;
}

// Accept either $pdo or $mysqli from db.php
if (!isset($pdo) && !isset($mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DB connection not created by db.php (expected $pdo or $mysqli).'
    ]);
    exit;
}

// Require session user_id (frontend expects some logged-in identity)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'hint' => 'No session user_id detected. Make sure user is logged in and session was started on login.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

try {
    // Discover admin table columns (so we don't select a non-existent Avatar column)
    $adminColumns = [];
    if (isset($pdo)) {
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'admin'");
        $colStmt->execute([':db' => $dbName]);
        $adminColumns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $dbNameResult = $mysqli->query("SELECT DATABASE() AS dbname");
        $row = $dbNameResult ? $dbNameResult->fetch_assoc() : null;
        $dbName = $row ? $row['dbname'] : null;
        $resCols = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$mysqli->real_escape_string($dbName)}' AND TABLE_NAME = 'admin'");
        if ($resCols) {
            while ($r = $resCols->fetch_assoc()) $adminColumns[] = $r['COLUMN_NAME'];
        }
    }

    $colsLower = array_map('strtolower', $adminColumns);

    // Build SELECT list for admin table based on actual columns present
    $selectCols = [];

    if (in_array('admin_id', $colsLower)) {
        $selectCols[] = 'Admin_ID';
    } elseif (in_array('id', $colsLower)) {
        $selectCols[] = 'id';
    }

    if (in_array('name', $colsLower)) {
        $selectCols[] = 'Name';
    } elseif (in_array('first_name', $colsLower) && in_array('last_name', $colsLower)) {
        $selectCols[] = "CONCAT(first_name, ' ', last_name) AS Name";
    }

    if (in_array('email', $colsLower)) {
        $selectCols[] = 'Email';
    }

    // choose an avatar-like column if present
    $avatarColReal = null;
    foreach ($adminColumns as $c) {
        $lc = strtolower($c);
        if ($lc === 'avatar' || $lc === 'avatar_path' || $lc === 'photo') {
            $avatarColReal = $c;
            break;
        }
    }
    if ($avatarColReal) {
        $selectCols[] = "{$avatarColReal} AS Avatar";
    }

    // If nothing determined, fallback to minimal known names
    if (empty($selectCols)) {
        // your admin table at least has Name and Email per screenshots
        $selectCols = ['Name', 'Email'];
    }

    $selectSql = implode(', ', $selectCols);

    // Query admin row only — do NOT attempt to query a users table that may not exist
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT {$selectSql} FROM admin WHERE Admin_ID = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $idEsc = intval($userId);
        $sql = "SELECT {$selectSql} FROM admin WHERE Admin_ID = {$idEsc} LIMIT 1";
        $res = $mysqli->query($sql);
        $admin = $res ? $res->fetch_assoc() : null;
    }

    $role = null;
    $user = null;

    if ($admin) {
        // Found admin row
        $role = 'Administrator';
        $user = [
            'id' => isset($admin['Admin_ID']) ? (int)$admin['Admin_ID'] : (isset($admin['id']) ? (int)$admin['id'] : $userId),
            'name' => $admin['Name'] ?? '',
            'email' => $admin['Email'] ?? '',
            'avatar' => $admin['Avatar'] ?? null
        ];
    } else {
        // No admin row found: fallback to session values (do not query users table)
        $sessionName = isset($_SESSION['name']) ? $_SESSION['name'] : null;
        $sessionEmail = isset($_SESSION['email']) ? $_SESSION['email'] : null;
        $user = [
            'id' => $userId,
            'name' => $sessionName ?: ($userType ? ucfirst($userType) : 'User'),
            'email' => $sessionEmail ?: '',
            'avatar' => null
        ];
        if ($userType && preg_match('/admin/i', $userType)) $role = 'Administrator';
        else $role = $role ?: 'User';
    }

    // final avatar fallback
    $defaultAvatar = '../assests/default-avatar.jpg';
    $avatar = !empty($user['avatar']) ? $user['avatar'] : $defaultAvatar;

    echo json_encode([
        'success' => true,
        'id' => (int)$user['id'],
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $role ?: 'User',
        'avatar' => $avatar
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'detail' => $e->getMessage()
    ]);
    exit;
}
