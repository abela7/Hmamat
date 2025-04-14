<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set page title
$page_title = "Settings";

// Get user information
$user_id = $_SESSION['user_id'];
$baptism_name = $_SESSION['baptism_name'];

// Get user language preference (default to English if not set)
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'en';

// Initialize success and error messages
$success = '';
$error = '';

// Handle Reset Progress
if (isset($_POST['reset_progress'])) {
    // Delete all activity records for this user
    $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success = $language === 'am' ? 'እድገትዎ በተሳካ ሁኔታ ዳግም ተጀምሯል።' : 'Your progress has been successfully reset.';
    } else {
        $error = $language === 'am' ? 'እድገትዎን ዳግም ሲጀምሩ ስህተት ተከስቷል።' : 'Error resetting your progress.';
    }
    $stmt->close();
}

// Handle Change Baptism Name
if (isset($_POST['change_name']) && isset($_POST['new_baptism_name'])) {
    $new_baptism_name = trim($_POST['new_baptism_name']);
    
    if (empty($new_baptism_name)) {
        $error = $language === 'am' ? 'የጥምቀት ስም ባዶ መሆን አይችልም።' : 'Baptism name cannot be empty.';
    } else {
        // Update baptism name in database
        $stmt = $conn->prepare("UPDATE users SET baptism_name = ? WHERE id = ?");
        $stmt->bind_param("si", $new_baptism_name, $user_id);
        
        if ($stmt->execute()) {
            // Update session
            $_SESSION['baptism_name'] = $new_baptism_name;
            $baptism_name = $new_baptism_name;
            $success = $language === 'am' ? 'የጥምቀት ስምዎ በተሳካ ሁኔታ ተቀይሯል።' : 'Your baptism name has been successfully changed.';
        } else {
            $error = $language === 'am' ? 'የጥምቀት ስምዎን በመቀየር ላይ ስህተት ተከስቷል።' : 'Error changing your baptism name.';
        }
        $stmt->close();
    }
}

