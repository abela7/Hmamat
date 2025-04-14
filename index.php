<?php
// Main entry point - Check for returning users or show welcome page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Check if user is already logged in
if (isUserLoggedIn()) {
    header("Location: user/dashboard.php");
    exit;
}

// Try to identify a returning user
$returning_user = identifyReturningUser();

if ($returning_user) {
    // Auto-login the returning user
    createUserSession($returning_user['id'], $returning_user['baptism_name'], $returning_user['unique_id'], $returning_user['role']);
    
    // Redirect to dashboard
    header("Location: user/dashboard.php");
    exit;
} else {
    // First-time visitor, redirect to welcome page
    header("Location: user/welcome.php");
    exit;
}
?> 