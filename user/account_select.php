<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Account - Hmamat</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>Select Your Account</h1>
            <p>Multiple accounts found with the baptism name: <strong><?php echo htmlspecialchars($baptism_name); ?></strong></p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="account-list">
                <?php foreach ($users as $user): ?>
                    <div class="account-item">
                        <form method="POST" action="account_select.php">
                            <input type="hidden" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                            
                            <div class="account-info">
                                <p><strong>Baptism Name:</strong> <?php echo htmlspecialchars($user['baptism_name']); ?></p>
                                <p><strong>Last Login:</strong> <?php echo !empty($user['last_login']) ? $user['last_login'] : 'Never'; ?></p>
                                <p><strong>Last Device:</strong> <?php echo getDeviceInfo($user['user_agent']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_<?php echo $user['id']; ?>">Password:</label>
                                <input type="password" id="password_<?php echo $user['id']; ?>" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Log In</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

<?php
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