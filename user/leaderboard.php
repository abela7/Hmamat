<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo"><?php echo APP_NAME; ?></div>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="leaderboard.php" class="nav-link active">Leaderboard</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
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
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - <?php echo APP_FULL_NAME; ?></p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 