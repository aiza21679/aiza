<?php
// save_profile.php
// Updates organization profile (student_organization).
// Returns JSON. No HTML output.

header('Content-Type: application/json; charset=utf-8');

// --- simple error helper ---
function json_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// start session (must be same session your UI uses)
if (session_status() === PHP_SESSION_NONE) session_start();

// require login / org context
$orgId = null;
if (!empty($_SESSION['org_id'])) {
    $orgId = (int) $_SESSION['org_id'];
} elseif (!empty($_POST['org_id'])) {
    // only allow if you want to accept org_id via POST
    $orgId = (int) $_POST['org_id'];
}

if (!$orgId) json_err('Organization not authenticated (no org_id in session).', 403);

// DB config - adjust to match your environment if different
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    error_log('save_profile.php DB connect error: ' . $mysqli->connect_error);
    json_err('Database connection failed.', 500);
}
$mysqli->set_charset('utf8mb4');

// Collect input (use null coalescing)
$name     = isset($_POST['name']) ? trim($_POST['name']) : null;
$email    = isset($_POST['email']) ? trim($_POST['email']) : null;
$phone    = isset($_POST['phone']) ? trim($_POST['phone']) : null;      // optional (may not be stored)
$position = isset($_POST['position']) ? trim($_POST['position']) : null; // optional (may not be stored)
$bio      = isset($_POST['bio']) ? trim($_POST['bio']) : null;

// Basic validation
if ($name === null || $name === '') json_err('Name is required.');
if ($email === null || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('Valid email is required.');

// We'll try to save logo if a file was sent
$uploadedLogoUrl = null;
if (!empty($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    $file = $_FILES['avatar'];
    // basic validations
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed, true)) {
        // allow by extension fallback if MIME missing
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
            json_err('Only JPG/PNG/GIF/WEBP images are allowed for avatar.');
        }
    }
    if ($file['size'] > 4 * 1024 * 1024) { // 4MB limit
        json_err('Avatar must be 4MB or smaller.');
    }

    // prepare upload folder (relative to this script)
    $uploadDir = __DIR__ . '/uploads/org_logos';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log('save_profile.php: failed to create upload dir ' . $uploadDir);
            json_err('Server error creating upload directory.', 500);
        }
    }

    // sanitize filename and generate unique name
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeBase = preg_replace('/[^a-z0-9-_]/i', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $uniq = time() . '-' . bin2hex(random_bytes(6));
    $filename = $safeBase . '-' . $uniq . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log('save_profile.php: move_uploaded_file failed for ' . $file['name']);
        json_err('Failed to save uploaded avatar.', 500);
    }

    // If your web root exposes /eBotoni/eBotoni/student_org/uploads/..., set URL accordingly
    // We'll return a path relative to project root so front-end can use it directly.
    // Example returned path: "student_org/uploads/org_logos/filename.jpg"
    $uploadedLogoUrl = 'student_org/uploads/org_logos/' . $filename;
}

// Build UPDATE query. Only update columns available in your table.
// Based on your screenshot student_organization table has: Org_Name, Description, Email, Logo_Url
// If your schema has different column names, change them here.
$updateParts = [];
$params = [];
$types = '';

// Org_Name (name)
$updateParts[] = 'Org_Name = ?';
$params[] = $name;
$types .= 's';

// Email
$updateParts[] = 'Email = ?';
$params[] = $email;
$types .= 's';

// Description (bio)
$updateParts[] = 'Description = ?';
$params[] = $bio ?? '';
$types .= 's';

// Logo_Url (if uploaded)
if ($uploadedLogoUrl !== null) {
    $updateParts[] = 'Logo_Url = ?';
    $params[] = $uploadedLogoUrl;
    $types .= 's';
}

// If you want to store phone/position but your table doesn't have these columns,
// you must ALTER TABLE to add them. If they exist, uncomment and include them.
// Example (do not uncomment unless columns exist):
// $updateParts[] = 'Phone = ?'; $params[] = $phone; $types .= 's';
// $updateParts[] = 'Position = ?'; $params[] = $position; $types .= 's';

if (count($updateParts) === 0) {
    json_err('Nothing to update.');
}

$sql = 'UPDATE student_organization SET ' . implode(', ', $updateParts) . ' WHERE Org_ID = ? LIMIT 1';
$params[] = $orgId;
$types .= 'i';

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    error_log('save_profile.php prepare failed: ' . $mysqli->error . ' SQL: ' . $sql);
    json_err('Server error preparing update.', 500);
}

// bind params dynamically
$bindNames = [];
$bindNames[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bindNames[] = &$params[$i];
}
// call_user_func_array expects references
call_user_func_array([$stmt, 'bind_param'], $bindNames);

if (!$stmt->execute()) {
    error_log('save_profile.php execute failed: ' . $stmt->error);
    json_err('Failed to update organization profile.', 500);
}
$stmt->close();

// Fetch the updated organization row to return to client
$res = $mysqli->prepare('SELECT Org_ID, Org_Name, Description, Email, Logo_Url, Registered_At FROM student_organization WHERE Org_ID = ? LIMIT 1');
if ($res) {
    $res->bind_param('i', $orgId);
    $res->execute();
    $r = $res->get_result()->fetch_assoc();
    $res->close();

    // If logo url is relative, build absolute url for front-end convenience
    $logo_url = $r['Logo_Url'] ?? null;
    if ($logo_url) {
        // If the logo path is already absolute (starts with http), keep it, otherwise build absolute
        if (!preg_match('#^https?://#i', $logo_url)) {
            // construct absolute URL using current host and request script path
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            // compute project base path: strip filename from script path
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            // Example result: https://localhost/eBotoni/eBotoni/student_org/uploads/...
            $logo_full = $scheme . '://' . $host . $basePath . '/' . ltrim($logo_url, '/');
        } else {
            $logo_full = $logo_url;
        }
    } else {
        $logo_full = null;
    }

    $out = [
        'Org_ID' => (int) $r['Org_ID'],
        'name' => $r['Org_Name'] ?? $name,
        'email' => $r['Email'] ?? $email,
        'bio' => $r['Description'] ?? $bio,
        'avatar_url' => $logo_full
    ];

    echo json_encode(['success' => true, 'data' => $out], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $mysqli->close();
    exit;
} else {
    // couldn't fetch row - return minimal success
    echo json_encode(['success' => true, 'data' => [
        'name' => $name,
        'email' => $email,
        'bio' => $bio,
        'avatar_url' => $uploadedLogoUrl ? $uploadedLogoUrl : null
    ]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $mysqli->close();
    exit;
}
