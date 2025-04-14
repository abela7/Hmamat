<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Initialize response
$response = [
    'success' => false,
    'message' => 'An error occurred.'
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get parameters
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $reason_id = isset($_POST['reason_id']) ? intval($_POST['reason_id']) : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    
    // Validate input
    if (empty($activity_id) || empty($status)) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }
    
    // Validate status
    if (!in_array($status, ['done', 'missed'])) {
        $response['message'] = 'Invalid status: ' . $status;
        echo json_encode($response);
        exit;
    }
    
    // Check for future dates (prevent marking future activities)
    if (strtotime($date) > strtotime(date('Y-m-d'))) {
        $response['message'] = 'Cannot mark activities for future dates.';
        echo json_encode($response);
        exit;
    }
    
    // Get activity details
    $stmt = $conn->prepare("SELECT default_points FROM activities WHERE id = ?");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Activity not found.';
        echo json_encode($response);
        exit;
    }
    
    $activity = $result->fetch_assoc();
    $points = $activity['default_points'];
    
    // Check if record already exists
    $stmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
    $stmt->bind_param("iis", $user_id, $activity_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $record = $result->fetch_assoc();
        $record_id = $record['id'];
        
        if ($status === 'done') {
            $stmt = $conn->prepare("UPDATE user_activity_log SET status = ?, points_earned = ?, reason_id = NULL WHERE id = ?");
            $stmt->bind_param("sii", $status, $points, $record_id);
        } else {
            $stmt = $conn->prepare("UPDATE user_activity_log SET status = ?, points_earned = 0, reason_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $status, $reason_id, $record_id);
        }
    } else {
        // Insert new record
        if ($status === 'done') {
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $user_id, $activity_id, $date, $status, $points);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned, reason_id) VALUES (?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("iissi", $user_id, $activity_id, $date, $status, $reason_id);
        }
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Activity status updated successfully.';
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 