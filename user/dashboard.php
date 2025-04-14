<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set page title
$page_title = "Dashboard";

// Get user information
$user_id = $_SESSION['user_id'];
$baptism_name = $_SESSION['baptism_name'];

// Get user's language preference
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'en';

// Calculate Easter date and remaining time
function getEasterDate($year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $a = $year % 19;
    $b = floor($year / 100);
    $c = $year % 100;
    $d = floor($b / 4);
    $e = $b % 4;
    $f = floor(($b + 8) / 25);
    $g = floor(($b - $f + 1) / 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = floor($c / 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = floor(($a + 11 * $h + 22 * $l) / 451);
    $month = floor(($h + $l - 7 * $m + 114) / 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    
    return mktime(0, 0, 0, $month, $day, $year);
}

// Calculate Easter Sunday and time remaining
$easter = getEasterDate();
$easter_date = date('Y-m-d', $easter);
$current_timestamp = time();
$remaining_seconds = $easter - $current_timestamp;

// Calculate full days and remaining hours
$remaining_days = floor($remaining_seconds / 86400);
$remaining_hours = floor(($remaining_seconds % 86400) / 3600);

// Calculate progress percentage (assuming Holy Week is 7 days)
$holy_week_start = $easter - (7 * 86400);
$total_holy_week_seconds = 7 * 86400;
$elapsed_seconds = $current_timestamp - $holy_week_start;
$progress_percentage = 0;

if ($current_timestamp >= $holy_week_start) {
    $progress_percentage = min(100, round(($elapsed_seconds / $total_holy_week_seconds) * 100));
}

// Has Easter already passed this year?
$easter_passed = $current_timestamp > $easter;

// Calculate Holy Week dates
$easter_date_obj = new DateTime(date('Y-m-d', $easter));
$holy_week_start_obj = clone $easter_date_obj;
$holy_week_start_obj->modify('-6 days'); // Monday before Easter

// Create array of Holy Week dates
$holy_week_dates = [];
$holy_week_labels = [
    'Monday' => $language === 'am' ? 'ሰኞ' : 'Monday',
    'Tuesday' => $language === 'am' ? 'ማክሰኞ' : 'Tuesday',
    'Wednesday' => $language === 'am' ? 'ረቡዕ' : 'Wednesday',
    'Thursday' => $language === 'am' ? 'ሐሙስ' : 'Thursday',
    'Friday' => $language === 'am' ? 'አርብ' : 'Friday',
    'Saturday' => $language === 'am' ? 'ቅዳሜ' : 'Saturday',
    'Sunday' => $language === 'am' ? 'እሁድ' : 'Sunday'
];

for ($i = 0; $i < 7; $i++) {
    $day_obj = clone $holy_week_start_obj;
    $day_obj->modify("+$i days");
    $day_date = $day_obj->format('Y-m-d');
    $day_name = $day_obj->format('l');
    $holy_week_dates[$day_date] = [
        'date' => $day_date,
        'day_name' => $day_name,
        'label' => $holy_week_labels[$day_name],
        'date_formatted' => $day_obj->format('d/m')
    ];
}

// Handle date selection
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate selected date is within Holy Week
if (!array_key_exists($selected_date, $holy_week_dates)) {
    // If selected date is not in Holy Week, default to current date or closest Holy Week date
    $current_date = date('Y-m-d');
    if (array_key_exists($current_date, $holy_week_dates)) {
        $selected_date = $current_date;
    } else {
        // Find closest date in Holy Week
        $current_timestamp = time();
        $closest_date = null;
        $closest_diff = PHP_INT_MAX;
        
        foreach ($holy_week_dates as $date => $info) {
            $date_timestamp = strtotime($date);
            $diff = abs($date_timestamp - $current_timestamp);
            
            if ($diff < $closest_diff) {
                $closest_diff = $diff;
                $closest_date = $date;
            }
        }
        
        $selected_date = $closest_date;
    }
}

// Get current date and selected day info
$current_date = date('Y-m-d');
$is_today = ($selected_date === $current_date);
$selected_day_name = $holy_week_dates[$selected_date]['day_name'];
$day_of_week = date('N', strtotime($selected_date)); // 1-7 (Monday-Sunday)

// Get daily message
$daily_message = "";
$stmt = $conn->prepare("SELECT message_text FROM daily_messages WHERE day_of_week = ? OR day_of_week IS NULL ORDER BY day_of_week DESC LIMIT 1");
$stmt->bind_param("i", $day_of_week);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $daily_message = $row['message_text'];
}
$stmt->close();

// Get user's activities for selected date
$completed_activities = array();
$stmt = $conn->prepare("SELECT activity_id, status FROM user_activity_log WHERE user_id = ? AND date_completed = ?");
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $completed_activities[$row['activity_id']] = $row['status'];
}
$stmt->close();

// Get all available activities
$activities = array();
$sql = "SELECT id, name, description, default_points, day_of_week FROM activities";
$sql .= " WHERE day_of_week IS NULL OR day_of_week = ?";
$sql .= " ORDER BY day_of_week DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $day_of_week);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
$stmt->close();

// Get miss reasons for the dropdown
$miss_reasons = array();
$stmt = $conn->prepare("SELECT id, reason_text FROM activity_miss_reasons ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $miss_reasons[$row['id']] = $row['reason_text'];
}
$stmt->close();

// Get user's progress for the last 7 days
$progress = array();
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $progress[$day] = array(
        'date' => $day,
        'day_name' => $day_name,
        'is_today' => ($i == 0),
        'points' => 0,
        'activities_done' => 0
    );
}

