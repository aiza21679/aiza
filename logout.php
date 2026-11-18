<?php
session_start();

// Clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// Ensure no caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
header("Location: ../login.html");
exit;
?>