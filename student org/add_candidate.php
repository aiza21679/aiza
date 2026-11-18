<?php
// add_candidate.php – fixed for your schema + returns avatar path
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
session_start();

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error',
            'error'   => $err
        ]);
    }
});

function jsonFail($message, $extra = [], $code = 400)
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit;
}

try {
    $mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
    if ($mysqli->connect_errno) {
        jsonFail('Database connection failed', ['mysqli_error' => $mysqli->connect_error], 500);
    }
    $mysqli->set_charset('utf8mb4');

    // required POST fields
    $required = [
        'FullName', 'Email', 'StudentID', 'YearLevel',
        'Position_ID', 'Org_ID', 'PartyList', 'Program',
        'Platform', 'Experience', 'Status'
    ];
    foreach ($required as $f) {
        if (!isset($_POST[$f]) || trim($_POST[$f]) === '') {
            jsonFail("Missing required field: $f");
        }
    }

    // normalize
    $full       = trim($_POST['FullName']);
    $email      = trim($_POST['Email']);
    $studentID  = trim($_POST['StudentID']);  // candidate.Student_ID
    $year       = trim($_POST['YearLevel']);
    $positionId = (int)$_POST['Position_ID'];
    $orgId      = (int)$_POST['Org_ID'];
    $party      = trim($_POST['PartyList']);
    $program    = trim($_POST['Program']);
    $platform   = trim($_POST['Platform']);
    $experience = trim($_POST['Experience']);
    $status     = trim($_POST['Status']);

    // ---------- validate FK: Student ----------
    $q = $mysqli->prepare("SELECT Student_ID FROM student WHERE Student_ID = ? LIMIT 1");
    if (!$q) jsonFail('Failed to prepare student lookup', ['mysqli_error' => $mysqli->error], 500);
    $q->bind_param('s', $studentID);
    $q->execute();
    $q->store_result();
    if ($q->num_rows === 0) {
        $q->close();
        jsonFail('Student_ID not found in students table. Please provide a valid Student ID.');
    }
    $q->close();

    // ---------- validate FK: Position ----------
    $qp = $mysqli->prepare("SELECT Position_ID FROM position WHERE Position_ID = ? LIMIT 1");
    if (!$qp) jsonFail('Failed to prepare position lookup', ['mysqli_error' => $mysqli->error], 500);
    $qp->bind_param('i', $positionId);
    $qp->execute();
    $qp->store_result();
    if ($qp->num_rows === 0) {
        $qp->close();
        jsonFail('Position_ID not found in position table. Use a valid Position_ID.');
    }
    $qp->close();

    // ---------- validate FK: Organization ----------
    $qo = $mysqli->prepare("SELECT Org_ID FROM student_organization WHERE Org_ID = ? LIMIT 1");
    if (!$qo) jsonFail('Failed to prepare organization lookup', ['mysqli_error' => $mysqli->error], 500);
    $qo->bind_param('i', $orgId);
    $qo->execute();
    $qo->store_result();
    if ($qo->num_rows === 0) {
        $qo->close();
        jsonFail('Org_ID not found in student_organization table. Use a valid Org_ID.');
    }
    $qo->close();

    // ---------- avatar upload (optional) ----------
    $avatar_path = null;
    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['avatar'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            jsonFail('Avatar upload error', ['upload_error' => $f['error']]);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            jsonFail('Invalid avatar file type');
        }

        $uploadsDir = __DIR__ . '/uploads/candidates';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
            jsonFail('Failed to create upload directory', ['path' => $uploadsDir], 500);
        }

        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target   = $uploadsDir . '/' . $filename;

        if (!move_uploaded_file($f['tmp_name'], $target)) {
            jsonFail('Failed to move uploaded avatar', ['target' => $target], 500);
        }

        // path stored in DB (web relative)
        $avatar_path = 'uploads/candidates/' . $filename;
    }

    // ---------- insert candidate ----------
    $sql = "INSERT INTO candidate 
        (FullName, Email, Student_ID, YearLevel, Position_ID, Org_ID,
         PartyList, Program, Platform, Experience, Avatar, `status`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        jsonFail('Failed to prepare insert', ['mysqli_error' => $mysqli->error], 500);
    }

    $avatarBind = $avatar_path; // may be null

    if (!$stmt->bind_param(
        "ssisiissssss",
        $full,
        $email,
        $studentID,
        $year,
        $positionId,
        $orgId,
        $party,
        $program,
        $platform,
        $experience,
        $avatarBind,
        $status
    )) {
        jsonFail('Failed to bind params', ['stmt_error' => $stmt->error], 500);
    }

    if (!$stmt->execute()) {
        jsonFail('Insert failed', ['stmt_error' => $stmt->error], 500);
    }

    $insertId = $stmt->insert_id;
    $stmt->close();
    $mysqli->close();

    echo json_encode([
        'success'   => true,
        'message'   => 'Candidate saved',
        'insert_id' => $insertId,
        'avatar'    => $avatar_path // may be null
    ]);

} catch (Exception $ex) {
    error_log('add_candidate exception: ' . $ex->getMessage());
    jsonFail('Server exception', ['exception' => $ex->getMessage()], 500);
}
