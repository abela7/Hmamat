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
}

/**
 * Create secure session token and store user session
 * @param int $user_id User ID
 * @param string $baptism_name User's baptism name
 * @param string $unique_id User's unique identifier
 * @return void
 */
function createUserSession($user_id, $baptism_name, $unique_id = '') {
    $session_token = bin2hex(random_bytes(32));
    $_SESSION['user_id'] = $user_id;
    $_SESSION['baptism_name'] = $baptism_name;
    $_SESSION['unique_id'] = $unique_id;
    $_SESSION['is_user'] = true;
    
    // Store session in database
    require_once 'db.php';
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
    
    // Set a cookie with the unique ID for persistent identification across sessions
    if (!empty($unique_id)) {
        setcookie('user_unique_id', $unique_id, time() + (86400 * 30), "/"); // 30 days
    }
}

/**
 * Attempt to identify a returning user based on unique ID, IP and user agent
 * @return array|null User data if found, null otherwise
 */
function identifyReturningUser() {
    require_once 'db.php';
    
    $unique_id = isset($_COOKIE['user_unique_id']) ? $_COOKIE['user_unique_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    if ($unique_id) {
        // Try to find user by unique ID
        $stmt = $conn->prepare("SELECT id, baptism_name, unique_id FROM users WHERE unique_id = ?");
        $stmt->bind_param("s", $unique_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Try to find user by IP and user agent as fallback
    $stmt = $conn->prepare("SELECT id, baptism_name, unique_id FROM users WHERE last_ip = ? AND user_agent = ? LIMIT 1");
    $stmt->bind_param("ss", $ip, $device_info);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Create admin session
 * @param int $admin_id Admin ID
 * @param string $username Admin username
 * @return void
 */
function createAdminSession($admin_id, $username) {
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['is_admin'] = true;
    
    // Update admin's last login
    require_once 'db.php';
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
}
?> 