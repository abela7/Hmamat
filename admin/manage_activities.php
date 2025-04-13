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
$activity_id = 0;
$name = "";
$description = "";
$default_points = 5;
$day_of_week = null;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $default_points = (int)$_POST['default_points'];
    $day_of_week = isset($_POST['day_of_week']) && $_POST['day_of_week'] !== '' ? (int)$_POST['day_of_week'] : null;
    
    // Validate input
    if (empty($name)) {
        $error = "Activity name is required.";
    } elseif ($default_points < 1) {
        $error = "Default points must be at least 1.";
    } elseif ($day_of_week !== null && ($day_of_week < 1 || $day_of_week > 7)) {
        $error = "Day of week must be between 1 and 7.";
    } else {
        if (isset($_POST['activity_id']) && $_POST['activity_id'] > 0) {
            // Update existing activity
            $activity_id = (int)$_POST['activity_id'];
            
            $stmt = $conn->prepare("UPDATE activities SET name = ?, description = ?, default_points = ?, day_of_week = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $name, $description, $default_points, $day_of_week, $activity_id);
            
            if ($stmt->execute()) {
                $success = "Activity updated successfully.";
                // Reset form
                $name = "";
                $description = "";
                $default_points = 5;
                $day_of_week = null;
                $action = '';
            } else {
                $error = "Failed to update activity: " . $conn->error;
            }
        } else {
            // Create new activity
            $stmt = $conn->prepare("INSERT INTO activities (name, description, default_points, day_of_week) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $description, $default_points, $day_of_week);
            
            if ($stmt->execute()) {
                $success = "Activity created successfully.";
                // Reset form
                $name = "";
                $description = "";
                $default_points = 5;
                $day_of_week = null;
                $action = '';
            } else {
                $error = "Failed to create activity: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle activity edit
if ($action === 'edit' && isset($_GET['id'])) {
    $activity_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT id, name, description, default_points, day_of_week FROM activities WHERE id = ?");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $activity = $result->fetch_assoc();
        $name = $activity['name'];
        $description = $activity['description'];
        $default_points = $activity['default_points'];
        $day_of_week = $activity['day_of_week'];
    } else {
        $error = "Activity not found.";
        $action = '';
    }
    $stmt->close();
}

// Handle activity delete
if ($action === 'delete' && isset($_GET['id'])) {
    $activity_id = (int)$_GET['id'];
    
    // Check if activity exists
    $stmt = $conn->prepare("SELECT id FROM activities WHERE id = ?");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Delete activity
        $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->bind_param("i", $activity_id);
        
        if ($stmt->execute()) {
            $success = "Activity deleted successfully.";
        } else {
            $error = "Failed to delete activity: " . $conn->error;
        }
    } else {
        $error = "Activity not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: manage_activities.php");
    exit;
}

// Get all activities
$activities = array();
$sql = "SELECT id, name, description, default_points, day_of_week FROM activities ORDER BY day_of_week, name";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Activities - <?php echo APP_NAME; ?></title>
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
                    <a href="manage_activities.php" class="sidebar-menu-link active">
                        <i class="fas fa-tasks sidebar-menu-icon"></i> Activities
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="manage_reasons.php" class="sidebar-menu-link">
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
                <h1 class="page-title">Manage Activities</h1>
                
                <div class="admin-header-actions">
                    <?php if ($action !== 'add' && $action !== 'edit'): ?>
                    <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Activity</a>
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
            <!-- Activity Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit' : 'Add'; ?> Activity</h3>
                </div>
                
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Activity Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label for="default_points" class="form-label">Default Points</label>
                                <input type="number" class="form-control" id="default_points" name="default_points" value="<?php echo $default_points; ?>" min="1" required>
                            </div>
                            
                            <div class="form-col">
                                <label for="day_of_week" class="form-label">Day of Week (Optional)</label>
                                <select class="form-control" id="day_of_week" name="day_of_week">
                                    <option value="">Available every day</option>
                                    <option value="1" <?php echo $day_of_week === 1 ? 'selected' : ''; ?>>Monday</option>
                                    <option value="2" <?php echo $day_of_week === 2 ? 'selected' : ''; ?>>Tuesday</option>
                                    <option value="3" <?php echo $day_of_week === 3 ? 'selected' : ''; ?>>Wednesday</option>
                                    <option value="4" <?php echo $day_of_week === 4 ? 'selected' : ''; ?>>Thursday</option>
                                    <option value="5" <?php echo $day_of_week === 5 ? 'selected' : ''; ?>>Friday</option>
                                    <option value="6" <?php echo $day_of_week === 6 ? 'selected' : ''; ?>>Saturday</option>
                                    <option value="7" <?php echo $day_of_week === 7 ? 'selected' : ''; ?>>Sunday</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Update' : 'Create'; ?> Activity</button>
                            <a href="manage_activities.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Activities List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activities</h3>
                </div>
                
                <div class="table-container">
                    <?php if (empty($activities)): ?>
                    <p class="p-3 text-center">No activities found. Click "Add Activity" to create one.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Points</th>
                                <th>Day of Week</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['name']); ?></td>
                                <td>
                                    <?php echo !empty($activity['description']) ? htmlspecialchars($activity['description']) : '<em>No description</em>'; ?>
                                </td>
                                <td><?php echo $activity['default_points']; ?></td>
                                <td>
                                    <?php 
                                    if ($activity['day_of_week'] === null) {
                                        echo 'Every day';
                                    } else {
                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        echo $days[$activity['day_of_week'] - 1];
                                    }
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="?action=edit&id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this activity?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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