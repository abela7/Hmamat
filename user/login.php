<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// If already logged in, redirect to dashboard
if (isUserLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Initialize variables
$baptism_name = "";
$error = "";

// Check if there's a redirect URL
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
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, baptism_name, password FROM users WHERE baptism_name = ?");
        $stmt->bind_param("s", $baptism_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Create user session
                createUserSession($user['id'], $user['baptism_name']);
                
                // Redirect to dashboard or specified page
                header("Location: " . $redirect);
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Baptism name not found.";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <?php if (!empty($daily_message)): ?>
                <div class="daily-message mb-4">
                    <p class="mb-0"><?php echo htmlspecialchars($daily_message); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <h1 class="text-center mb-4"><?php echo APP_NAME; ?></h1>
                        <h2 class="card-title text-center">Login</h2>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($redirect != 'dashboard.php' ? '?redirect=' . urlencode($redirect) : '')); ?>">
                            <div class="form-group mb-3">
                                <label for="baptism_name" class="form-label">Baptism Name</label>
                                <input type="text" class="form-control" id="baptism_name" name="baptism_name" value="<?php echo htmlspecialchars($baptism_name); ?>" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-block">Login</button>
                            </div>
                        </form>
                        
                        <p class="text-center mt-3">
                            Don't have an account? <a href="register.php">Register</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 