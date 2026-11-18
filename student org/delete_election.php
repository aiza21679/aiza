<?php
// delete_election.php
// Hard delete endpoint: deletes the election row by Election_ID.
// WARNING: this permanently removes the row. Use backups or adjust to soft-delete if needed.

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
    exit;
}

// read JSON payload (fallback to form)
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$electionId = $data['electionId'] ?? $data['Election_ID'] ?? null;
if (!$electionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing electionId']);
    exit;
}

/* DB config - adjust as needed */
$dbHost = "127.0.0.1";
$dbName = "eboto_db";
$dbUser = "root";
$dbPass = "";
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection error', 'error' => $e->getMessage()]);
    exit;
}

// Optional: verify that election exists
try {
    $q = $pdo->prepare("SELECT Election_ID FROM election WHERE Election_ID = :id LIMIT 1");
    $q->execute([':id' => $electionId]);
    $row = $q->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Election not found']);
        exit;
    }

    // Hard delete:
    $d = $pdo->prepare("DELETE FROM election WHERE Election_ID = :id LIMIT 1");
    $d->execute([':id' => $electionId]);

    echo json_encode(['success' => true, 'message' => 'Election deleted successfully', 'electionId' => $electionId]);
    exit;

} catch (Exception $e) {
    error_log("delete_election error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete election', 'error' => $e->getMessage()]);
    exit;
}
