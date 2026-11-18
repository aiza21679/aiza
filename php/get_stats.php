<?php
// php/get_stats.php
// Returns JSON: { success: true, totalVoters: N, activeElections: M, votesToday: X, participationRate: Y }
// participationRate is a **number** (integer percent) — the frontend will append the '%' when rendering.

header('Content-Type: application/json; charset=utf-8');
// Optional: adjust origin to your app domain if you don't want wildcard in production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$response = [
    'success' => true,
    'totalVoters' => 0,
    'activeElections' => 0,
    'votesToday' => 0,
    'participationRate' => 0  // numeric percent (no % sign)
];

// Load your DB connection. db.php should create either $mysqli (MySQLi) or $pdo (PDO).
// If your db.php is in a different path adjust the require path.
require __DIR__ . '/db.php';

// helper runner that works with mysqli or pdo
$useMysqli = (isset($mysqli) && $mysqli instanceof mysqli);
$usePdo = (isset($pdo) && ($pdo instanceof PDO || is_object($pdo)));

function runCountQuery($sql) {
    global $useMysqli, $usePdo, $mysqli, $pdo;
    if ($useMysqli) {
        $res = $mysqli->query($sql);
        if ($res) {
            $row = $res->fetch_row();
            $res->free();
            return (int)($row[0] ?? 0);
        }
        return 0;
    } elseif ($usePdo) {
        try {
            $stmt = $pdo->query($sql);
            if ($stmt !== false) {
                $val = $stmt->fetchColumn();
                return (int)($val ?? 0);
            }
        } catch (Exception $e) {
            return 0;
        }
        return 0;
    } else {
        // no DB connection available
        return 0;
    }
}

/* ---------- total voters (student table) ---------- */
try {
    // Adjust table name if yours differs (students, student_tbl, etc.)
    $response['totalVoters'] = runCountQuery("SELECT COUNT(*) FROM `student`");
} catch (Throwable $e) {
    // leave default 0
}

/* ---------- active elections ---------- */
try {
    // Primary attempt: explicit status column containing 'active'
    $response['activeElections'] = runCountQuery(
        "SELECT COUNT(*) FROM `election` WHERE LOWER(COALESCE(`status`,'')) = 'active'"
    );

    // If that returned 0, try fallback by checking date ranges (start/end columns)
    if ($response['activeElections'] === 0) {
        // This SQL uses COALESCE to support possible column names `start_date` / `startDate` etc.
        // It counts rows where current date is between start and end (if present).
        $dateRangeSql = "
            SELECT COUNT(*) FROM `election`
            WHERE (
              (COALESCE(`start_date`, `startDate`, '') = '' AND COALESCE(`end_date`, `endDate`, '') = '')
              OR
              (
                (COALESCE(`start_date`, `startDate`) IS NULL OR COALESCE(`start_date`, `startDate`) = '' OR COALESCE(`start_date`, `startDate`) <= NOW())
                AND
                (COALESCE(`end_date`, `endDate`) IS NULL OR COALESCE(`end_date`, `endDate`) = '' OR COALESCE(`end_date`, `endDate`) >= NOW())
              )
            )
        ";
        $countByDate = runCountQuery($dateRangeSql);
        // use the date-based result only if it's > 0 to avoid overriding a valid 0
        if ($countByDate > 0) $response['activeElections'] = $countByDate;
    }
} catch (Throwable $e) {
    // ignore and keep default
}

/* ---------- votes today ---------- */
try {
    // Try a few likely table/column combos. CURDATE() avoids PHP timezone issues.
    $voteQueries = [
        // vote_master with date-like columns
        "SELECT COUNT(*) FROM `vote_master` WHERE DATE(COALESCE(`created_at`,`timestamp`,`created`)) = CURDATE()",
        // generic vote table
        "SELECT COUNT(*) FROM `vote` WHERE DATE(COALESCE(`created_at`,`timestamp`,`created`)) = CURDATE()",
        // alternate table name
        "SELECT COUNT(*) FROM `votes` WHERE DATE(COALESCE(`created_at`,`timestamp`,`created`)) = CURDATE()",
        // fallback: last 24 hours
        "SELECT COUNT(*) FROM `vote_master` WHERE COALESCE(`created_at`,`timestamp`) >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
        "SELECT COUNT(*) FROM `vote` WHERE COALESCE(`created_at`,`timestamp`) >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
        "SELECT COUNT(*) FROM `votes` WHERE COALESCE(`created_at`,`timestamp`) >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
    ];

    foreach ($voteQueries as $q) {
        $c = runCountQuery($q);
        if ($c > 0) { // we stop when we find a non-zero result
            $response['votesToday'] = $c;
            break;
        }
    }

    // If all returned 0 above, it's still possible there are votes but no date column or different schema.
    // As a last-ditch attempt, check total rows in likely vote tables (could be 0).
    if ($response['votesToday'] === 0) {
        $totalAttempt = runCountQuery("SELECT COUNT(*) FROM `vote_master`");
        if ($totalAttempt > 0) $response['votesToday'] = $totalAttempt;
        else {
            $totalAttempt2 = runCountQuery("SELECT COUNT(*) FROM `vote`");
            if ($totalAttempt2 > 0) $response['votesToday'] = $totalAttempt2;
            else {
                $totalAttempt3 = runCountQuery("SELECT COUNT(*) FROM `votes`");
                if ($totalAttempt3 > 0) $response['votesToday'] = $totalAttempt3;
            }
        }
    }
} catch (Throwable $e) {
    // keep default
}

/* ---------- participation rate (numeric percent) ---------- */
// We compute: (votesToday / totalVoters) * 100
if (!empty($response['totalVoters']) && $response['totalVoters'] > 0) {
    $rate = ($response['votesToday'] / $response['totalVoters']) * 100;
    $response['participationRate'] = (int) round($rate); // integer percent (e.g. 4)
} else {
    $response['participationRate'] = 0;
}

/* ---------- final output ---------- */
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// if using mysqli, close gracefully
if ($useMysqli) {
    try { $mysqli->close(); } catch (Throwable $e) {}
}
exit;
