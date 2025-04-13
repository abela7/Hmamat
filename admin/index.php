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

// Get statistics
$stats = array(
    'total_users' => 0,
    'active_users' => 0,
    'total_activities' => 0,
    'activities_completed' => 0
);

// Total users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['total_users'] = $row['count'];
}
$stmt->close();

// Active users (with activity in the last 7 days)
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM user_activity_log 
                       WHERE date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['active_users'] = $row['count'];
}
$stmt->close();

// Total activities
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM activities");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['total_activities'] = $row['count'];
}
$stmt->close();

// Activities completed today
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_activity_log 
                       WHERE date_completed = CURDATE() AND status = 'done'");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['activities_completed'] = $row['count'];
}
$stmt->close();

// Recent activity log
$recent_activity = array();
$stmt = $conn->prepare("SELECT u.baptism_name, a.name as activity_name, ual.date_completed, 
                       ual.status, ual.points_earned, amr.reason_text
                       FROM user_activity_log ual
                       JOIN users u ON ual.user_id = u.id
                       JOIN activities a ON ual.activity_id = a.id
                       LEFT JOIN activity_miss_reasons amr ON ual.reason_id = amr.id
                       ORDER BY ual.created_at DESC
                       LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
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
                    <a href="index.php" class="sidebar-menu-link active">
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
                <h1 class="page-title">Dashboard</h1>
                
                <div class="admin-header-actions">
                    <div class="admin-user">
                        <i class="fas fa-user"></i>
                        <span class="admin-username"><?php echo htmlspecialchars($admin_username); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_activities']; ?></div>
                    <div class="stat-label">Activities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['activities_completed']; ?></div>
                    <div class="stat-label">Completed Today</div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                </div>
                
                <div class="table-container">
                    <?php if (empty($recent_activity)): ?>
                    <p class="p-3 text-center">No recent activity to display.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Activity</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Points</th>
                                <th>Reason (if not done)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['baptism_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['activity_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($activity['date_completed'])); ?></td>
                                <td>
                                    <?php if ($activity['status'] == 'done'): ?>
                                    <span class="badge badge-success">Done</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Not Done</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $activity['points_earned']; ?></td>
                                <td><?php echo htmlspecialchars($activity['reason_text'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="manage_activities.php?action=add" class="btn btn-block">Add New Activity</a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="manage_reasons.php?action=add" class="btn btn-block">Add Miss Reason</a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="manage_messages.php?action=add" class="btn btn-block">Add Daily Message</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 