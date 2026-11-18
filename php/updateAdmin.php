<?php
// updateAdmin.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

/* include db.php */
$dbCandidates = [
    __DIR__ . '/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../db.php',
    rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/eBotoni/eBotoni/student/db.php',
    __DIR__ . '/../student/db.php'
];

foreach ($dbCandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        break;
    }
}

/* Read JSON input */
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

$id = isset($input['id']) ? (int)$input['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
if (!$id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No admin id provided and no session available.']);
    exit;
}

/* Build update */
$set = [];
$params = [':id' => $id];
if (isset($input['name'])) { $set[] = "Name = :name"; $params[':name'] = $input['name']; }
if (isset($input['role'])) { $set[] = "position = :role"; $params[':role'] = $input['role']; } // adjust column name if needed
if (isset($input['avatar'])) { $set[] = "Avatar = :avatar"; $params[':avatar'] = $input['avatar']; }

if (empty($set)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update.']);
    exit;
}

try {
    if (isset($pdo)) {
        $sql = "UPDATE admin SET " . implode(', ', $set) . " WHERE Admin_ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } elseif (isset($mysqli)) {
        $updates = [];
        if (isset($input['name'])) $updates[] = "Name = '".$mysqli->real_escape_string($input['name'])."'";
        if (isset($input['role'])) $updates[] = "position = '".$mysqli->real_escape_string($input['role'])."'";
        if (isset($input['avatar'])) $updates[] = "Avatar = '".$mysqli->real_escape_string($input['avatar'])."'";
        $sql = "UPDATE admin SET ".implode(',', $updates)." WHERE Admin_ID = ".intval($id);
        $mysqli->query($sql);
    } else {
        echo json_encode(['success' => false, 'message' => 'No DB connection available.']);
        exit;
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB update failed', 'detail' => $e->getMessage()]);
}
exit;
