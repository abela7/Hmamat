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
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    
    // Validate input
    if (empty($activity_id) || empty($date)) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }
    
    // Delete the activity record
    $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
    $stmt->bind_param("iis", $user_id, $activity_id, $date);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Activity reset successfully.';
        } else {
            $response['message'] = 'No activity record found to reset.';
        }
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 