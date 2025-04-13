<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// End admin session
endAdminSession();

// Redirect to login page
header("Location: login.php");
exit;
?> 