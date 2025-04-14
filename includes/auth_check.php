<?php
// Include configuration
require_once 'config.php';

/**
 * Check user authentication
 * @return bool True if user is authenticated, false otherwise
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check admin authentication
 * @return bool True if admin is authenticated, false otherwise
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Check if the logged-in user has the admin role
 * @return bool True if user has admin role, false otherwise
 */
function hasAdminRole() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

/**
 * Redirect user to login page if not authenticated
 * @param string $redirect URL to redirect to after login
 */
function requireUserLogin($redirect = '') {
    if (!isUserLoggedIn()) {
        $redirect_url = !empty($redirect) ? '?redirect=' . urlencode($redirect) : '';
        header("Location: " . USER_URL . "/login.php" . $redirect_url);
        exit;
    }
}

/**
 * Redirect admin to login page if not authenticated
 * @param string $redirect URL to redirect to after login
 */
function requireAdminLogin($redirect = '') {
    if (!isAdminLoggedIn()) {
        $redirect_url = !empty($redirect) ? '?redirect=' . urlencode($redirect) : '';
        header("Location: " . ADMIN_URL . "/login.php" . $redirect_url);
        exit;
    }
    
    // Check admin role
    if (!hasAdminRole()) {
        header("Location: " . USER_URL . "/dashboard.php");
        exit;
    }
}

/**
 * Create secure session token and store user session
 * @param int $user_id User ID
 * @param string $baptism_name User's baptism name
 * @param string $unique_id User's unique identifier
 * @param string $role User's role
 * @return void
 */
function createUserSession($user_id, $baptism_name, $unique_id = '', $role = 'user') {
    $session_token = bin2hex(random_bytes(32));
    $_SESSION['user_id'] = $user_id;
    $_SESSION['baptism_name'] = $baptism_name;
    $_SESSION['unique_id'] = $unique_id;
    $_SESSION['is_user'] = true;
    $_SESSION['role'] = $role;
    
    // Store session in database
    require_once 'db.php';
    global $conn;
    
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Update the user's last login information
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), last_ip = ?, user_agent = ? WHERE id = ?");
    $stmt->bind_param("ssi", $ip, $device_info, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Add session record
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, device_info) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $session_token, $ip, $device_info);
    $stmt->execute();
    $stmt->close();
    
    // Set cookies for persistent identification
    if (!empty($unique_id)) {
        setcookie('user_unique_id', $unique_id, time() + (86400 * 90), "/", "", false, true); // 90 days
    }
    
    // Create or update device token
    createDeviceToken($user_id, $ip, $device_info);
}

/**
 * Attempt to identify a returning user based on unique ID, IP and user agent
 * @return array|null User data if found, null otherwise
 */
function identifyReturningUser() {
    require_once 'db.php';
    global $conn;
    
    // Check for our enhanced device fingerprint cookie first
    $device_token = isset($_COOKIE['hmt_device_token']) ? $_COOKIE['hmt_device_token'] : null;
    
    // Legacy check for unique_id
    $unique_id = isset($_COOKIE['user_unique_id']) ? $_COOKIE['user_unique_id'] : null;
    
    // Get current device info
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // First priority: Try to find by device token (most reliable)
    if ($device_token) {
        $stmt = $conn->prepare("SELECT u.id, u.baptism_name, u.unique_id, u.role 
                               FROM users u 
                               JOIN user_devices d ON u.id = d.user_id 
                               WHERE d.device_token = ? 
                               LIMIT 1");
        $stmt->bind_param("s", $device_token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Second priority: Try to find by unique ID cookie
    if ($unique_id) {
        $stmt = $conn->prepare("SELECT id, baptism_name, unique_id, role FROM users WHERE unique_id = ?");
        $stmt->bind_param("s", $unique_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // If found by unique_id but we don't have a device token yet,
            // create one for future use
            if (!$device_token) {
                createDeviceToken($user['id'], $ip, $device_info);
            }
            
            return $user;
        }
        $stmt->close();
    }
    
    // Third priority: Try to find by exact IP and user agent match as fallback
    // This is least reliable but helps with legacy users
    $stmt = $conn->prepare("SELECT id, baptism_name, unique_id, role FROM users 
                           WHERE last_ip = ? AND user_agent = ? 
                           ORDER BY last_login DESC LIMIT 1");
    $stmt->bind_param("ss", $ip, $device_info);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Create device token for future use
        if (!$device_token) {
            createDeviceToken($user['id'], $ip, $device_info);
        }
        
        return $user;
    }
    
    return null;
}

/**
 * Create a new device token for persistent recognition
 * @param int $user_id User ID
 * @param string $ip IP address
 * @param string $device_info User agent string
 * @return string The generated device token
 */
function createDeviceToken($user_id, $ip, $device_info) {
    require_once 'db.php';
    global $conn;
    
    // Generate a secure random token
    $device_token = bin2hex(random_bytes(32));
    
    // Create a simple device fingerprint (can be enhanced with JavaScript)
    $fingerprint = md5($ip . $device_info);
    
    // Check if we already have an entry for this device
    $stmt = $conn->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_fingerprint = ?");
    $stmt->bind_param("is", $user_id, $fingerprint);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing device
        $device = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE user_devices SET device_token = ?, last_used = NOW(), 
                              ip_address = ?, user_agent = ? WHERE id = ?");
        $stmt->bind_param("sssi", $device_token, $ip, $device_info, $device['id']);
    } else {
        // Insert new device
        $stmt = $conn->prepare("INSERT INTO user_devices (user_id, device_token, device_fingerprint, 
                              ip_address, user_agent, created_at, last_used) 
                              VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("issss", $user_id, $device_token, $fingerprint, $ip, $device_info);
    }
    
    $stmt->execute();
    $stmt->close();
    
    // Set long-lived cookie (90 days)
    setcookie('hmt_device_token', $device_token, time() + (86400 * 90), "/", "", false, true);
    
    return $device_token;
}

/**
 * Create admin session
 * @param int $admin_id Admin ID
 * @param string $username Admin username
 * @param string $role Admin's role
 * @return void
 */
function createAdminSession($admin_id, $username, $role = 'admin') {
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['is_admin'] = true;
    $_SESSION['role'] = $role;
    
    // Update admin's last login
    require_once 'db.php';
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $stmt = $conn->prepare("UPDATE admins SET last_login = NOW(), last_ip = ? WHERE id = ?");
    $stmt->bind_param("si", $ip, $admin_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * End user session
 * @return void
 */
function endUserSession() {
    if (isset($_SESSION['user_id'])) {
        require_once 'db.php';
        global $conn;
        
        $user_id = $_SESSION['user_id'];
        
        // Remove session from database
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Clear user session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['baptism_name']);
    unset($_SESSION['unique_id']);
    unset($_SESSION['is_user']);
    unset($_SESSION['role']);
    
    // Remove the unique ID cookie
    setcookie('user_unique_id', '', time() - 3600, '/');
}

/**
 * End admin session
 * @return void
 */
function endAdminSession() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['is_admin']);
    unset($_SESSION['role']);
}
?> 