$stmt = $conn->prepare("SELECT date_completed, SUM(points_earned) as total_points, COUNT(*) as activities_count 
                       FROM user_activity_log 
                       WHERE user_id = ? AND date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       AND status = 'done'
                       GROUP BY date_completed");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($progress[$row['date_completed']])) {
        $progress[$row['date_completed']]['points'] = $row['total_points'];
        $progress[$row['date_completed']]['activities_done'] = $row['activities_count'];
    }
}
$stmt->close();

// Get leaderboard data
$leaderboard = array();
$stmt = $conn->prepare("SELECT u.baptism_name, SUM(ual.points_earned) as total_points
                       FROM users u
                       JOIN user_activity_log ual ON u.id = ual.user_id
                       LEFT JOIN user_preferences up ON u.id = up.user_id
                       WHERE ual.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       AND ual.status = 'done'
                       AND (up.show_on_leaderboard = 1 OR up.show_on_leaderboard IS NULL)
                       GROUP BY u.id
                       ORDER BY total_points DESC
                       LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = $row;
}
$stmt->close();

// Get user's rank and total points
$user_rank = 0;
$user_total_points = 0;

$stmt = $conn->prepare("SELECT user_id, total_points, rank
                       FROM (
                           SELECT ual.user_id, SUM(ual.points_earned) as total_points,
                           RANK() OVER (ORDER BY SUM(ual.points_earned) DESC) as rank
                           FROM user_activity_log ual
                           JOIN users u ON ual.user_id = u.id
                           LEFT JOIN user_preferences up ON u.id = up.user_id
                           WHERE ual.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                           AND ual.status = 'done'
                           AND (up.show_on_leaderboard = 1 OR up.show_on_leaderboard IS NULL)
                           GROUP BY ual.user_id
                       ) as rankings
                       WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_rank = $row['rank'];
    $user_total_points = $row['total_points'];
} else {
    $user_rank = "-";
    $user_total_points = 0;
}
$stmt->close();

// Include header
include_once '../includes/user_header.php';
?>

<!-- Daily Message -->
<?php if (!empty($daily_message)): ?>
<div class="daily-message mb-4">
    <p class="mb-0"><?php echo htmlspecialchars($daily_message); ?></p>
</div>
<?php endif; ?>

