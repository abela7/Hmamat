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
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = "";
$success = "";

// Handle user delete
if ($action === 'delete' && $user_id > 0) {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully.";
        } else {
            $error = "Failed to delete user: " . $conn->error;
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: view_users.php");
    exit;
}

// Handle user role change
if ($action === 'make_admin' && $user_id > 0) {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Update user role
        $role = 'admin';
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $user_id);
        
        if ($stmt->execute()) {
            $success = "User promoted to admin successfully.";
        } else {
            $error = "Failed to update user role: " . $conn->error;
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: view_users.php");
    exit;
}

if ($action === 'remove_admin' && $user_id > 0) {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Update user role
        $role = 'user';
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $user_id);
        
        if ($stmt->execute()) {
            $success = "Admin role removed successfully.";
        } else {
            $error = "Failed to update user role: " . $conn->error;
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
    
    // Redirect to remove action from URL
    header("Location: view_users.php");
    exit;
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

// Base query
$query = "SELECT id, baptism_name, email, role, last_login, last_ip, created_at FROM users";
$count_query = "SELECT COUNT(*) as total FROM users";

// Add search conditions
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(baptism_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

// Combine where clauses
if (!empty($where_clauses)) {
    $where_statement = " WHERE " . implode(" AND ", $where_clauses);
    $query .= $where_statement;
    $count_query .= $where_statement;
}

// Finalize query with order and limits
$query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get total users count for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params) && !empty($types)) {
    // Remove the limit and offset parameters for the count query
    $count_types = substr($types, 0, -2);
    $count_params = array_slice($params, 0, -2);
    
    if (!empty($count_params)) {
        $ref_params = [];
        $count_stmt->bind_param($count_types, ...$count_params);
    }
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_users = $count_row['total'];
$total_pages = ceil($total_users / $limit);

// Get users for current page
$stmt = $conn->prepare($query);
if (!empty($params) && !empty($types)) {
    $ref_params = [];
    foreach ($params as $key => $value) {
        $ref_params[$key] = &$params[$key];
    }
    
    $stmt->bind_param($types, ...$ref_params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - <?php echo APP_NAME; ?></title>
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
                    <a href="manage_messages.php" class="sidebar-menu-link">
                        <i class="fas fa-comment sidebar-menu-icon"></i> Daily Messages
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="view_users.php" class="sidebar-menu-link active">
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
                <h1 class="page-title">View Users</h1>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by baptism name or email" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="role" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Regular Users</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Baptism Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['baptism_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($user['role'] !== 'admin'): ?>
                                            <a href="?action=make_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to make this user an admin?');"><i class="fas fa-user-shield"></i> Make Admin</a>
                                            <?php else: ?>
                                            <a href="?action=remove_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Are you sure you want to remove admin privileges?');"><i class="fas fa-user"></i> Remove Admin</a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="alert alert-info">No users found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 