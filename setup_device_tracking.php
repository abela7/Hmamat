<?php
// Script to set up the user devices tracking system
require_once 'includes/config.php';
require_once 'includes/db.php';

// Display header
echo "===========================================\n";
echo "HIMAMAT Device Tracking System Setup\n";
echo "===========================================\n\n";

// Read SQL file
$sql_file = 'db/user_devices.sql';
if (!file_exists($sql_file)) {
    echo "Error: SQL file not found at $sql_file\n";
    exit(1);
}

$sql = file_get_contents($sql_file);

// Execute SQL
echo "Creating user_devices table...\n";
if ($conn->multi_query($sql)) {
    do {
        // Store result from first query
        if ($result = $conn->store_result()) {
            $result->free();
        }
        
        // Try to move to the next result
    } while ($conn->more_results() && $conn->next_result());
    
    if ($conn->errno) {
        echo "Error: " . $conn->error . "\n";
        exit(1);
    }
    
    echo "Table created successfully!\n\n";
} else {
    echo "Error: " . $conn->error . "\n";
    exit(1);
}

// Check if any users need unique IDs
echo "Checking for users without unique IDs...\n";
$stmt = $conn->prepare("SELECT id, baptism_name FROM users WHERE unique_id IS NULL OR unique_id = ''");
$stmt->execute();
$result = $stmt->get_result();

$updated = 0;
while ($user = $result->fetch_assoc()) {
    // Generate unique ID for user
    $unique_id = bin2hex(random_bytes(16));
    
    // Update user
    $update = $conn->prepare("UPDATE users SET unique_id = ? WHERE id = ?");
    $update->bind_param("si", $unique_id, $user['id']);
    if ($update->execute()) {
        $updated++;
    }
    $update->close();
}

if ($updated > 0) {
    echo "Updated $updated users with new unique IDs.\n";
} else {
    echo "All users already have unique IDs.\n";
}

echo "\nSetup complete!\n";
echo "===========================================\n";
echo "You can now use the enhanced device tracking system.\n";
echo "===========================================\n";
?> 