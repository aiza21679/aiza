<?php
// delete_candidate.php
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
    $mysqli = new mysqli('localhost', 'root', '', 'eboto_db');
    if ($mysqli->connect_errno) {
        jsonFail('Database connection failed', ['mysqli_error' => $mysqli->connect_error], 500);
    }
    $mysqli->set_charset('utf8mb4');

    if (empty($_POST['Candidate_ID'])) {
        jsonFail('Missing Candidate_ID');
    }

    $candidateId = (int)$_POST['Candidate_ID'];

    // Check candidate exists
    $check = $mysqli->prepare("SELECT Candidate_ID FROM candidate WHERE Candidate_ID = ? LIMIT 1");
    if (!$check) {
        jsonFail('Failed to prepare candidate lookup', ['mysqli_error' => $mysqli->error], 500);
    }
    $check->bind_param('i', $candidateId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        jsonFail('Candidate not found');
    }
    $check->close();

    // Try to delete
    $del = $mysqli->prepare("DELETE FROM candidate WHERE Candidate_ID = ?");
    if (!$del) {
        jsonFail('Failed to prepare delete', ['mysqli_error' => $mysqli->error], 500);
    }
    $del->bind_param('i', $candidateId);

    if (!$del->execute()) {
        // Possible foreign key constraint (e.g., votes referencing candidate)
        jsonFail('Delete failed – candidate may be referenced in other tables.', [
            'stmt_error' => $del->error
        ], 500);
    }

    $del->close();
    $mysqli->close();

    echo json_encode([
        'success' => true,
        'message' => 'Candidate deleted'
    ]);

} catch (Exception $ex) {
    error_log('delete_candidate exception: ' . $ex->getMessage());
    jsonFail('Server exception', ['exception' => $ex->getMessage()], 500);
}
