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
$reason_id = 0;
$reason_text = "";
$action = isset($_GET['action']) ? $_GET['action'] : '';
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $reason_text = trim($_POST['reason_text']);
    
    // Validate input
    if (empty($reason_text)) {
        $error = "Reason text is required.";
    } else {
        if (isset($_POST['reason_id']) && $_POST['reason_id'] > 0) {
            // Update existing reason
            $reason_id = (int)$_POST['reason_id'];
            
            $stmt = $conn->prepare("UPDATE activity_miss_reasons SET reason_text = ? WHERE id = ?");
            $stmt->bind_param("si", $reason_text, $reason_id);
            
            if ($stmt->execute()) {
                $success = "Reason updated successfully.";
                // Reset form
                $reason_text = "";
                $action = '';
            } else {
                $error = "Failed to update reason: " . $conn->error;
            }
        } else {
            // Create new reason
            $stmt = $conn->prepare("INSERT INTO activity_miss_reasons (reason_text) VALUES (?)");
            $stmt->bind_param("s", $reason_text);
            
            if ($stmt->execute()) {
                $success = "Reason created successfully.";
                // Reset form
                $reason_text = "";
                $action = '';
            } else {
                $error = "Failed to create reason: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle reason edit
if ($action === 'edit' && isset($_GET['id'])) {
    $reason_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT id, reason_text FROM activity_miss_reasons WHERE id = ?");
    $stmt->bind_param("i", $reason_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $reason = $result->fetch_assoc();
        $reason_text = $reason['reason_text'];
    } else {
        $error = "Reason not found.";
        $action = '';
    }
    $stmt->close();
}

// Handle reason delete
if ($action === 'delete' && isset($_GET['id'])) {
    $reason_id = (int)$_GET['id'];
    
    // Check if reason exists
    $stmt = $conn->prepare("SELECT id FROM activity_miss_reasons WHERE id = ?");
    $stmt->bind_param("i", $reason_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Check if reason is used in user_activity_log
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_activity_log WHERE reason_id = ?");
        $stmt->bind_param("i", $reason_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Cannot delete this reason as it is being used in activity logs.";
        } else {
            // Delete reason
            $stmt = $conn->prepare("DELETE FROM activity_miss_reasons WHERE id = ?");
            $stmt->bind_param("i", $reason_id);
            
            if ($stmt->execute()) {
                $success = "Reason deleted successfully.";
            } else {
                $error = "Failed to delete reason: " . $conn->error;
            }
        }
    } else {
        $error = "Reason not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: manage_reasons.php");
    exit;
}

// Get all reasons
$reasons = array();
$sql = "SELECT id, reason_text, created_at FROM activity_miss_reasons ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reasons[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Miss Reasons - <?php echo APP_NAME; ?></title>
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
                    <a href="manage_reasons.php" class="sidebar-menu-link active">
                        <i class="fas fa-question-circle sidebar-menu-icon"></i> Miss Reasons
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage_messages.php" class="sidebar-menu-link">
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
                <h1 class="page-title">Manage Activity Miss Reasons</h1>
                
                <div class="admin-header-actions">
                    <?php if ($action !== 'add' && $action !== 'edit'): ?>
                    <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Reason</a>
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
            <!-- Reason Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit' : 'Add'; ?> Miss Reason</h3>
                </div>
                
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="reason_id" value="<?php echo $reason_id; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group mb-3">
                            <label for="reason_text" class="form-label">Reason Text</label>
                            <input type="text" class="form-control" id="reason_text" name="reason_text" value="<?php echo htmlspecialchars($reason_text); ?>" required>
                            <small class="form-text text-muted">Enter a valid reason why someone might miss an activity.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Update' : 'Create'; ?> Reason</button>
                            <a href="manage_reasons.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Reasons List -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($reasons) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reason Text</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reasons as $reason): ?>
                                <tr>
                                    <td><?php echo $reason['id']; ?></td>
                                    <td><?php echo htmlspecialchars($reason['reason_text']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($reason['created_at'])); ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $reason['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?action=delete&id=<?php echo $reason['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this reason?');"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No reasons found. <a href="?action=add">Add a reason</a>.</div>
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