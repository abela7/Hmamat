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

<div class="dashboard-container">
    <!-- Left Column: Main Activities -->
    <div class="dashboard-main">
        <!-- Date Navigation - Redesigned to be more professional -->
        <div class="date-controller mb-4">
            <?php 
            // Get current day index
            $dates_array = array_keys($holy_week_dates);
            $current_index = array_search($selected_date, $dates_array);
            $prev_date = ($current_index > 0) ? $dates_array[$current_index - 1] : null;
            $next_date = ($current_index < count($dates_array) - 1) ? $dates_array[$current_index + 1] : null;
            
            // Format the current day and date
            $current_day = $holy_week_dates[$selected_date]['label'];
            $current_display_date = date('F j, Y', strtotime($selected_date));
            ?>
            
            <div class="date-controls">
                <a href="?date=<?php echo $prev_date; ?>" class="date-nav-btn <?php echo $prev_date ? '' : 'disabled'; ?>" <?php echo $prev_date ? '' : 'disabled'; ?>>
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <div class="current-day">
                    <span class="day-name"><?php echo $current_day; ?></span>
                    <span class="day-date"><?php echo $current_display_date; ?></span>
                </div>
                
                <a href="?date=<?php echo $next_date; ?>" class="date-nav-btn <?php echo $next_date ? '' : 'disabled'; ?>" <?php echo $next_date ? '' : 'disabled'; ?>>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <?php if (!$is_today && array_key_exists($current_date, $holy_week_dates)): ?>
            <a href="dashboard.php" class="today-btn">
                <i class="fas fa-calendar-day"></i> 
                <?php echo $language === 'am' ? 'ወደ ዛሬ ተመለስ' : 'Today'; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Spiritual Activities - Redesigned for better mobile appearance -->
        <div class="activities-container">
            <h2 class="section-title">
                <?php echo $language === 'am' ? 'የመንፈሳዊ እንቅስቃሴዎች' : 'Spiritual Activities'; ?>
            </h2>
            
            <?php if (empty($activities)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-tasks"></i></div>
                <p><?php echo $language === 'am' ? 'ለዚህ ቀን የሚገኙ እንቅስቃሴዎች የሉም።' : 'No activities available for this day.'; ?></p>
            </div>
            <?php else: ?>
            <div class="activities-list">
                <?php foreach ($activities as $activity): ?>
                <div class="activity-card" id="activity-<?php echo $activity['id']; ?>">
                    <div class="activity-content">
                        <div class="activity-header">
                            <h3 class="activity-title"><?php echo htmlspecialchars($activity['name']); ?></h3>
                            <div class="activity-points"><?php echo $activity['default_points']; ?></div>
                        </div>
                        
                        <?php if (!empty($activity['description'])): ?>
                        <div class="activity-details">
                            <?php echo htmlspecialchars($activity['description']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="activity-actions">
                        <?php if (!isset($completed_activities[$activity['id']])): ?>
                            <?php if ($is_today): ?>
                                <button class="action-btn success mark-done" data-activity-id="<?php echo $activity['id']; ?>">
                                    <i class="fas fa-check"></i> <?php echo $language === 'am' ? 'ተጠናቋል' : 'Complete'; ?>
                                </button>
                                <button class="action-btn secondary mark-not-done" data-activity-id="<?php echo $activity['id']; ?>">
                                    <i class="fas fa-times"></i> <?php echo $language === 'am' ? 'አልተጠናቀቀም' : 'Not Done'; ?>
                                </button>
                            <?php else: ?>
                                <div class="activity-status empty">
                                    <i class="fas fa-minus-circle"></i> <?php echo $language === 'am' ? 'መዝገብ የለም' : 'No Record'; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($completed_activities[$activity['id']] == 'done'): ?>
                            <div class="activity-status completed">
                                <i class="fas fa-check-circle"></i> <?php echo $language === 'am' ? 'ተጠናቋል' : 'Completed'; ?>
                                <button class="action-btn reset reset-activity" data-activity-id="<?php echo $activity['id']; ?>" data-date="<?php echo $selected_date; ?>">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="activity-status missed">
                                <i class="fas fa-times-circle"></i> <?php echo $language === 'am' ? 'አልተጠናቀቀም' : 'Not Completed'; ?>
                                <button class="action-btn reset reset-activity" data-activity-id="<?php echo $activity['id']; ?>" data-date="<?php echo $selected_date; ?>">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column: Stats and Leaderboard -->
    <div class="dashboard-sidebar">
        <!-- Easter Countdown - Simplified and more elegant -->
        <div class="stats-card">
            <h2 class="section-title">
                <?php echo $language === 'am' ? 'ፋሲካ ቀን' : 'Easter Sunday'; ?>
            </h2>
            
            <?php if (!$easter_passed): ?>
                <div class="countdown-display">
                    <div class="countdown-date">
                        <?php echo date('F j, Y', $easter); ?>
                    </div>
                    
                    <div class="time-remaining">
                        <?php if ($remaining_days > 0): ?>
                            <div class="time-block">
                                <span class="time-value"><?php echo $remaining_days; ?></span>
                                <span class="time-label"><?php echo $language === 'am' ? 'ቀን' : 'days'; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="time-block">
                            <span class="time-value"><?php echo $remaining_hours; ?></span>
                            <span class="time-label"><?php echo $language === 'am' ? 'ሰዓት' : 'hours'; ?></span>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percentage; ?>%;" 
                                aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="progress-label">
                            <?php echo $progress_percentage; ?>% <?php echo $language === 'am' ? 'ተጠናቋል' : 'Complete'; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="countdown-display completed">
                    <div class="celebration-message">
                        <i class="fas fa-church"></i>
                        <div><?php echo $language === 'am' ? 'ክርስቶስ ተነሣ !' : 'Christ is Risen!'; ?></div>
                        <div class="sub-text"><?php echo date('F j, Y', $easter); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Participants -->
        <div class="stats-card">
            <h2 class="section-title">
                <?php echo $language === 'am' ? 'ከፍተኛ ተሳታፊዎች' : 'Top Participants'; ?>
            </h2>
            
            <?php if (empty($leaderboard)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-trophy"></i></div>
                <p><?php echo $language === 'am' ? 'እስካሁን ምንም መረጃ የለም።' : 'No data available yet.'; ?></p>
            </div>
            <?php else: ?>
            <div class="leaderboard">
                <div class="your-rank">
                    <div class="rank-label"><?php echo $language === 'am' ? 'የእርስዎ ደረጃ' : 'Your Rank'; ?></div>
                    <div class="rank-value">#<?php echo $user_rank; ?></div>
                    <div class="points-value"><?php echo $user_total_points; ?> <?php echo $language === 'am' ? 'ነጥብ' : 'points'; ?></div>
                </div>
                
                <div class="top-users">
                    <?php for ($i = 0; $i < min(5, count($leaderboard)); $i++): ?>
                    <div class="leaderboard-row <?php echo ($leaderboard[$i]['baptism_name'] === $baptism_name) ? 'is-you' : ''; ?>">
                        <div class="rank"><?php echo $i + 1; ?></div>
                        <div class="name"><?php echo htmlspecialchars($leaderboard[$i]['baptism_name']); ?></div>
                        <div class="points"><?php echo $leaderboard[$i]['total_points']; ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <a href="leaderboard.php" class="view-all-btn">
                    <?php echo $language === 'am' ? 'ሙሉ ደረጃ ሰሌዳ ይመልከቱ' : 'View Complete Leaderboard'; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Not Done Modal - More stylish modal -->
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
                    <button type="submit" class="btn btn-primary"><?php echo $language === 'am' ? 'አስገባ' : 'Submit'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

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
        
        $.ajax({
            url: "submit_activity.php",
            method: "POST",
            data: {
                activity_id: activityId,
                status: 'missed',
                reason_id: reasonId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $("#notDoneModal").css("display", "none");
                    
                    // Update UI
                    $(`#activity-\${activityId} .activity-actions`).html('<span class="badge bg-secondary">Not Completed</span> <button class="btn btn-sm btn-danger reset-activity" data-activity-id="' + activityId + '" data-date="<?php echo $selected_date; ?>"><i class="fas fa-undo"></i> Reset</button>');
                    
                    // Optional: Update points display
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
});
</script>
EOT;

// Include footer
include_once '../includes/user_footer.php';
?> 