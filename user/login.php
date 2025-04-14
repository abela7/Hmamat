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
$password_required = false;
$matched_user = null;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Process baptism name form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_baptism_name'])) {
    $baptism_name = trim($_POST['baptism_name']);
    
    if (empty($baptism_name)) {
        $error = "Baptism name is required.";
    } else {
        // Check if we have this user
        $stmt = $conn->prepare("SELECT id, baptism_name, last_ip, user_agent FROM users WHERE baptism_name = ?");
        $stmt->bind_param("s", $baptism_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users_count = $result->num_rows;
        
        if ($users_count === 0) {
            $error = "Baptism name not found.";
        } elseif ($users_count === 1) {
            // Single user with this baptism name
            $matched_user = $result->fetch_assoc();
            
            // Check if device info matches for auto-login
            $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $current_device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Check device fingerprint first
            $device_token = isset($_COOKIE['hmt_device_token']) ? $_COOKIE['hmt_device_token'] : null;
            
            if ($device_token) {
                $device_stmt = $conn->prepare("SELECT user_id FROM user_devices WHERE device_token = ? AND user_id = ?");
                $device_stmt->bind_param("si", $device_token, $matched_user['id']);
                $device_stmt->execute();
                $device_result = $device_stmt->get_result();
                
                if ($device_result->num_rows === 1) {
                    // Found device match, auto-login
                    $stmt = $conn->prepare("SELECT id, baptism_name, unique_id, role FROM users WHERE id = ?");
                    $stmt->bind_param("i", $matched_user['id']);
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    $user = $user_result->fetch_assoc();
                    
                    createUserSession($user['id'], $user['baptism_name'], $user['unique_id'], $user['role']);
                    header("Location: " . $redirect);
                    exit;
                }
            }
            
            // If IP and user agent match, allow auto-login
            if ($matched_user['last_ip'] === $current_ip && $matched_user['user_agent'] === $current_device) {
                $stmt = $conn->prepare("SELECT id, baptism_name, unique_id, role FROM users WHERE id = ?");
                $stmt->bind_param("i", $matched_user['id']);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $user = $user_result->fetch_assoc();
                
                createUserSession($user['id'], $user['baptism_name'], $user['unique_id'], $user['role']);
                header("Location: " . $redirect);
                exit;
            }
            
            // Otherwise, require password
            $password_required = true;
        } else {
            // Multiple users, need password to disambiguate
            $password_required = true;
        }
        $stmt->close();
    }
}

// Process full login form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_with_password'])) {
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
        
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">ሰሙነ ሕማማት</h2>
                
                <div class="text-center mb-4">
                    <p>የዐቢይ ጾም የመጨረሻ ሳምንት</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">ለዚህ ድህረ ገጹ አዲስ ነዎት?</h5>
                                 
                                <a href="welcome.php?step=1" class="btn">እዚህ ላይ ይጫኑ</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">ካሁን በፊት ተመዝግበዋል?</h5>
                                
                                <button type="button" class="btn" data-bs-toggle="collapse" data-bs-target="#loginForm">እዚህ ላይ ይጫኑ</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="collapse mt-4" id="loginForm">
                    <?php if (!$password_required): ?>
                        <!-- Step 1: Enter Baptism Name -->
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($redirect != 'dashboard.php' ? '?redirect=' . urlencode($redirect) : '')); ?>">
                            <div class="form-group mb-3">
                                <label for="baptism_name" class="form-label"><?php echo $language === 'am' ? 'የጥምቀት ስም' : 'Baptism Name'; ?></label>
                                <input type="text" class="form-control" id="baptism_name" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="check_baptism_name" class="btn btn-block"><?php echo $language === 'am' ? 'ቀጥል' : 'Continue'; ?></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Step 2: Enter Password (if needed) -->
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <?php echo $language === 'am' ? 'እባክዎ የ ' . htmlspecialchars($baptism_name) . ' መለያ የይለፍ ቃል ያረጋግጡ።' : 'Please confirm password for ' . htmlspecialchars($baptism_name); ?>
                            </div>
                        </div>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($redirect != 'dashboard.php' ? '?redirect=' . urlencode($redirect) : '')); ?>">
                            <input type="hidden" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>">
                            
                            <div class="form-group mb-4">
                                <label for="password" class="form-label"><?php echo $language === 'am' ? 'የይለፍ ቃል' : 'Password'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted"><?php echo $language === 'am' ? 'በጥምቀት ስምዎ ብቻ ከተመዘገቡ፣ የይለፍ ቃልዎ 000000 ነው' : 'If you registered with just your baptism name, your password is 000000'; ?></small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login_with_password" class="btn btn-block"><?php echo $language === 'am' ? 'ግባ' : 'Login'; ?></button>
                            </div>
                            
                            <div class="mt-3">
                                <a href="login.php" class="btn btn-link btn-sm"><?php echo $language === 'am' ? 'መመለስ' : 'Go back'; ?></a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 