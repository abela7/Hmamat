<?php
// Start session if not already started
session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php'; // Ensure db.php is loaded before auth_check.php
require_once '../includes/auth_check.php';

// End user session
endUserSession();

// Additional session cleanup
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Force deletion of all cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
}

// Final destruction of session
session_destroy();

// Force a complete refresh - prevent caching issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.php?logout=success");
exit;
?> 