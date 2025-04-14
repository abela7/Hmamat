<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to reset activities.']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Validate input
if (!isset($_POST['activity_id']) || !isset($_POST['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$activity_id = intval($_POST['activity_id']);
$date = $_POST['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}

// Check if activity exists
$stmt = $conn->prepare("SELECT id FROM activities WHERE id = ?");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Activity not found.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Delete the activity record for the specific date
$stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
$stmt->bind_param("iis", $user_id, $activity_id, $date);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Activity reset successfully.',
        'activity_id' => $activity_id,
        'date' => $date
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error resetting activity: ' . $conn->error]);
}

$stmt->close();
?> 