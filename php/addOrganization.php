<?php
// php/addOrganization.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Optional: require admin session
$roleOk = false;
if (!empty($_SESSION['role']) && preg_match('/admin/i', $_SESSION['role'])) $roleOk = true;
if (!empty($_SESSION['user_type']) && preg_match('/admin/i', $_SESSION['user_type'])) $roleOk = true;

// If you don't want an auth check during dev, comment out the next block.
// Keep it in production.
if (empty($_SESSION['user_id']) || !$roleOk) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - admin required']);
    exit;
}

// DB config - adjust if your db.php provides $mysqli instead, you can include it instead
$dbHost = 'localhost'; $dbUser = 'root'; $dbPass = ''; $dbName = 'eboto_db';
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed: ' . $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset('utf8mb4');

// sanitize helper
function clean($s) { return trim($s === null ? '' : (string)$s); }

// Read POST fields
$orgName = clean($_POST['orgName'] ?? '');
$orgDescription = clean($_POST['orgDescription'] ?? '');
$orgEmail = clean($_POST['orgEmail'] ?? '');

// basic validation
if ($orgName === '' || $orgEmail === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Organization name and email are required.']);
    exit;
}

// prepare logo upload handling
$uploadDir = __DIR__ . '/../assets/org_logos/'; // ensure this directory exists and is writable
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
    if (!is_dir($uploadDir)) {
        // fallback to public assets folder
        $uploadDir = __DIR__ . '/../assets/';
    }
}

$logoUrl = null;
if (!empty($_FILES['orgLogo']) && $_FILES['orgLogo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['orgLogo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Logo upload error: ' . $file['error']]);
        exit;
    }

    // Validate size (<= 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Logo exceeds 2MB limit.']);
        exit;
    }

    // Validate mime / extension
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/jpg' => 'jpg'];
    if (!isset($allowed[$mime])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid logo file type. Allowed: jpg, png, gif.']);
        exit;
    }

    $ext = $allowed[$mime];
    try {
        $basename = bin2hex(random_bytes(8)); // unique
    } catch (Exception $e) {
        $basename = uniqid('logo_', true);
    }
    $filename = $basename . '.' . $ext;
    $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file.']);
        exit;
    }

    // store relative URL for DB (adjust to your public path)
    $publicPath = '../assets/org_logos/' . $filename;
    $logoUrl = $publicPath;
}

// create hashed password placeholder (if your schema requires Password)
$hashedPassword = password_hash($orgEmail . time(), PASSWORD_DEFAULT);

// Insert into DB (prepared)
$sql = "INSERT INTO student_organization (Org_Name, Description, Email, Password, Logo_Url, Registered_At)
        VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}
$logoParam = $logoUrl ?? null;
$stmt->bind_param('sssss', $orgName, $orgDescription, $orgEmail, $hashedPassword, $logoParam);
$ok = $stmt->execute();
if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// Return JSON with created org
$out = [
    'Org_ID' => (int)$newId,
    'Org_Name' => $orgName,
    'Description' => $orgDescription,
    'Email' => $orgEmail,
    'Logo_Url' => $logoUrl ?? '../assets/default-logo.jpg',
    'Total_Voters' => 0,
    'Pending' => 0
];

echo json_encode(['success' => true, 'org' => $out]);
$mysqli->close();
exit;
