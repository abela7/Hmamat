<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if admin registration is allowed
$admin_registration_allowed = true; // You might want to set this to false in production or limit by IP

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$username = "";
$password = "";
$confirm_password = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose another one or login.";
        } else {
            // Create new admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Get client info for tracking
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            // Insert admin
            $stmt = $conn->prepare("INSERT INTO admins (username, password, last_ip, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $username, $hashed_password, $ip_address);
            
            if ($stmt->execute()) {
                // Auto-login the admin and redirect to dashboard
                $admin_id = $stmt->insert_id;
                createAdminSession($admin_id, $username);
                header("Location: index.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php if (!$admin_registration_allowed): ?>
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card" style="width: 400px;">
            <div class="card-body">
                <h2 class="card-title text-center mb-4"><?php echo APP_NAME; ?> Admin</h2>
                <div class="alert alert-danger">
                    Admin registration is not allowed. Please contact the system administrator.
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card" style="width: 400px;">
            <div class="card-body">
                <h2 class="card-title text-center mb-4"><?php echo APP_NAME; ?> Admin Registration</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                    <p><a href="../" class="text-decoration-none">Back to User Interface</a></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 