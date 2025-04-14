<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set proper headers
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => 'An error occurred.',
    'data' => null
];

// Log the incoming request for debugging
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Activity Reset - Raw POST data: ' . file_get_contents('php://input'));
error_log('Activity Reset - POST params: ' . print_r($_POST, true));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get parameters
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    
    // Log the parsed parameters
    error_log('Parsed parameters - Activity ID: ' . $activity_id . ', Date: ' . $date);
    
    // Validate input
    if (empty($activity_id) || empty($date)) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Delete the activity record
        $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ? AND activity_id = ? AND date_completed = ?");
        $stmt->bind_param("iis", $user_id, $activity_id, $date);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Activity reset successfully.';
                $response['data'] = [
                    'activity_id' => $activity_id,
                    'date' => $date
                ];
            } else {
                $response['message'] = 'No activity record found to reset.';
            }
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Exception: ' . $e->getMessage();
        error_log('Exception in reset_activity.php: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
exit;
?> 