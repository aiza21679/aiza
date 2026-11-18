<?php
// update_candidate.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
session_start();

function jsonFail($message, $extra = [], $code = 400)
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit;
}

try {
    // same connection style as add_candidate.php
    $mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
    if ($mysqli->connect_errno) {
        jsonFail('Database connection failed', ['mysqli_error' => $mysqli->connect_error], 500);
    }
    $mysqli->set_charset('utf8mb4');

    // Candidate_ID is required
    if (empty($_POST['Candidate_ID'])) {
        jsonFail('Missing Candidate_ID');
    }

    $candidateId = (int)$_POST['Candidate_ID'];

    // Check candidate exists
    $check = $mysqli->prepare("SELECT * FROM candidate WHERE Candidate_ID = ? LIMIT 1");
    if (!$check) {
        jsonFail('Failed to prepare candidate lookup', ['mysqli_error' => $mysqli->error], 500);
    }
    $check->bind_param('i', $candidateId);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        $check->close();
        jsonFail('Candidate not found');
    }
    $existing = $result->fetch_assoc();
    $check->close();

    // Build dynamic UPDATE
    $fields = [];
    $params = [];
    $types  = '';

    // FullName
    if (isset($_POST['FullName'])) {
        $fields[] = "FullName = ?";
        $params[] = trim($_POST['FullName']);
        $types   .= 's';
    }

    // Email
    if (isset($_POST['Email'])) {
        $fields[] = "Email = ?";
        $params[] = trim($_POST['Email']);
        $types   .= 's';
    }

    // StudentID -> Student_ID (with validation)
    if (isset($_POST['StudentID'])) {
        $studentID = trim($_POST['StudentID']);
        if ($studentID !== '') {
            $q = $mysqli->prepare("SELECT Student_ID FROM student WHERE Student_ID = ? LIMIT 1");
            if (!$q) {
                jsonFail('Failed to prepare student lookup', ['mysqli_error' => $mysqli->error], 500);
            }
            $sid = (int)$studentID;
            $q->bind_param('i', $sid);
            $q->execute();
            $q->store_result();
            if ($q->num_rows === 0) {
                $q->close();
                jsonFail('Student_ID not found in student table.');
            }
            $q->close();

            $fields[] = "Student_ID = ?";
            $params[] = $sid;
            $types   .= 'i';
        }
    }

    // YearLevel
    if (isset($_POST['YearLevel'])) {
        $fields[] = "YearLevel = ?";
        $params[] = trim($_POST['YearLevel']);
        $types   .= 's';
    }

    // PartyList
    if (isset($_POST['PartyList'])) {
        $fields[] = "PartyList = ?";
        $params[] = trim($_POST['PartyList']);
        $types   .= 's';
    }

    // Program
    if (isset($_POST['Program'])) {
        $fields[] = "Program = ?";
        $params[] = trim($_POST['Program']);
        $types   .= 's';
    }

    // Platform
    if (isset($_POST['Platform'])) {
        $fields[] = "Platform = ?";
        $params[] = trim($_POST['Platform']);
        $types   .= 's';
    }

    // Experience
    if (isset($_POST['Experience'])) {
        $fields[] = "Experience = ?";
        $params[] = trim($_POST['Experience']);
        $types   .= 's';
    }

    // status
    if (isset($_POST['Status'])) {
        $fields[] = "`status` = ?";
        $params[] = trim($_POST['Status']);
        $types   .= 's';
    }

    // Optional avatar upload
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

        $avatar_path = 'uploads/candidates/' . $filename;

        $fields[] = "Avatar = ?";
        $params[] = $avatar_path;
        $types   .= 's';
    }

    if (empty($fields)) {
        jsonFail('No fields to update');
    }

    // Add Candidate_ID to params
    $params[] = $candidateId;
    $types   .= 'i';

    $sql = "UPDATE candidate SET " . implode(', ', $fields) . " WHERE Candidate_ID = ?";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        jsonFail('Failed to prepare update', ['mysqli_error' => $mysqli->error, 'sql' => $sql], 500);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        jsonFail('Update failed', ['stmt_error' => $stmt->error], 500);
    }

    $stmt->close();

    // Fetch updated row to send back
    $re = $mysqli->prepare("
        SELECT 
            Candidate_ID AS id,
            FullName,
            Email,
            Student_ID AS StudentID,
            YearLevel,
            Position_ID,
            Org_ID,
            PartyList,
            Program,
            Platform,
            Experience,
            Avatar,
            `status` AS Status
        FROM candidate
        WHERE Candidate_ID = ?
        LIMIT 1
    ");
    $re->bind_param('i', $candidateId);
    $re->execute();
    $updatedRes = $re->get_result();
    $updatedRow = $updatedRes->fetch_assoc();
    $re->close();

    $mysqli->close();

    echo json_encode([
        'success'  => true,
        'message'  => 'Candidate updated',
        'candidate'=> $updatedRow
    ]);

} catch (Exception $ex) {
    error_log('update_candidate exception: ' . $ex->getMessage());
    jsonFail('Server exception', ['exception' => $ex->getMessage()], 500);
}
