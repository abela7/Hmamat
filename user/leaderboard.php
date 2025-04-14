<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set page title
$page_title = "Leaderboard";

// Get user information
$user_id = $_SESSION['user_id'];
$baptism_name = $_SESSION['baptism_name'];

// Get leaderboard data
$leaderboard = array();
$stmt = $conn->prepare("SELECT u.baptism_name, SUM(ual.points_earned) as total_points
                       FROM users u
                       JOIN user_activity_log ual ON u.id = ual.user_id
                       WHERE ual.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       AND ual.status = 'done'
                       GROUP BY u.id
                       ORDER BY total_points DESC
                       LIMIT 20");
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
                           SELECT user_id, SUM(points_earned) as total_points,
                           RANK() OVER (ORDER BY SUM(points_earned) DESC) as rank
                           FROM user_activity_log
                           WHERE date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                           AND status = 'done'
                           GROUP BY user_id
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Leaderboard</h2>
    </div>
    
    <div class="card-body">
        <div class="alert alert-primary">
            Your rank: <strong><?php echo $user_rank; ?></strong> with <strong><?php echo $user_total_points; ?></strong> points
        </div>
        
        <?php if (empty($leaderboard)): ?>
        <p class="text-center">No data available for the leaderboard yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Baptism Name</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $index => $user): ?>
                    <tr <?php echo ($user['baptism_name'] === $baptism_name) ? 'class="table-primary"' : ''; ?>>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($user['baptism_name']); ?></td>
                        <td><?php echo $user['total_points']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-center mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 