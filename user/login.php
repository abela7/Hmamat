<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = "Login";

// Check for logout flag
$just_logged_out = isset($_GET['logout']) && $_GET['logout'] == 'success';

// If already logged in, redirect to dashboard
if (isUserLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Try auto-login for returning visitors, but skip if user just logged out
if (!$just_logged_out) {
    $returning_user = identifyReturningUser();
    if ($returning_user) {
        // Auto-login the returning user and redirect
        createUserSession($returning_user['id'], $returning_user['baptism_name'], $returning_user['unique_id'], $returning_user['role']);
        header("Location: dashboard.php");
        exit;
    }
}

// Initialize variables
$baptism_name = "";
$error = "";
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $baptism_name = trim($_POST['baptism_name']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($baptism_name) || empty($password)) {
        $error = "Both baptism name and password are required.";
    } else {
        // Check if user exists - there may be multiple users with the same baptism name
        $stmt = $conn->prepare("SELECT id, baptism_name, password, unique_id, role, last_ip, user_agent FROM users WHERE baptism_name = ?");
        $stmt->bind_param("s", $baptism_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users_count = $result->num_rows;
        
        if ($users_count === 0) {
            $error = "Baptism name not found.";
        } elseif ($users_count === 1) {
            // Single user with this baptism name
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Create user session
                createUserSession($user['id'], $user['baptism_name'], $user['unique_id'], $user['role']);
                
                // Redirect to dashboard or specified page
                header("Location: " . $redirect);
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            // Multiple users with the same baptism name
            // Check if at least one user has the correct password
            $valid_users = [];
            $result->data_seek(0); // Reset result pointer
            
            while ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $valid_users[] = $user;
                }
            }
            
            if (count($valid_users) > 0) {
                // Store valid users in session and redirect to account selection page
                $_SESSION['valid_users'] = $valid_users;
                $_SESSION['redirect_url'] = $redirect;
                $_SESSION['account_select_password'] = $password;
                
                header("Location: account_select.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        }
        $stmt->close();
    }
}

// Get day of the week (1-7, Monday is 1)
$day_of_week = date('N');

// Get daily message if available
$daily_message = "Welcome to Holy Week Spiritual Tracker. Login to begin your spiritual journey.";
$stmt = $conn->prepare("SELECT message_text FROM daily_messages WHERE day_of_week = ? OR day_of_week IS NULL ORDER BY day_of_week DESC LIMIT 1");
$stmt->bind_param("i", $day_of_week);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $daily_message = $row['message_text'];
}
$stmt->close();

// Include header (without the user login check)
include_once '../includes/user_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <?php if ($just_logged_out): ?>
        <div class="alert alert-success mb-4">
            <p class="mb-0">You have been successfully logged out.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($daily_message)): ?>
        <div class="daily-message mb-4">
            <p class="mb-0"><?php echo htmlspecialchars($daily_message); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">Welcome to HIMAMAT</h2>
                
                <div class="text-center mb-4">
                    <p>The Holy Week Spiritual Tracker for Ethiopian Orthodox faithful.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">First Time Visitor?</h5>
                                <p class="card-text">Start your spiritual journey during HIMAMAT.</p>
                                <a href="welcome.php?step=1" class="btn">Get Started</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Returning User?</h5>
                                <p class="card-text">Enter your baptism name and password.</p>
                                <button type="button" class="btn" data-bs-toggle="collapse" data-bs-target="#loginForm">Login</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="collapse mt-4" id="loginForm">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($redirect != 'dashboard.php' ? '?redirect=' . urlencode($redirect) : '')); ?>">
                        <div class="form-group mb-3">
                            <label for="baptism_name" class="form-label">Baptism Name</label>
                            <input type="text" class="form-control" id="baptism_name" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="form-text text-muted">If you registered with just your baptism name, your password is 000000</small>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-block">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 