<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to mark activities.']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get current date
$current_date = date('Y-m-d');

// Validate input
if (!isset($_POST['activity_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$activity_id = intval($_POST['activity_id']);
$status = $_POST['status'];

// Validate status
if ($status !== 'done' && $status !== 'missed') {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

// Check if activity exists
$stmt = $conn->prepare("SELECT id, name, default_points FROM activities WHERE id = ?");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Activity not found.']);
    $stmt->close();
    exit;
}

$activity = $result->fetch_assoc();
$stmt->close();

// Check if activity has already been marked for today
$stmt = $conn->prepare("SELECT id, status FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
$stmt->bind_param("iis", $user_id, $activity_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Activity already marked, update it
    $existing = $result->fetch_assoc();
    
    // Calculate points based on status
    $points_earned = ($status === 'done') ? $activity['default_points'] : 0;
    
    $stmt = $conn->prepare("UPDATE user_activity_log SET status = ?, points_earned = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $status, $points_earned, $existing['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating activity: ' . $conn->error]);
    }
} else {
    // New activity log entry
    // Calculate points based on status
    $points_earned = ($status === 'done') ? $activity['default_points'] : 0;
    
    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iissi", $user_id, $activity_id, $current_date, $status, $points_earned);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity marked successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error marking activity: ' . $conn->error]);
    }
}

$stmt->close();
?> 