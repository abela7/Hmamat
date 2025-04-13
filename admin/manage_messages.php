<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if admin is logged in
requireAdminLogin();

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Initialize variables
$message_id = 0;
$message_text = "";
$day_of_week = null;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $message_text = trim($_POST['message_text']);
    $day_of_week = isset($_POST['day_of_week']) && $_POST['day_of_week'] !== '' ? (int)$_POST['day_of_week'] : null;
    
    // Validate input
    if (empty($message_text)) {
        $error = "Message text is required.";
    } elseif ($day_of_week !== null && ($day_of_week < 1 || $day_of_week > 7)) {
        $error = "Day of week must be between 1 and 7.";
    } else {
        if (isset($_POST['message_id']) && $_POST['message_id'] > 0) {
            // Update existing message
            $message_id = (int)$_POST['message_id'];
            
            $stmt = $conn->prepare("UPDATE daily_messages SET message_text = ?, day_of_week = ? WHERE id = ?");
            $stmt->bind_param("sii", $message_text, $day_of_week, $message_id);
            
            if ($stmt->execute()) {
                $success = "Message updated successfully.";
                // Reset form
                $message_text = "";
                $day_of_week = null;
                $action = '';
            } else {
                $error = "Failed to update message: " . $conn->error;
            }
        } else {
            // Check if a message for this day already exists
            if ($day_of_week !== null) {
                $stmt = $conn->prepare("SELECT id FROM daily_messages WHERE day_of_week = ?");
                $stmt->bind_param("i", $day_of_week);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "A message for this day already exists. Please edit the existing message.";
                    $stmt->close();
                    goto skipInsertion;
                }
            }
            
            // Create new message
            $stmt = $conn->prepare("INSERT INTO daily_messages (message_text, day_of_week) VALUES (?, ?)");
            $stmt->bind_param("si", $message_text, $day_of_week);
            
            if ($stmt->execute()) {
                $success = "Message created successfully.";
                // Reset form
                $message_text = "";
                $day_of_week = null;
                $action = '';
            } else {
                $error = "Failed to create message: " . $conn->error;
            }
        }
        $stmt->close();
    }
    
    skipInsertion:
}

// Handle message edit
if ($action === 'edit' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT id, message_text, day_of_week FROM daily_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $message = $result->fetch_assoc();
        $message_text = $message['message_text'];
        $day_of_week = $message['day_of_week'];
    } else {
        $error = "Message not found.";
        $action = '';
    }
    $stmt->close();
}

// Handle message delete
if ($action === 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    // Check if message exists
    $stmt = $conn->prepare("SELECT id FROM daily_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Delete message
        $stmt = $conn->prepare("DELETE FROM daily_messages WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        
        if ($stmt->execute()) {
            $success = "Message deleted successfully.";
        } else {
            $error = "Failed to delete message: " . $conn->error;
        }
    } else {
        $error = "Message not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: manage_messages.php");
    exit;
}

// Get all messages
$messages = array();
$sql = "SELECT id, message_text, day_of_week, created_at FROM daily_messages ORDER BY day_of_week, id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Array of day names for display
$day_names = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Daily Messages - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="admin-logo"><?php echo APP_NAME; ?> Admin</div>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="index.php" class="sidebar-menu-link">
                        <i class="fas fa-tachometer-alt sidebar-menu-icon"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage_activities.php" class="sidebar-menu-link">
                        <i class="fas fa-tasks sidebar-menu-icon"></i> Activities
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage_reasons.php" class="sidebar-menu-link">
                        <i class="fas fa-question-circle sidebar-menu-icon"></i> Miss Reasons
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage_messages.php" class="sidebar-menu-link active">
                        <i class="fas fa-comment sidebar-menu-icon"></i> Daily Messages
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="view_users.php" class="sidebar-menu-link">
                        <i class="fas fa-users sidebar-menu-icon"></i> Users
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="logout.php" class="sidebar-menu-link">
                        <i class="fas fa-sign-out-alt sidebar-menu-icon"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="admin-header">
                <h1 class="page-title">Manage Daily Messages</h1>
                
                <div class="admin-header-actions">
                    <?php if ($action !== 'add' && $action !== 'edit'): ?>
                    <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Message</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Message Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit' : 'Add'; ?> Daily Message</h3>
                </div>
                
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group mb-3">
                            <label for="message_text" class="form-label">Message Text</label>
                            <textarea class="form-control" id="message_text" name="message_text" rows="4" required><?php echo htmlspecialchars($message_text); ?></textarea>
                            <small class="form-text text-muted">Enter the daily message to display on the login page.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="day_of_week" class="form-label">Day of Week</label>
                            <select class="form-control" id="day_of_week" name="day_of_week">
                                <option value="">Display every day (default)</option>
                                <?php foreach ($day_names as $day_num => $day_name): ?>
                                <option value="<?php echo $day_num; ?>" <?php echo $day_of_week === $day_num ? 'selected' : ''; ?>><?php echo $day_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select a specific day to display this message, or leave blank for a general message.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Update' : 'Create'; ?> Message</button>
                            <a href="manage_messages.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Messages List -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($messages) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Message Text</th>
                                    <th>Day</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?php echo $message['id']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($message['message_text'], 0, 100)) . (strlen($message['message_text']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo $message['day_of_week'] !== null ? $day_names[$message['day_of_week']] : 'Every day'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message?');"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No messages found. <a href="?action=add">Add a message</a>.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 