<?php
// getOrganization.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php'; // use your db.php that creates $mysqli or update connection below

// Prefer session-based org id (if you store it). Otherwise accept ?orgId=...
session_start();
$orgId = $_SESSION['org_id'] ?? ($_GET['orgId'] ?? null);
if (!$orgId) {
    echo json_encode(['success' => false, 'error' => 'orgId missing']);
    exit;
}

$sql = "SELECT Org_ID, Org_Name, Description, Email, Logo_Url, Registered_At
        FROM student_organization
        WHERE Org_ID = ? LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $orgId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Organization not found']);
    exit;
}

$row['Logo_Url'] = $row['Logo_Url'] ?: 'uploads/org-default.png';
echo json_encode(['success' => true, 'organization' => $row]);
