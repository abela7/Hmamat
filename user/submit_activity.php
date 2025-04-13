<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Get user information
$user_id = $_SESSION['user_id'];

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get required parameters
    $activity_id = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate parameters
    if ($activity_id <= 0) {
        $response['message'] = 'Invalid activity ID';
    } elseif (!in_array($status, ['done', 'not_done'])) {
        $response['message'] = 'Invalid status';
    } else {
        // Get current date
        $current_date = date('Y-m-d');
        
        // Check if activity exists
        $stmt = $conn->prepare("SELECT id, default_points FROM activities WHERE id = ?");
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows !== 1) {
            $response['message'] = 'Activity not found';
        } else {
            $activity = $result->fetch_assoc();
            $points = ($status === 'done') ? $activity['default_points'] : 0;
            
            // Check if activity has already been logged today
            $stmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
            $stmt->bind_param("iis", $user_id, $activity_id, $current_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing log
                $log_id = $result->fetch_assoc()['id'];
                
                if ($status === 'done') {
                    $stmt = $conn->prepare("UPDATE user_activity_log SET status = ?, points_earned = ?, reason_id = NULL WHERE id = ?");
                    $stmt->bind_param("sii", $status, $points, $log_id);
                } else {
                    $reason_id = isset($_POST['reason_id']) ? (int)$_POST['reason_id'] : null;
                    $stmt = $conn->prepare("UPDATE user_activity_log SET status = ?, points_earned = 0, reason_id = ? WHERE id = ?");
                    $stmt->bind_param("sii", $status, $reason_id, $log_id);
                }
            } else {
                // Create new log
                if ($status === 'done') {
                    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, points_earned) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissi", $user_id, $activity_id, $current_date, $status, $points);
                } else {
                    $reason_id = isset($_POST['reason_id']) ? (int)$_POST['reason_id'] : null;
                    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, date_completed, status, reason_id, points_earned) VALUES (?, ?, ?, ?, ?, 0)");
                    $stmt->bind_param("iissi", $user_id, $activity_id, $current_date, $status, $reason_id);
                }
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Activity ' . ($status === 'done' ? 'completed' : 'marked as not done');
                $response['points'] = $points;
            } else {
                $response['message'] = 'Failed to log activity: ' . $conn->error;
            }
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 