<?php
// php/get_activities.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    $activities = [];

    // 1) recent votes (join student if possible)
    $sqlVotes = "SELECT v.Vote_ID, v.Timestamp, v.Student_ID, v.Election_ID, s.name as student_name
                 FROM `vote` v
                 LEFT JOIN `student` s ON s.Student_ID = v.Student_ID
                 ORDER BY v.Timestamp DESC
                 LIMIT 6";
    $stmt = $pdo->query($sqlVotes);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $title = ($row['student_name'] ?? ('Student #' . $row['Student_ID'])) . ' cast a vote';
        $activities[] = [
            'type' => 'vote-yea',
            'title' => $title,
            'time' => date('g:i A • M j, Y', strtotime($row['Timestamp']))
        ];
    }

    // 2) recent elections published (published_at or Start_Date)
    $sqlE = "SELECT Election_ID, Title, published_at, Start_Date FROM `election`
             WHERE published_at IS NOT NULL OR Start_Date IS NOT NULL
             ORDER BY COALESCE(published_at, Start_Date) DESC
             LIMIT 4";
    $stmt = $pdo->query($sqlE);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $when = $r['published_at'] ?? $r['Start_Date'];
        $activities[] = [
            'type' => 'history',
            'title' => 'Election "' . $r['Title'] . '" launched',
            'time' => date('g:i A • M j, Y', strtotime($when))
        ];
    }

    // keep only latest 10, sort by time desc
    usort($activities, function($a,$b){
        $ta = strtotime(preg_replace('/.*•\s*/','',$a['time'])) ?: 0;
        $tb = strtotime(preg_replace('/.*•\s*/','',$b['time'])) ?: 0;
        return $tb - $ta;
    });
    $activities = array_slice($activities, 0, 10);

    echo json_encode($activities);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Server error','detail'=>$e->getMessage()]);
    exit;
}
