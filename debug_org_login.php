<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
$conn = new mysqli('localhost','root','','eboto_db');
if ($conn->connect_error) die("DB conn failed: ".$conn->connect_error);

// change these to the exact values you tested
$email = 'it@gmail.com';
$pw = '12345';

$stmt = $conn->prepare("SELECT * FROM student_organization WHERE Email = ? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) { echo "No org row for Email = $email\n"; exit; }
$user = $res->fetch_assoc();
echo "<pre>"; print_r($user);
echo "\nStored-password: [".$user['Password']."]\nLength: ".strlen($user['Password'])."\n\n";
echo "password_verify: "; var_export(password_verify($pw, $user['Password']));
echo "\nsha256 match: "; var_export(hash('sha256',$pw) === $user['Password']);
echo "\nplain-text equal: "; var_export($pw === $user['Password']);
echo "\nemail-equals-password fallback: "; var_export($pw === $user['Email']);
echo "</pre>";
$stmt->close(); $conn->close();
    