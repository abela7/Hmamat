<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = "Select Account";

// Initialize variables
$baptism_name = '';
$password = '';
$redirect = '../index.php';
$error = '';
$users = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $baptism_name = $_POST['baptism_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    $redirect = $_POST['redirect'] ?? '../index.php';
    
    // Validate data
    if (empty($baptism_name) || empty($password) || empty($user_id)) {
        $error = "All fields are required.";
    } else {
        // Get the specific user
        $stmt = $conn->prepare("SELECT id, baptism_name, password, unique_id, role FROM users WHERE id = ? AND baptism_name = ?");
        $stmt->bind_param("is", $user_id, $baptism_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
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
            $error = "User not found.";
        }
        $stmt->close();
    }
} else {
    // If direct access, check if we have the required parameters
    $baptism_name = $_GET['baptism_name'] ?? '';
    $redirect = $_GET['redirect'] ?? '../index.php';
    
    if (empty($baptism_name)) {
        header("Location: login.php");
        exit;
    }
    
    // Get all users with this baptism name
    $stmt = $conn->prepare("SELECT id, baptism_name, last_login, last_ip, user_agent FROM users WHERE baptism_name = ?");
    $stmt->bind_param("s", $baptism_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: login.php?error=User+not+found");
        exit;
    } elseif ($result->num_rows === 1) {
        // If only one user, redirect back to login
        header("Location: login.php?baptism_name=" . urlencode($baptism_name));
        exit;
    }
    
    // Fetch all matching users
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

// Include header (without user check)
include_once '../includes/user_header.php';

// Helper function to get simplified device info
function getDeviceInfo($userAgent) {
    if (empty($userAgent) || $userAgent === 'Unknown') {
        return 'Unknown device';
    }
    
    $device = 'Unknown device';
    
    // Detect mobile devices
    if (strpos($userAgent, 'iPhone') !== false) {
        $device = 'iPhone';
    } elseif (strpos($userAgent, 'iPad') !== false) {
        $device = 'iPad';
    } elseif (strpos($userAgent, 'Android') !== false) {
        if (strpos($userAgent, 'Mobile') !== false) {
            $device = 'Android phone';
        } else {
            $device = 'Android tablet';
        }
    } elseif (strpos($userAgent, 'Windows Phone') !== false) {
        $device = 'Windows Phone';
    }
    // Detect desktop devices
    elseif (strpos($userAgent, 'Windows') !== false) {
        $device = 'Windows computer';
    } elseif (strpos($userAgent, 'Macintosh') !== false) {
        $device = 'Mac computer';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $device = 'Linux computer';
    }
    
    // Add browser info
    if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
        $device .= ' (Chrome)';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $device .= ' (Firefox)';
    } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
        $device .= ' (Safari)';
    } elseif (strpos($userAgent, 'Edg') !== false) {
        $device .= ' (Edge)';
    } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        $device .= ' (Internet Explorer)';
    }
    
    return $device;
}
?>

<div class="simple-container">
    <div class="account-select-container">
        <h1 class="main-title">Select Your Account</h1>
        
        <p class="account-name-message">Multiple accounts found with the baptism name: <strong><?php echo htmlspecialchars($baptism_name); ?></strong></p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="account-list">
            <?php foreach ($users as $user): ?>
                <div class="account-item">
                    <div class="account-info">
                        <div class="info-row">
                            <strong>Baptism Name:</strong> <?php echo htmlspecialchars($user['baptism_name']); ?>
                        </div>
                        <div class="info-row">
                            <strong>Last Login:</strong> <?php echo !empty($user['last_login']) ? $user['last_login'] : 'Never'; ?>
                        </div>
                        <div class="info-row">
                            <strong>Last Device:</strong> <?php echo getDeviceInfo($user['user_agent']); ?>
                        </div>
                    </div>
                    
                    <form method="POST" action="account_select.php" class="account-form">
                        <input type="hidden" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        
                        <div class="form-group">
                            <label for="password_<?php echo $user['id']; ?>" class="form-label">Password:</label>
                            <input type="password" class="form-control" id="password_<?php echo $user['id']; ?>" name="password" required>
                        </div>
                        
                        <button type="submit" class="login-btn">Log In</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="back-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</div>

<style>
.simple-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.account-select-container {
    background-color: #F1ECE2;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.main-title {
    font-size: 1.8rem;
    color: #301934;
    margin-bottom: 20px;
    font-weight: 700;
    text-align: center;
}

.account-name-message {
    margin-bottom: 20px;
    text-align: center;
    color: #5D4225;
}

.account-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.account-item {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.account-info {
    margin-bottom: 15px;
}

.info-row {
    margin-bottom: 8px;
    color: #5D4225;
}

.info-row strong {
    color: #301934;
    margin-right: 5px;
}

.account-form {
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #301934;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #301934;
    border-radius: 4px;
    background-color: white;
    color: #301934;
}

.form-control:focus {
    outline: none;
    border-color: #DAA520;
    box-shadow: 0 0 0 2px rgba(218, 165, 32, 0.2);
}

.login-btn {
    background-color: #DAA520;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}

.login-btn:hover {
    background-color: #301934;
    transform: translateY(-2px);
}

.back-link {
    text-align: center;
    margin-top: 20px;
}

.back-link a {
    color: #301934;
    text-decoration: none;
    padding: 10px 20px;
    border: 1px solid #301934;
    border-radius: 5px;
    transition: all 0.2s ease;
    display: inline-block;
}

.back-link a:hover {
    background-color: #301934;
    color: white;
}

.alert {
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 576px) {
    .account-select-container {
        padding: 15px;
    }
    
    .main-title {
        font-size: 1.5rem;
    }
}
</style>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 