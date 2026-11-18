<?php
// db.php - safe, idempotent, creates both $mysqli and $pdo (no output)
// Place this file where your scripts `require` it from. Do NOT echo/print here.

if (session_status() === PHP_SESSION_NONE) {
    // Only start session if none exists
    session_start();
}

/*
  DB credentials - change these only if your environment differs.
  Common XAMPP local defaults: host=127.0.0.1, user=root, pass='', dbname=eboto_db
*/
$dbHost = '127.0.0.1';     // use 127.0.0.1 to avoid socket vs TCP differences
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';
$dbCharset = 'utf8mb4';

// Create mysqli instance (existing code compatibility)
$mysqli = null;
try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        error_log("db.php mysqli connect_error: ({$mysqli->connect_errno}) {$mysqli->connect_error}");
        // keep $mysqli as null on failure; don't output here
        $mysqli = null;
    } else {
        // set charset
        @$mysqli->set_charset($dbCharset);
    }
} catch (Throwable $t) {
    error_log("db.php mysqli exception: " . $t->getMessage());
    $mysqli = null;
}

// Also create a PDO instance and assign to $pdo so scripts expecting PDO work
$pdo = null;
try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $ex) {
    // log but do not echo (so JSON endpoints remain valid)
    error_log("db.php pdo connection error: " . $ex->getMessage());
    $pdo = null;
}

// Export $pdo and $mysqli (they are created in this file's scope; requiring file gets them)
return;
