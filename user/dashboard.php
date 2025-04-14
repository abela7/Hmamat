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

// Get current date
$current_date = date('Y-m-d');
$day_of_week = date('N'); // 1-7 (Monday-Sunday)

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

// Get user's activities for today
$completed_activities = array();
$stmt = $conn->prepare("SELECT activity_id, status FROM user_activity_log WHERE user_id = ? AND date_completed = ?");
$stmt->bind_param("is", $user_id, $current_date);
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
<div class="daily-message">
    <p class="mb-0"><?php echo htmlspecialchars($daily_message); ?></p>
</div>
<?php endif; ?>

<!-- Easter Countdown -->
<div class="card mb-4">
    <h3 class="card-title">
        <?php if ($easter_passed): ?>
            Easter Sunday has passed
        <?php else: ?>
            Countdown to Easter Sunday
        <?php endif; ?>
    </h3>
    <div class="p-3">
        <?php if (!$easter_passed): ?>
            <div class="easter-countdown">
                <div class="countdown-info mb-2">
                    <div class="countdown-date">
                        <strong>Easter Date:</strong> <?php echo date('F j, Y', $easter); ?>
                    </div>
                    <div class="countdown-remaining">
                        <strong>Remaining:</strong> 
                        <?php if ($remaining_days > 0): ?>
                            <?php echo $remaining_days; ?> day<?php echo $remaining_days != 1 ? 's' : ''; ?> 
                        <?php endif; ?>
                        <?php echo $remaining_hours; ?> hour<?php echo $remaining_hours != 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%;" 
                         aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo $progress_percentage; ?>%
                    </div>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted">Holy Week Progress</small>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-2">
                <p>Christ is Risen! Easter Sunday was on <?php echo date('F j, Y', $easter); ?></p>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" 
                         aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                        Completed 100%
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Progress Tracker -->
<div class="card mb-4">
    <h3 class="card-title">Your 7-Day Journey</h3>
    <div class="progress-tracker">
        <?php foreach ($progress as $date => $day): ?>
        <div class="progress-day <?php echo $day['is_today'] ? 'active' : ''; ?> <?php echo $day['activities_done'] > 0 ? 'completed' : ''; ?>">
            <div class="progress-day-name"><?php echo $day['day_name']; ?></div>
            <div class="progress-day-points"><?php echo $day['points']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center">
        <p>Your current rank: <strong><?php echo $user_rank; ?></strong> with <strong><?php echo $user_total_points; ?></strong> points</p>
    </div>
</div>

<!-- Today's Activities -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Today's Spiritual Activities</h3>
    </div>
    
    <?php if (empty($activities)): ?>
    <p class="p-3 text-center">No activities available for today.</p>
    <?php else: ?>
    <ul class="activity-list">
        <?php foreach ($activities as $activity): ?>
        <li class="activity-item" id="activity-<?php echo $activity['id']; ?>">
            <div>
                <div class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></div>
                <?php if (!empty($activity['description'])): ?>
                <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center">
                <div class="activity-points me-3"><?php echo $activity['default_points']; ?></div>
                <div class="activity-actions">
                    <?php if (!isset($completed_activities[$activity['id']])): ?>
                    <button class="btn btn-sm btn-success mark-done" data-activity-id="<?php echo $activity['id']; ?>">Done</button>
                    <button class="btn btn-sm btn-secondary mark-not-done" data-activity-id="<?php echo $activity['id']; ?>">Not Done</button>
                    <?php elseif ($completed_activities[$activity['id']] == 'done'): ?>
                    <span class="badge bg-success">Completed</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Not Completed</span>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<!-- Leaderboard Preview -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Top Participants</h3>
    </div>
    
    <?php if (empty($leaderboard)): ?>
    <p class="p-3 text-center">No data available for the leaderboard yet.</p>
    <?php else: ?>
    <div class="p-3">
        <?php for ($i = 0; $i < min(5, count($leaderboard)); $i++): ?>
        <div class="leaderboard-item">
            <div class="leaderboard-rank"><?php echo $i + 1; ?></div>
            <div class="leaderboard-name"><?php echo htmlspecialchars($leaderboard[$i]['baptism_name']); ?></div>
            <div class="leaderboard-points"><?php echo $leaderboard[$i]['total_points']; ?> points</div>
        </div>
        <?php endfor; ?>
        
        <div class="text-center mt-3">
            <a href="leaderboard.php" class="btn btn-outline">View Full Leaderboard</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Not Done Modal -->
<div class="modal" id="notDoneModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Why couldn't you complete this activity?</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="notDoneForm">
                <input type="hidden" id="activity_id" name="activity_id">
                
                <div class="form-group mb-3">
                    <label for="reason_id" class="form-label">Please select a reason:</label>
                    <select class="form-control" id="reason_id" name="reason_id" required>
                        <option value="">Select a reason</option>
                        <?php foreach ($miss_reasons as $id => $reason): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($reason); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-block">Submit</button>
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
                    $(`#activity-\${activityId} .activity-actions`).html('<span class="badge bg-success">Completed</span>');
                    
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
    
    // Open modal for not done
    $(".mark-not-done").click(function() {
        const activityId = $(this).data("activity-id");
        $("#activity_id").val(activityId);
        $("#notDoneModal").css("display", "flex");
    });
    
    // Close modal
    $(".close-modal").click(function() {
        $("#notDoneModal").hide();
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
                status: 'not_done',
                reason_id: reasonId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $("#notDoneModal").hide();
                    
                    // Update UI
                    $(`#activity-\${activityId} .activity-actions`).html('<span class="badge bg-secondary">Not Completed</span>');
                    
                    // Optional: refresh the page
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
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is("#notDoneModal")) {
            $("#notDoneModal").hide();
        }
    });
});
</script>
EOT;

// Include footer
include_once '../includes/user_footer.php';
?> 