<div class="simple-container">
    <!-- Date Navigation -->
    <div class="simple-date-nav">
        <?php
        // Get prev/next dates from the holy_week_dates array
        $dates_array = array_keys($holy_week_dates);
        $current_index = array_search($selected_date, $dates_array);
        $prev_date = ($current_index > 0) ? $dates_array[$current_index - 1] : null;
        $next_date = ($current_index < count($dates_array) - 1) ? $dates_array[$current_index + 1] : null;
        ?>
        
        <a href="<?php echo $prev_date ? '?date='.$prev_date : 'javascript:void(0)'; ?>" class="nav-arrow <?php echo $prev_date ? '' : 'disabled'; ?>">
            <i class="fas fa-chevron-left"></i>
        </a>
        
        <h2 class="current-date">
            <?php echo $holy_week_dates[$selected_date]['label']; ?>, <?php echo date('F j, Y', strtotime($selected_date)); ?>
        </h2>
        
        <a href="<?php echo $next_date ? '?date='.$next_date : 'javascript:void(0)'; ?>" class="nav-arrow <?php echo $next_date ? '' : 'disabled'; ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <!-- Spiritual Activities Title -->
    <div class="activity-title-section">
        <h1 class="main-title">Spiritual Activities</h1>
        <?php if ($language === 'am'): ?>
        <h2 class="amharic-title">መጽሐፍ ቅዱስ ማንበብ</h2>
        <?php endif; ?>
    </div>
    
    <!-- Activities List -->
    <div class="activities-simple-list">
        <?php foreach ($activities as $activity): ?>
            <div class="activity-simple-item" id="activity-<?php echo $activity['id']; ?>">
                <div class="activity-info">
                    <div class="activity-details">
                        <h3 class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></h3>
                        <?php if (!empty($activity['description'])): ?>
                            <p class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="activity-actions">
                    <?php if (isset($completed_activities[$activity['id']]) && $completed_activities[$activity['id']] == 'done'): ?>
                        <div class="status-badge completed">
                            <i class="fas fa-check-circle"></i> <?php echo $language === 'am' ? 'ተጠናቋል' : 'Complete'; ?>
                        </div>
                        <?php if (strtotime($selected_date) <= strtotime(date('Y-m-d'))): ?>
                        <button class="reset-btn" onclick="resetActivity(<?php echo $activity['id']; ?>, '<?php echo $selected_date; ?>')">
                            <i class="fas fa-undo"></i> <?php echo $language === 'am' ? 'ዳግም አስጀምር' : 'Reset'; ?>
                        </button>
                        <?php endif; ?>
                    <?php elseif (isset($completed_activities[$activity['id']]) && $completed_activities[$activity['id']] == 'missed'): ?>
                        <div class="status-badge missed">
                            <i class="fas fa-times-circle"></i> <?php echo $language === 'am' ? 'አልተጠናቀቀም' : 'Not Done'; ?>
                        </div>
                        <?php if (strtotime($selected_date) <= strtotime(date('Y-m-d'))): ?>
                        <button class="reset-btn" onclick="resetActivity(<?php echo $activity['id']; ?>, '<?php echo $selected_date; ?>')">
                            <i class="fas fa-undo"></i> <?php echo $language === 'am' ? 'ዳግም አስጀምር' : 'Reset'; ?>
                        </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (strtotime($selected_date) <= strtotime(date('Y-m-d'))): ?>
                            <button class="action-btn success" onclick="markComplete(<?php echo $activity['id']; ?>)">
                                <i class="fas fa-check"></i> <?php echo $language === 'am' ? 'ተጠናቋል' : 'Complete'; ?>
                            </button>
                            <button class="action-btn secondary" onclick="markMissed(<?php echo $activity['id']; ?>)">
                                <i class="fas fa-times"></i> <?php echo $language === 'am' ? 'አልተጠናቀቀም' : 'Not Done'; ?>
                            </button>
                        <?php else: ?>
                            <div class="future-message">
                                <?php echo $language === 'am' ? 'ምክንያት የወደፊት ቀን ነው' : 'Future date - cannot mark yet'; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Not Done Modal -->
<div class="modal" id="notDoneModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $language === 'am' ? 'ይህን እንቅስቃሴ ማጠናቀቅ ያልቻሉበት ምክንያት ምንድን ነው?' : 'Why couldn\'t you complete this activity?'; ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="notDoneForm">
                <input type="hidden" id="activity_id" name="activity_id">
                
                <div class="form-group mb-3">
                    <label for="reason_id" class="form-label"><?php echo $language === 'am' ? 'እባክዎ ምክንያት ይምረጡ:' : 'Please select a reason:'; ?></label>
                    <select class="form-control" id="reason_id" name="reason_id" required>
                        <option value=""><?php echo $language === 'am' ? 'ምክንያት ይምረጡ' : 'Select a reason'; ?></option>
                        <?php foreach ($miss_reasons as $id => $reason): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($reason); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn"><?php echo $language === 'am' ? 'አስገባ' : 'Submit'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.simple-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f5f0;
}

.simple-date-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    background: #F1ECE2;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.current-date {
    font-size: 1.2rem;
    margin: 0;
    color: #301934;
    font-weight: 600;
}

.nav-arrow {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 50%;
    color: #301934;
    text-decoration: none;
}

.nav-arrow.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.activity-title-section {
    margin-bottom: 30px;
}

.main-title {
    font-size: 2.2rem;
    color: #301934;
    margin-bottom: 5px;
    font-weight: 700;
}

.amharic-title {
    font-size: 1.6rem;
    color: #5D4225;
    margin-top: 5px;
    font-weight: 500;
}

