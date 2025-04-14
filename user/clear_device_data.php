<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Get current user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Delete device tracking cookie
setcookie('user_unique_id', '', time() - 3600, '/');

// Clear session data
$_SESSION = array();
session_destroy();

// If user was logged in, delete their device records from database
if ($user_id) {
    $stmt = $conn->prepare("DELETE FROM user_devices WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo "<p>Device data cleared for your account.</p>";
} else {
    echo "<p>Device cookie cleared.</p>";
}

echo "<p>Your device has been forgotten by the system.</p>";
echo "<p><a href='../index.php'>Return to homepage</a></p>";
?> 