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

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First check if there are already miss reasons
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_miss_reasons");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Only add default reasons if none exist
    if ($row['count'] == 0) {
        // Default miss reasons
        $default_reasons = [
            "I was ill or not feeling well",
            "I forgot to do it",
            "I didn't have time today",
            "I was traveling",
            "I had family obligations",
            "Technical issues prevented me from completing",
            "Other reasons"
        ];
        
        // Prepare statement for insertion
        $stmt = $conn->prepare("INSERT INTO activity_miss_reasons (reason_text) VALUES (?)");
        
        // Insert each reason
        $success = true;
        foreach ($default_reasons as $reason) {
            $stmt->bind_param("s", $reason);
            if (!$stmt->execute()) {
                $success = false;
                $response['message'] = 'Database error: ' . $conn->error;
                break;
            }
        }
        
        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Default reasons added successfully.';
            $response['count'] = count($default_reasons);
        }
    } else {
        $response['success'] = true;
        $response['message'] = 'Reasons already exist.';
        $response['count'] = $row['count'];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 