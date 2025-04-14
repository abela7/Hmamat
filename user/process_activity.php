<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Validate the date (ensure it's not in the future)
if (strtotime($date) > strtotime(date('Y-m-d'))) {
    $_SESSION['error_message'] = 'Cannot mark activities for future dates.';
    header("Location: $redirect?date=$date");
    exit;
}

// Validate activity ID
if (empty($activity_id)) {
    $_SESSION['error_message'] = 'Missing activity ID.';
    header("Location: $redirect?date=$date");
    exit;
}

// Process based on action
switch ($action) {
    case 'complete':
        // Get activity details to determine points
        $stmt = $conn->prepare("SELECT default_points FROM activities WHERE id = ?");
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error_message'] = 'Activity not found.';
            header("Location: $redirect?date=$date");
            exit;
        }
        
        $activity = $result->fetch_assoc();
        $points = $activity['default_points'];
        $stmt->close();
        
        // Check if record already exists and update or insert
        $stmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
        $stmt->bind_param("iis", $user_id, $activity_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $record = $result->fetch_assoc();
            $record_id = $record['id'];
            
            $stmt = $conn->prepare("UPDATE user_activity_log SET status = 'done', points_earned = ?, reason_id = NULL WHERE id = ?");
            $stmt->bind_param("ii", $points, $record_id);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned) VALUES (?, ?, ?, 'done', ?)");
            $stmt->bind_param("iisi", $user_id, $activity_id, $date, $points);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $_SESSION['success_message'] = 'Activity marked as complete!';
        } else {
            $_SESSION['error_message'] = 'Error updating activity status: ' . $conn->error;
        }
        break;
        
    case 'missed':
        $reason_id = isset($_GET['reason_id']) ? intval($_GET['reason_id']) : 0;
        
        if (empty($reason_id)) {
            $_SESSION['error_message'] = 'Missing reason for not completing the activity.';
            header("Location: $redirect?date=$date");
            exit;
        }
        
        // Check if record already exists and update or insert
        $stmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
        $stmt->bind_param("iis", $user_id, $activity_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $record = $result->fetch_assoc();
            $record_id = $record['id'];
            
            $stmt = $conn->prepare("UPDATE user_activity_log SET status = 'missed', points_earned = 0, reason_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $reason_id, $record_id);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned, reason_id) VALUES (?, ?, ?, 'missed', 0, ?)");
            $stmt->bind_param("iisi", $user_id, $activity_id, $date, $reason_id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $_SESSION['success_message'] = 'Activity marked as not done.';
        } else {
            $_SESSION['error_message'] = 'Error updating activity status: ' . $conn->error;
        }
        break;
        
    case 'reset':
        // Delete the activity record
        $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
        $stmt->bind_param("iis", $user_id, $activity_id, $date);
        
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $_SESSION['success_message'] = 'Activity reset successfully.';
        } else {
            $_SESSION['error_message'] = 'Error resetting activity: ' . $conn->error;
        }
        break;
        
    default:
        $_SESSION['error_message'] = 'Invalid action.';
}

// Redirect back to the original page
header("Location: $redirect?date=$date");
exit;
?> 