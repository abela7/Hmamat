<?php
// Start session if not already started
session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';

// Save user ID if available
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// ======== AGGRESSIVE SESSION CLEARING ========

// Clear all session variables
$_SESSION = array();

// Kill the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Remove all cookies (both device tracking and others)
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
}

// ======== CLEAN UP DATABASE ENTRIES ========

if ($user_id && $conn) {
    // Remove session records
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Remove device tracking records
    $stmt = $conn->prepare("DELETE FROM user_devices WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 50px auto; max-width: 600px;'>";
    echo "<h3>Force Logout Complete</h3>";
    echo "<p>All user sessions and device tracking records have been removed.</p>";
    echo "<p>Device tokens and cookies have been cleared.</p>";
    echo "<p><a href='login.php' style='display: inline-block; padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;'>Return to Login</a></p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 50px auto; max-width: 600px;'>";
    echo "<h3>Force Logout Complete</h3>";
    echo "<p>Session data and cookies have been cleared.</p>";
    echo "<p><a href='login.php' style='display: inline-block; padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;'>Return to Login</a></p>";
    echo "</div>";
}
?> 