<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = "Register";

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Initialize variables
$baptism_name = "";
$password = "";
$confirm_password = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $baptism_name = trim($_POST['baptism_name']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate input
    if (empty($baptism_name) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Create new user - baptism names can be duplicated
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Get client info for tracking
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Set role to 'user' for regular user registration
        $role = 'user';
        
        // Generate unique identifier - this will differentiate users with same baptism name
        $unique_id = generate_unique_id();
        
        // Insert user with unique identifier and role
        $stmt = $conn->prepare("INSERT INTO users (baptism_name, role, password, unique_id, last_ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $baptism_name, $role, $hashed_password, $unique_id, $ip_address, $user_agent);
        
        if ($stmt->execute()) {
            // Auto-login the user and redirect to dashboard
            $user_id = $stmt->insert_id;
            createUserSession($user_id, $baptism_name, $unique_id, $role);
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}

/**
 * Generate a unique identifier for the user
 * @return string A unique identifier
 */
function generate_unique_id() {
    // Combine multiple factors for uniqueness
    $base = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . time() . rand(1000, 9999);
    return md5($base);
}

// Include header
include_once '../includes/user_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">Register</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group mb-3">
                        <label for="baptism_name" class="form-label">የክርስትና ስም</label>
                        <input type="text" class="form-control" id="baptism_name" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-block">Register</button>
                    </div>
                </form>
                
                <p class="text-center mt-3">
                    Already have an account? <a href="login.php">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 