<?php
session_start();
require_once('../config/database.php');  // Add database connection
require_once('../includes/auth_check.php');
requireGamemaster();

// Get statistics for the gamemaster's assigned games
$stats_query = $conn->query("
    SELECT 
        g.id as game_id,
        g.name as game_name,
        g.icon as game_icon,
        COUNT(DISTINCT t.id) as total_tournaments,
        COUNT(DISTINCT tm.team_id) as total_teams,
        COUNT(DISTINCT m.id) as total_matches,
        SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_matches,
        AVG(m.team1_score + m.team2_score) as avg_total_score
    FROM games g
    INNER JOIN gamemaster_games gg ON g.id = gg.game_id
    LEFT JOIN tournaments t ON g.id = t.game_id
    LEFT JOIN tournament_teams tm ON t.id = tm.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    WHERE gg.user_id = {$_SESSION['user_id']}
    GROUP BY g.id
");

// Get recent activity - Updated team name references
$activity_query = $conn->query("
    SELECT 
        'match_completed' as type,
        m.id,
        m.match_date,
        t1.team_name as team1_name,
        t2.team_name as team2_name,
        m.team1_score,
        m.team2_score,
        g.name as game_name
    FROM matches m
    INNER JOIN tournaments t ON m.tournament_id = t.id
    INNER JOIN games g ON t.game_id = g.id
    INNER JOIN teams t1 ON m.team1_id = t1.id
    INNER JOIN teams t2 ON m.team2_id = t2.id
    INNER JOIN gamemaster_games gg ON g.id = gg.game_id
    WHERE gg.user_id = {$_SESSION['user_id']}
    AND m.status = 'completed'
    ORDER BY m.match_date DESC
    LIMIT 5
");

if (!$stats_query || !$activity_query) {
    die("Error fetching data: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Game Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/reports.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="reports-header">
            <div class="header-title">
                <h1><i class="bi bi-graph-up"></i> Reports & Analytics</h1>
                <p>View statistics and reports for your assigned games</p>
            </div>
            <div class="header-actions">
                <button class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <button class="btn-export" onclick="exportReport()">
                    <i class="bi bi-download"></i> Export
                </button>
            </div>
        </div>

        <div class="reports-grid">
            <?php if($stats_query->num_rows > 0): ?>
                <?php while($game = $stats_query->fetch_assoc()): ?>
                    <div class="game-stats-card">
                        <div class="game-header">
                            <img src="../images/games/<?php echo $game['game_icon']; ?>" alt="<?php echo $game['game_name']; ?>" class="game-icon">
                            <h3><?php echo $game['game_name']; ?></h3>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $game['total_tournaments']; ?></div>
                                <div class="stat-label">Tournaments</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $game['total_teams']; ?></div>
                                <div class="stat-label">Teams</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $game['total_matches']; ?></div>
                                <div class="stat-label">Matches</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo round(($game['completed_matches'] / max(1, $game['total_matches'])) * 100); ?>%</div>
                                <div class="stat-label">Completion Rate</div>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="chart-<?php echo $game['game_id']; ?>"></canvas>
                        </div>
                        
                        <div class="game-actions">
                            <button class="btn-details" onclick="viewGameDetails(<?php echo $game['game_id']; ?>)">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
            
            <div class="activity-card">
                <h3><i class="bi bi-activity"></i> Recent Activity</h3>
                <?php if($activity_query->num_rows > 0): ?>
                    <div class="activity-list">
                        <?php while($activity = $activity_query->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        Match Completed: <?php echo $activity['team1_name']; ?> vs <?php echo $activity['team2_name']; ?>
                                    </div>
                                    <div class="activity-details">
                                        <span class="game-name"><?php echo $activity['game_name']; ?></span>
                                        <span class="score"><?php echo $activity['team1_score']; ?> - <?php echo $activity['team2_score']; ?></span>
                                        <span class="date"><?php echo date('M d, Y', strtotime($activity['match_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-activity">
                        <i class="bi bi-clock-history"></i>
                        <p>No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/reports.js"></script>
</body>
</html> 