.activities-simple-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-simple-item {
    background: #F1ECE2;
    border-radius: 10px;
    padding: 20px;
}

.activity-info {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.activity-details {
    flex: 1;
}

.activity-name {
    font-size: 1.3rem;
    font-weight: 600;
    color: #301934;
    margin: 0 0 10px;
}

.activity-description {
    margin: 0;
    color: #5D4225;
    font-size: 1rem;
}

.activity-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 15px;
}

.action-btn {
    padding: 12px 24px;
    border-radius: 5px;
    border: none;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}

.action-btn.success {
    background-color: #316B3A;
    color: white;
}

.action-btn.secondary {
    background-color: #301934;
    color: white;
}

.status-badge {
    padding: 10px 15px;
    border-radius: 5px;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}

.status-badge.completed {
    background-color: #316B3A;
    color: white;
}

.status-badge.missed {
    background-color: #301934;
    color: white;
}

.reset-btn {
    background: none;
    border: none;
    color: #301934;
    font-weight: 600;
    cursor: pointer;
    padding: 5px 10px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s ease;
}

.reset-btn:hover {
    background-color: rgba(0,0,0,0.05);
    border-radius: 5px;
}

.future-message {
    color: #6c757d;
    font-style: italic;
    text-align: center;
    width: 100%;
}

@media (max-width: 576px) {
    .activity-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .main-title {
        font-size: 1.8rem;
    }
    
    .amharic-title {
        font-size: 1.4rem;
    }
    
    .activity-name {
        font-size: 1.2rem;
    }
    
    .action-btn, .status-badge {
        font-size: 1rem;
        padding: 10px;
    }
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #F1ECE2;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.modal-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #301934;
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #301934;
    transition: color 0.2s ease;
}

