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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $new_language = isset($_POST['language']) ? $_POST['language'] : 'en';
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
                    
                    <form method="post" action="settings.php">
                        <!-- Language Preference -->
                        <div class="form-group mb-4">
                            <label class="form-label">
                                <?php echo $language === 'am' ? 'ቋንቋ' : 'Language'; ?>
                            </label>
                            <div class="d-flex">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="radio" name="language" id="lang-en" 
                                           value="en" <?php echo $user_language === 'en' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="lang-en">English</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="language" id="lang-am" 
                                           value="am" <?php echo $user_language === 'am' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="lang-am">አማርኛ</label>
                                </div>
                            </div>
                        </div>
                        
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
                        
                        <!-- Notification Settings -->
                        <div class="form-group mb-4">
                            <label class="form-label">
                                <?php echo $language === 'am' ? 'ማሳወቂያዎች' : 'Notifications'; ?>
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email-notifications" 
                                       name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email-notifications">
                                    <?php echo $language === 'am' ? 'የኢሜይል ማሳወቂያዎችን ተቀበል' : 'Receive email notifications'; ?>
                                </label>
                                <div class="form-text">
                                    <?php echo $language === 'am' ? 'ስለ ቀጣይ ክንዋኔዎች እና ማሳሰቢያዎች ኢሜይሎችን ይቀበሉ።' : 'Receive emails about upcoming events and reminders.'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn">
                                <?php echo $language === 'am' ? 'ማስተካከያዎችን አስቀምጥ' : 'Save Settings'; ?>
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Account Settings -->
                    <h3>
                        <?php echo $language === 'am' ? 'የመለያ ቅንብሮች' : 'Account Settings'; ?>
                    </h3>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo $language === 'am' ? 'የይለፍ ቃል ይቀይሩ' : 'Change Password'; ?>
                                    </h5>
                                    <p class="card-text">
                                        <?php echo $language === 'am' ? 'የመለያዎን የይለፍ ቃል ለመቀየር እዚህ ጠቅ ያድርጉ።' : 'Click here to change your account password.'; ?>
                                    </p>
                                    <a href="change_password.php" class="btn btn-outline">
                                        <?php echo $language === 'am' ? 'የይለፍ ቃል ይቀይሩ' : 'Change Password'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo $language === 'am' ? 'የመለያ መረጃ' : 'Account Information'; ?>
                                    </h5>
                                    <p class="card-text">
                                        <?php echo $language === 'am' ? 'የመለያዎን ዝርዝሮች ይመልከቱ እና ማደስ ይችላሉ።' : 'View and update your account details.'; ?>
                                    </p>
                                    <a href="profile.php" class="btn btn-outline">
                                        <?php echo $language === 'am' ? 'መግለጫ ይመልከቱ' : 'View Profile'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 