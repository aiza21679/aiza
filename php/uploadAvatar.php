<?php
// uploadAvatar.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // set to 1 temporarily while debugging
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

/* Include db.php (try common locations) */
$dbCandidates = [
    __DIR__ . '/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../db.php',
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

$dbAvailable = (isset($pdo) || isset($mysqli));

// Determine adminId (POST field or session)
$adminId = null;
if (!empty($_POST['adminId'])) {
    $adminId = (int)$_POST['adminId'];
} elseif (isset($_SESSION['user_id'])) {
    $adminId = (int)$_SESSION['user_id'];
}

// Validate uploaded file
if (!isset($_FILES['avatar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded (field: avatar).']);
    exit;
}

$file = $_FILES['avatar'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $map = [
      UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds upload_max_filesize.',
      UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds MAX_FILE_SIZE.',
      UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
      UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];
    $msg = $map[$file['error']] ?? 'Unknown upload error.';
    echo json_encode(['success' => false, 'message' => $msg, 'code' => $file['error']]);
    exit;
}

$allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!array_key_exists($mime, $allowed)) {
    http_response_code(415);
    echo json_encode(['success' => false, 'message' => 'Unsupported file type. Use JPG, PNG or WEBP.']);
    exit;
}

$maxBytes = 3 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'File too large. Max 3MB.']);
    exit;
}

/* Prepare uploads dir (relative to php/) */
$uploadsDir = __DIR__ . '/../uploads/avatars';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create uploads directory.']);
        exit;
    }
}

/* Save file */
$ext = $allowed[$mime];
$basename = bin2hex(random_bytes(12));
$filename = $basename . '.' . $ext;
$target = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    exit;
}
@chmod($target, 0644);

/* Build relative path for frontend (adjust if your front-end expects different prefix) */
$relativePath = '../uploads/avatars/' . $filename;

/* Try update DB if available and adminId known */
if ($dbAvailable && $adminId) {
    try {
        if (isset($pdo)) {
            // find avatar-like column
            $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin'");
            $colStmt->execute();
            $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
            $avatarCol = null;
            foreach ($cols as $c) {
                $lc = strtolower($c);
                if (in_array($lc, ['avatar','avatar_path','photo'])) { $avatarCol = $c; break; }
            }
            $colName = $avatarCol ?? 'Avatar';
            $sql = "UPDATE admin SET {$colName} = :avatar WHERE Admin_ID = :id";
            $u = $pdo->prepare($sql);
            $u->execute([':avatar' => $relativePath, ':id' => $adminId]);
        } else {
            $avatarEsc = $mysqli->real_escape_string($relativePath);
            $idEsc = intval($adminId);
            $mysqli->query("UPDATE admin SET Avatar = '{$avatarEsc}' WHERE Admin_ID = {$idEsc}");
        }
    } catch (Exception $e) {
        // still return success; include warning
        echo json_encode(['success' => true, 'path' => $relativePath, 'warning' => 'Saved file but DB update failed', 'detail' => $e->getMessage()]);
        exit;
    }
}

/* Success response */
echo json_encode(['success' => true, 'path' => $relativePath]);
exit;
