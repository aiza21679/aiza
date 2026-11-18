<?php
// status_updater.php
// Run periodically (cron) to update statuses for all elections based on start/end.

declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbName = 'eboto_db';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (PDOException $e) { echo "DB error\n"; exit(1); }

$rows = $pdo->query("SELECT * FROM `election`")->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
$updates = 0;

foreach ($rows as $r) {
    $start = isset($r['start_date']) ? $r['start_date'] : (isset($r['Start_Date']) ? $r['Start_Date'] : null);
    $end   = isset($r['end_date']) ? $r['end_date'] : (isset($r['End_Date']) ? $r['End_Date'] : null);
    $idCol = isset($r['Election_ID']) ? 'Election_ID' : (isset($r['election_id']) ? 'election_id' : 'id');
    $id = $r[$idCol] ?? $r['id'] ?? null;
    if ($id === null) continue;

    $s = $start ? new DateTimeImmutable($start, new DateTimeZone(date_default_timezone_get())) : null;
    $e = $end   ? new DateTimeImmutable($end,   new DateTimeZone(date_default_timezone_get())) : null;

    $newStatus = 'upcoming';
    if ($s && $e) {
        if ($now < $s) $newStatus = 'upcoming';
        elseif ($now >= $s && $now <= $e) $newStatus = 'ongoing';
        else $newStatus = 'completed';
    } else {
        $newStatus = 'ongoing';
    }

    $currentStatus = $r['status'] ?? ($r['Status'] ?? null);
    if ($currentStatus !== $newStatus) {
        $upd = $pdo->prepare("UPDATE `election` SET `status` = :status WHERE `$idCol` = :id LIMIT 1");
        $upd->execute([':status'=>$newStatus, ':id'=>$id]);
        $updates++;
    }
}

echo "Status updater done. Updated: $updates\n";