.close-modal:hover {
    color: #DAA520;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #301934;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
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
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.btn {
    background-color: #301934;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn:hover {
    background-color: #DAA520;
    transform: translateY(-2px);
}
</style>

<script>
function markComplete(activityId) {
    // Store current scroll position
    const scrollPosition = window.pageYOffset;
    
    // Submit request directly
    $.post("ajax/update_activity.php", {
        activity_id: activityId,
        status: "done",
        date: "<?php echo $selected_date; ?>"
    })
    .done(function(response) {
        if (response.success) {
            window.location.href = "dashboard.php?date=<?php echo $selected_date; ?>&scroll=" + scrollPosition;
        } else {
            alert("Error: " + response.message);
            console.log(response);
        }
    })
    .fail(function(xhr, status, error) {
        console.error("AJAX Error:", status, error);
        console.log(xhr.responseText);
        alert("An error occurred. Please try again.");
    });
}

function markMissed(activityId) {
    $("#activity_id").val(activityId);
    $("#notDoneModal").css("display", "flex");
}

function resetActivity(activityId, date) {
    // Store current scroll position
    const scrollPosition = window.pageYOffset;
    
    // Submit request directly
    $.post("ajax/reset_activity.php", {
        activity_id: activityId,
        date: date
    })
    .done(function(response) {
        if (response.success) {
            window.location.href = "dashboard.php?date=<?php echo $selected_date; ?>&scroll=" + scrollPosition;
        } else {
            alert("Error: " + response.message);
            console.log(response);
        }
    })
    .fail(function(xhr, status, error) {
        console.error("AJAX Error:", status, error);
        console.log(xhr.responseText);
        alert("An error occurred. Please try again.");
    });
}

// Close modal
$(".close-modal").click(function() {
    $("#notDoneModal").css("display", "none");
});

// Directly handle the Not Done form submission with a cleaner approach
$("#notDoneForm").submit(function(e) {
    e.preventDefault();
    
    const activityId = $("#activity_id").val();
    const reasonId = $("#reason_id").val();
    
    if (!reasonId) {
        alert("Please select a reason.");
        return;
    }
    
    // Close modal first
    $("#notDoneModal").css("display", "none");
    
    // Store current scroll position
    const scrollPosition = window.pageYOffset;
    
    // Submit the form directly
    $.post("ajax/update_activity.php", {
        activity_id: activityId,
        status: "missed",
        reason_id: reasonId,
        date: "<?php echo $selected_date; ?>"
    })
    .done(function(response) {
        if (response.success) {
            window.location.href = "dashboard.php?date=<?php echo $selected_date; ?>&scroll=" + scrollPosition;
        } else {
            alert("Error: " + response.message);
            console.log(response);
        }
    })
    .fail(function(xhr, status, error) {
        console.error("AJAX Error:", status, error);
        console.log(xhr.responseText);
        alert("An error occurred. Please try again.");
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('notDoneModal');
    if (event.target === modal) {
        $("#notDoneModal").css("display", "none");
    }
}

// Restore scroll position after page reload
$(document).ready(function() {
    if (window.location.href.includes('scroll=')) {
        const scrollParam = window.location.href.split('scroll=')[1];
        const scrollPosition = parseInt(scrollParam.split('&')[0]);
        
        if (!isNaN(scrollPosition)) {
            window.scrollTo(0, scrollPosition);
        }
    }
});
</script>

<?php
// Page-specific scripts
$page_scripts = <<<EOT
<script>
$(document).ready(function() {
    // Check if it's a new day and reload page
    function checkForNewDay() {
        const currentDate = new Date().toISOString().split('T')[0];
        const storedDate = localStorage.getItem('lastVisitDate');
        
        if (storedDate && storedDate !== currentDate) {
            // It's a new day, reload to the current date
            window.location.href = 'dashboard.php';
        }
        
        // Store current date
        localStorage.setItem('lastVisitDate', currentDate);
    }
    
    // Run check for new day
    checkForNewDay();
    
    // Set interval to check every minute if it's midnight
    setInterval(function() {
        const now = new Date();
        if (now.getHours() === 0 && now.getMinutes() === 0) {
            // It's midnight, reload the page
            window.location.href = 'dashboard.php';
        }
    }, 60000); // Check every minute
    
    // Mark activity as done
    $(".mark-done").click(function() {
        const activityId = $(this).data("activity-id");
        
        $.ajax({
            url: "submit_activity.php",
            method: "POST",
            data: {
                activity_id: activityId,
                status: 'done'
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $(`#activity-\${activityId} .activity-actions`).html('<span class="badge bg-success">Completed</span> <button class="btn btn-sm btn-danger reset-activity" data-activity-id="' + activityId + '" data-date="<?php echo $selected_date; ?>"><i class="fas fa-undo"></i> Reset</button>');
                    
                    // Optional: Update points display
                    // You might want to refresh the page to show updated progress
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("An error occurred. Please try again.");
            }
        });
    });
    
    // Reset an activity
    $(document).on("click", ".reset-activity", function() {
        const activityId = $(this).data("activity-id");
        const date = $(this).data("date");
        
        if (confirm("Are you sure you want to reset this activity? This will remove your record for this activity.")) {
            $.ajax({
                url: "ajax/reset_activity.php",
                method: "POST",
                data: {
                    activity_id: activityId,
                    date: date
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        if (date === "<?php echo date('Y-m-d'); ?>") {
                            // For today, show action buttons
                            $(`#activity-\${activityId} .activity-actions`).html(
                                '<button class="btn btn-sm btn-success mark-done" data-activity-id="' + activityId + '">Done</button> ' +
                                '<button class="btn btn-sm btn-secondary mark-not-done" data-activity-id="' + activityId + '">Not Done</button>'
                            );
                        } else {
                            // For past days, just show "No Record"
                            $(`#activity-\${activityId} .activity-actions`).html(
                                '<span class="badge bg-light text-dark">No Record</span>'
                            );
                        }
                        
                        // Refresh the page after a delay to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Open modal for not done
    $(".mark-not-done").click(function() {
        const activityId = $(this).data("activity-id");
        $("#activity_id").val(activityId);
        $("#notDoneModal").css("display", "flex");
    });
    
    // Close modal
    $(".close-modal").click(function() {
        $("#notDoneModal").css("display", "none");
    });
    
    // Submit not done form
    $("#notDoneForm").submit(function(e) {
        e.preventDefault();
        
        const activityId = $("#activity_id").val();
        const reasonId = $("#reason_id").val();
        
        if (!reasonId) {
            alert("Please select a reason.");
            return;
        }
        
        $.ajax({
            url: "ajax/update_activity.php",
            method: "POST",
            data: {
                activity_id: activityId,
                status: 'missed',
                reason_id: reasonId,
                date: '<?php echo $selected_date; ?>'
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#notDoneModal").css("display", "none");
                    
                    // Reload page and maintain scroll position
                    const scrollPosition = window.pageYOffset;
                    window.location.href = 'dashboard.php?date=<?php echo $selected_date; ?>&scroll=' + scrollPosition;
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log(xhr.responseText);
                alert("An error occurred. Please try again.");
            }
        });
    });
});
</script>
EOT;

// Include footer
include_once '../includes/user_footer.php';
?> 