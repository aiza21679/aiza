<?php
header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'eboto_db';

$UPLOAD_DIR = __DIR__ . '/../uploads/parties/';   // adjust to actual folder
$PUBLIC_PREFIX = '/eBotoni/uploads/parties/';     // adjust to public URL prefix

if (!is_dir($UPLOAD_DIR)) {
  @mkdir($UPLOAD_DIR, 0755, true);
}

$mysqli = new mysqli($host,$user,$pass,$db);
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB connect error']);
  exit;
}
$mysqli->set_charset('utf8mb4');

$name = trim($_POST['name'] ?? '');
$short_code = trim($_POST['short_code'] ?? '');
$color = trim($_POST['color'] ?? '');
$slogan = trim($_POST['slogan'] ?? '');
$department = trim($_POST['department'] ?? '');
$established = trim($_POST['established'] ?? null);

if ($name === '') {
  echo json_encode(['success'=>false,'message'=>'Party name required']);
  exit;
}

$logo_filename = null;
if (!empty($_FILES['logo']['name'])) {
  $allowed = ['jpg','jpeg','png','gif','webp'];
  $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false,'message'=>'Invalid image type']);
    exit;
  }
  $logo_filename = preg_replace('/[^a-z0-9\-_\.]/i','', $short_code ?: preg_replace('/\s+/','-',strtolower($name))) 
                   . '-' . time() . '.' . $ext;
  $dest = $UPLOAD_DIR . $logo_filename;
  if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
    echo json_encode(['success'=>false,'message'=>'Failed to save logo file']);
    exit;
  }
}

$stmt = $mysqli->prepare("INSERT INTO party_lists (name, short_code, color, slogan, department, established, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
  echo json_encode(['success'=>false,'message'=>'Prepare failed','detail'=>$mysqli->error]);
  exit;
}
$stmt->bind_param('sssssss', $name, $short_code, $color, $slogan, $department, $established, $logo_filename);
$ok = $stmt->execute();
if (!$ok) {
  echo json_encode(['success'=>false,'message'=>'Execute failed','detail'=>$stmt->error]);
  exit;
}

echo json_encode(['success'=>true,'message'=>'Party added','id'=>$mysqli->insert_id, 'logo' => $logo_filename ? ($PUBLIC_PREFIX . $logo_filename) : null]);
$stmt->close();
$mysqli->close();