// Handle Delete Account
if (isset($_POST['delete_account']) && isset($_POST['confirm_delete'])) {
    if ($_POST['confirm_delete'] === 'DELETE') {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete user's activities
            $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete user's preferences
            $stmt = $conn->prepare("DELETE FROM user_preferences WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete user's sessions
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete user's device records
            $stmt = $conn->prepare("DELETE FROM user_devices WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Finally delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Clear sessions and cookies
            session_unset();
            session_destroy();
            
            setcookie('user_unique_id', '', time() - 3600, '/');
            setcookie('hmt_device_token', '', time() - 3600, '/');
            setcookie('user_language', '', time() - 3600, '/');
            
            // Redirect to welcome page
            header("Location: welcome.php?step=1");
            exit;
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $error = $language === 'am' ? 'መለያዎን በመሰረዝ ላይ ስህተት ተከስቷል።' : 'Error deleting your account: ' . $e->getMessage();
        }
    } else {
        $error = $language === 'am' ? 'ለማረጋገጥ እባክዎን "DELETE" ይጻፉ።' : 'Please type "DELETE" to confirm.';
    }
}

// Get user preferences
$show_on_leaderboard = true;
$email_notifications = true;
$user_language = $language;

$stmt = $conn->prepare("SELECT language, show_on_leaderboard, email_notifications FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $preferences = $result->fetch_assoc();
    $user_language = $preferences['language'];
    $show_on_leaderboard = (bool)$preferences['show_on_leaderboard'];
    $email_notifications = (bool)$preferences['email_notifications'];
}
$stmt->close();

// Process preferences form submission
if (isset($_POST['save_preferences'])) {
    // Get form data
    $new_language = 'am'; // Force Amharic
    $new_show_on_leaderboard = isset($_POST['show_on_leaderboard']) ? 1 : 0;
    $new_email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    // Check if preferences record exists
    $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing preferences
        $stmt = $conn->prepare("UPDATE user_preferences SET 
                                language = ?, 
                                show_on_leaderboard = ?, 
                                email_notifications = ?, 
                                updated_at = NOW() 
                                WHERE user_id = ?");
        $stmt->bind_param("siii", $new_language, $new_show_on_leaderboard, $new_email_notifications, $user_id);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("INSERT INTO user_preferences 
                                (user_id, language, show_on_leaderboard, email_notifications, created_at, updated_at) 
                                VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("isii", $user_id, $new_language, $new_show_on_leaderboard, $new_email_notifications);
    }
    
    if ($stmt->execute()) {
        // Update local variables for display
        $user_language = $new_language;
        $show_on_leaderboard = (bool)$new_show_on_leaderboard;
        $email_notifications = (bool)$new_email_notifications;
        
        // Set language cookie
        setcookie('user_language', $new_language, time() + (86400 * 90), "/");
        
        $success = $user_language === 'am' ? 'ማስተካከያዎች በተሳካ ሁኔታ ተቀምጠዋል።' : 'Settings saved successfully.';
    } else {
        $error = $user_language === 'am' ? 'ማስተካከያዎችን በማስቀመጥ ላይ ስህተት ተከስቷል።' : 'Error saving settings.';
    }
    $stmt->close();
}

// Include header
include_once '../includes/user_header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">
                        <?php echo $language === 'am' ? 'ማስተካከያዎች' : 'Settings'; ?>
                    </h2>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- User Preferences -->
                    <form method="post" action="settings.php">
                        <!-- Visibility Settings -->
                        <div class="form-group mb-4">
                            <label class="form-label">
                                <?php echo $language === 'am' ? 'ከሌሎች ጋር ይካፈሉ' : 'Share with others'; ?>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="show-on-leaderboard" 
                                       name="show_on_leaderboard" <?php echo $show_on_leaderboard ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show-on-leaderboard">
                                    <?php echo $language === 'am' ? 'በንግድ ሰሌዳ ላይ አሳይ' : 'Show on leaderboard'; ?>
                                </label>
                                <div class="form-text">
                                    <?php echo $language === 'am' ? 'ይህን ካጠፉት፣ በመሪ ሰሌዳ ላይ አይታዩም።' : 'If turned off, you will not appear on the leaderboard.'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="save_preferences" class="btn btn-primary">
                                <?php echo $language === 'am' ? 'ማስተካከያዎችን አስቀምጥ' : 'Save Settings'; ?>
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Reset Progress -->
                    <div class="settings-section mb-4">
                        <h3 class="settings-heading danger-text">
                            <?php echo $language === 'am' ? 'እድገት ዳግም ያስጀምሩ' : 'Reset Progress'; ?>
                        </h3>
                        <p class="text-muted">
                            <?php echo $language === 'am' ? 'ይህ የእርስዎን ሁሉንም ነጥቦች እና ሪኮርዶች ይሰርዛል። ይህ እርምጃ ተመልሶ ሊወሰድ አይችልም።' : 'This will delete all your points and activity records. This action cannot be undone.'; ?>
                        </p>
                        <form method="post" action="settings.php" onsubmit="return confirm('<?php echo $language === 'am' ? 'እርግጠኛ ነዎት? ይህ እርምጃ ሁሉንም ሪኮርዶችዎን ይሰርዛል።' : 'Are you sure? This will delete all your records.'; ?>');">
                            <button type="submit" name="reset_progress" class="btn btn-danger">
                                <?php echo $language === 'am' ? 'እድገት ዳግም ያስጀምሩ' : 'Reset Progress'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Delete Account -->
                    <div class="settings-section mb-4">
                        <h3 class="settings-heading danger-text">
                            <?php echo $language === 'am' ? 'መለያ ሰርዝ' : 'Delete Account'; ?>
                        </h3>
                        <p class="text-muted">
                            <?php echo $language === 'am' ? 'መለያዎን መሰረዝ ሁሉንም ውሂብዎን እና መለያዎን በቋሚነት ይሰርዛል። ይህ እርምጃ ተመልሶ ሊወሰድ አይችልም።' : 'Deleting your account will permanently remove all your data and account. This action cannot be undone.'; ?>
                        </p>
                        <form method="post" action="settings.php" onsubmit="return confirm('<?php echo $language === 'am' ? 'እርግጠኛ ነዎት? ይህ እርምጃ መለያዎን በቋሚነት ይሰርዛል።' : 'Are you sure? This will permanently delete your account.'; ?>');">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="confirm_delete" placeholder="<?php echo $language === 'am' ? 'ለማረጋገጥ DELETE ይጻፉ' : 'Type DELETE to confirm'; ?>">
                                <button type="submit" name="delete_account" class="btn btn-danger">
                                    <?php echo $language === 'am' ? 'መለያ ሰርዝ' : 'Delete Account'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-heading {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.danger-text {
    color: #dc3545;
}

.settings-section {
    padding-top: 15px;
}
</style>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 