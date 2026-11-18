<?php
header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'eboto_db';

$mysqli = new mysqli($host,$user,$pass,$db);
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB connect error','detail'=>$mysqli->connect_error]);
  exit;
}
$mysqli->set_charset('utf8mb4');

$sql = "SELECT id, name, short_code, color, 
               COALESCE(logo,'') AS logo, COALESCE(slogan,'') AS slogan, COALESCE(department,'') AS department, COALESCE(established,'') AS established
        FROM party_lists
        ORDER BY name";
$res = $mysqli->query($sql);
if (!$res) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Query failed','detail'=>$mysqli->error]);
  exit;
}

$rows = [];
while ($r = $res->fetch_assoc()) {
  // ensure logo is absolute/public path if stored as filename
  if (!empty($r['logo']) && !preg_match('#^https?://#i', $r['logo']) && strpos($r['logo'],'/') !== 0) {
    // adjust this base to match your public uploads folder
    $r['logo'] = '/eBotoni/uploads/parties/' . $r['logo'];
  }
  $rows[] = $r;
}

echo json_encode(['success'=>true,'data'=>$rows]);
$mysqli->close();
