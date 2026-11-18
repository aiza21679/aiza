<?php
// login.php - fixed, robust, returns JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// DB connection - adjust credentials if needed
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "eboto_db";

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['status'=>'error','message'=>'Please enter username and password']);
    exit;
}

/**
 * tryLogin
 * - $conn: mysqli connection
 * - $table: table name
 * - $id_field: column to match username (e.g. Student_ID, Admin_ID, Email)
 * - $username: value to search
 * - $password: provided password
 * - $extra_condition: optional SQL (e.g. "AND `Status` = 'Active'")
 *
 * Returns user array on success, false on failure.
 */
function tryLogin($conn, $table, $id_field, $username, $password, $extra_condition = "")
{
    try {
        $query = "SELECT * FROM `{$table}` WHERE `{$id_field}` = ? {$extra_condition} LIMIT 1";
        $stmt = $conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows !== 1) {
            $stmt->close();
            return false;
        }

        $user = $result->fetch_assoc();
        $stored = $user['Password'] ?? null;
        $stmt->close();

        $match = false;

        // Plain text equal (legacy)
        if ($stored !== null && $stored === $password) {
            $match = true;
            // Optionally upgrade hash:
            if (function_exists('password_hash')) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE `{$table}` SET `Password` = ? WHERE `{$id_field}` = ?");
                if ($u) {
                    $u->bind_param("ss", $newHash, $user[$id_field]);
                    $u->execute();
                    $u->close();
                }
            }
        }
        // SHA-256 legacy (length 64)
        elseif ($stored !== null && strlen($stored) === 64 && hash('sha256', $password) === $stored) {
            $match = true;
            // upgrade to password_hash
            if (function_exists('password_hash')) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE `{$table}` SET `Password` = ? WHERE `{$id_field}` = ?");
                if ($u) {
                    $u->bind_param("ss", $newHash, $user[$id_field]);
                    $u->execute();
                    $u->close();
                }
            }
        }
        // password_hash verify
        elseif ($stored !== null && password_verify($password, $stored)) {
            $match = true;
        }

        // special-case: student_organization allowed to login with email as password (legacy behavior)
        if ($table === 'student_organization' && !$match) {
            if (isset($user['Email']) && strcasecmp($password, $user['Email']) === 0) {
                $match = true;
            }
        }

        if (!$match) return false;
        return $user;

    } catch (Exception $e) {
        error_log("tryLogin error: " . $e->getMessage());
        return false;
    }
}

/* ---------- LOGIN ORDER ---------- */

// ADMIN
$adminUser = tryLogin($conn, 'admin', 'Admin_ID', $username, $password);
if ($adminUser !== false) {
    // regenerate session id
    session_regenerate_id(true);
    $_SESSION['user_type'] = 'admin';
    $_SESSION['user_id'] = $adminUser['Admin_ID'] ?? null;
    $_SESSION['user_name'] = $adminUser['Name'] ?? $adminUser['Admin_ID'] ?? 'Admin';
    // if you want Student_ID null for non-students:
    $_SESSION['Student_ID'] = null;
    $_SESSION['student_id'] = null;

    // update last login (best-effort)
    try {
        $u = $conn->prepare("UPDATE `admin` SET last_login_at = NOW() WHERE Admin_ID = ?");
        if ($u) { $u->bind_param('s', $adminUser['Admin_ID']); $u->execute(); $u->close(); }
    } catch (Exception $e) {}

    echo json_encode(['status'=>'success','redirect'=>'./admin/admin_dashboard.html']);
    exit;
}

// STUDENT (only Active)
$studentUser = tryLogin($conn, 'student', 'Student_ID', $username, $password, "AND `Status` = 'Active'");
if ($studentUser !== false) {
    session_regenerate_id(true);

    // store both keys to be compatible with different code in your app
    // Student_ID keeps the original DB value (may be numeric-like string)
    $sid = $studentUser['Student_ID'] ?? $studentUser['student_id'] ?? null;

    $_SESSION['user_type'] = 'student';
    $_SESSION['user_id'] = $sid;
    $_SESSION['user_name'] = $studentUser['Name'] ?? $sid ?? 'Student';

    // store both keys (some pages look for lowercase, others uppercase)
    $_SESSION['Student_ID'] = $sid;
    $_SESSION['student_id'] = $sid;

    // update last login
    try {
        $u = $conn->prepare("UPDATE `student` SET last_login_at = NOW() WHERE Student_ID = ?");
        if ($u) { $u->bind_param('s', $sid); $u->execute(); $u->close(); }
    } catch (Exception $e) {}

    echo json_encode(['status'=>'success','redirect'=>'./student/dashboard.php']);
    exit;
}

// STUDENT ORGANIZATION (by Email)
$orgUser = tryLogin($conn, 'student_organization', 'Email', $username, $password);
if ($orgUser !== false) {
    session_regenerate_id(true);

    // lowercase keys (legacy)
    $_SESSION['org_id']   = $orgUser['Org_ID'] ?? null;
    $_SESSION['org_name'] = $orgUser['Org_Name'] ?? null;
    $_SESSION['org_email']= $orgUser['Email'] ?? null;

    // ALSO set uppercase keys that other endpoints expect (fixes Org_ID missing in session)
    // Cast Org_ID to int when possible
    if (isset($orgUser['Org_ID']) && is_numeric($orgUser['Org_ID'])) {
        $_SESSION['Org_ID'] = (int) $orgUser['Org_ID'];
    } else {
        // keep null if not provided / not numeric
        $_SESSION['Org_ID'] = isset($orgUser['Org_ID']) ? $orgUser['Org_ID'] : null;
    }
    $_SESSION['Org_Name']  = $orgUser['Org_Name'] ?? ($_SESSION['org_name'] ?? null);
    $_SESSION['Org_Email'] = $orgUser['Email'] ?? ($_SESSION['org_email'] ?? null);

    // store generic user/session fields too
    $_SESSION['user_type'] = 'student_organization';
    $_SESSION['user_id']   = $orgUser['Email'] ?? null;
    $_SESSION['user_name'] = $orgUser['Org_Name'] ?? $orgUser['Email'] ?? null;

    // set Student_ID keys null to avoid confusion
    $_SESSION['Student_ID'] = null;
    $_SESSION['student_id'] = null;

    // update last login
    try {
        $u = $conn->prepare("UPDATE `student_organization` SET last_login_at = NOW() WHERE Email = ?");
        if ($u) { $u->bind_param('s', $orgUser['Email']); $u->execute(); $u->close(); }
    } catch (Exception $e) {}

    echo json_encode(['status'=>'success','redirect'=>'./student%20org/org-dashboard.php']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Invalid credentials or inactive account.']);
exit;
