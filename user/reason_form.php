<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set page title
$page_title = "Not Done Reason";

// Get parameters
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Validate the date (ensure it's not in the future)
if (strtotime($date) > strtotime(date('Y-m-d'))) {
    $_SESSION['error_message'] = 'Cannot mark activities for future dates.';
    header("Location: $redirect?date=$date");
    exit;
}

// Validate activity ID
if (empty($activity_id)) {
    $_SESSION['error_message'] = 'Missing activity ID.';
    header("Location: $redirect?date=$date");
    exit;
}

// Get activity name
$activity_name = "";
$stmt = $conn->prepare("SELECT name FROM activities WHERE id = ?");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $activity_name = $row['name'];
} else {
    $_SESSION['error_message'] = 'Activity not found.';
    header("Location: $redirect?date=$date");
    exit;
}
$stmt->close();

// Get user's language preference
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'en';

// Get miss reasons for the dropdown
$miss_reasons = array();
$stmt = $conn->prepare("SELECT id, reason_text FROM activity_miss_reasons ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $miss_reasons[$row['id']] = $row['reason_text'];
}
$stmt->close();

// Include header
include_once '../includes/user_header.php';
?>

<div class="simple-container">
    <div class="reason-form-container">
        <h1 class="main-title"><?php echo $language === 'am' ? 'ባለመጠናቀቁ ምክንያት' : 'Why couldn\'t you complete this activity?'; ?></h1>
        
        <p class="activity-info"><?php echo $language === 'am' ? 'እንቅስቃሴ' : 'Activity'; ?>: <strong><?php echo htmlspecialchars($activity_name); ?></strong></p>
        <p class="date-info"><?php echo $language === 'am' ? 'ቀን' : 'Date'; ?>: <strong><?php echo date('F j, Y', strtotime($date)); ?></strong></p>
        
        <form action="process_activity.php" method="get" class="reason-form">
            <input type="hidden" name="action" value="missed">
            <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">
            <input type="hidden" name="date" value="<?php echo $date; ?>">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            
            <div class="form-group">
                <label for="reason_id" class="form-label"><?php echo $language === 'am' ? 'እባክዎ ምክንያት ይምረጡ' : 'Please select a reason'; ?>:</label>
                <select name="reason_id" id="reason_id" class="form-control" required>
                    <option value=""><?php echo $language === 'am' ? 'ምክንያት ይምረጡ' : 'Select a reason'; ?></option>
                    <?php foreach ($miss_reasons as $id => $reason): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($reason); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <a href="<?php echo $redirect; ?>?date=<?php echo $date; ?>" class="back-btn"><?php echo $language === 'am' ? 'ተመለስ' : 'Cancel'; ?></a>
                <button type="submit" class="submit-btn"><?php echo $language === 'am' ? 'አስገባ' : 'Submit'; ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.simple-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f5f0;
}

.reason-form-container {
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

.activity-info, .date-info {
    margin-bottom: 10px;
    color: #5D4225;
    font-size: 1.1rem;
}

.reason-form {
    margin-top: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #301934;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #301934;
    border-radius: 5px;
    background-color: white;
    color: #301934;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #DAA520;
    box-shadow: 0 0 0 2px rgba(218, 165, 32, 0.2);
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-top: 25px;
}

.back-btn, .submit-btn {
    padding: 12px 20px;
    border-radius: 5px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.back-btn {
    background-color: #e0e0e0;
    color: #333;
    border: none;
}

.submit-btn {
    background-color: #301934;
    color: white;
    border: none;
    flex-grow: 1;
}

.submit-btn:hover {
    background-color: #DAA520;
}

@media (max-width: 576px) {
    .reason-form-container {
        padding: 15px;
    }
    
    .main-title {
        font-size: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .back-btn, .submit-btn {
        width: 100%;
    }
}
</style>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 