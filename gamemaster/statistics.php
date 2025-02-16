<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get statistics for the gamemaster
try {
    // Total tournaments managed
    $tournaments_query = $conn->query("
        SELECT COUNT(DISTINCT t.id) as total_tournaments
        FROM tournaments t
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN gamemaster_games gg ON g.id = gg.game_id
        WHERE gg.user_id = " . (int)$_SESSION['user_id']
    );
    $tournaments_stats = $tournaments_query->fetch_assoc();

    // Total matches overseen - Fixed ambiguous status column
    $matches_query = $conn->query("
        SELECT 
            COUNT(*) as total_matches,
            SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_matches,
            SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) as pending_matches
        FROM matches m
        INNER JOIN tournaments t ON m.tournament_id = t.id
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN gamemaster_games gg ON g.id = gg.game_id
        WHERE gg.user_id = " . (int)$_SESSION['user_id']
    );
    $matches_stats = $matches_query->fetch_assoc();

    // Games managed
    $games_query = $conn->query("
        SELECT 
            g.*, 
            (SELECT COUNT(*) FROM tournaments t WHERE t.game_id = g.id) as tournament_count,
            (SELECT COUNT(*) FROM tournaments t 
             INNER JOIN matches m ON t.id = m.tournament_id 
             WHERE t.game_id = g.id) as matches_count,
            (SELECT COUNT(*) FROM tournaments t 
             INNER JOIN matches m ON t.id = m.tournament_id 
             WHERE t.game_id = g.id AND m.status = 'completed') as completed_matches_count
        FROM games g
        WHERE g.name IN ('Tekken 8', 'Call of Duty Mobile', 'Mobile Legends')
        ORDER BY FIELD(g.name, 'Tekken 8', 'Call of Duty Mobile', 'Mobile Legends')"
    );

    // Add query for team leaderboard
    $leaderboard_query = $conn->query("
        SELECT 
            t.id,
            t.team_name,
            t.team_logo,
            COUNT(DISTINCT tb.tournament_id) as tournaments_played,
            COUNT(CASE WHEN tb.winner_id = t.id THEN 1 END) as matches_won,
            COUNT(CASE WHEN tb.team1_id = t.id OR tb.team2_id = t.id THEN 1 END) as total_matches,
            SUM(CASE 
                WHEN tb.winner_id = t.id THEN 3  -- 3 points for a win
                WHEN tb.status = 'completed' AND tb.winner_id != t.id THEN 0  -- 0 points for a loss
                ELSE 0 
            END) as total_points
        FROM teams t
        INNER JOIN tournament_teams tt ON t.id = tt.team_id
        INNER JOIN tournaments tour ON tt.tournament_id = tour.id
        INNER JOIN tournament_brackets tb ON tour.id = tb.tournament_id 
            AND (tb.team1_id = t.id OR tb.team2_id = t.id)
        INNER JOIN gamemaster_games gg ON tour.game_id = gg.game_id
        WHERE gg.user_id = " . (int)$_SESSION['user_id'] . "
        GROUP BY t.id
        ORDER BY total_points DESC, matches_won DESC
        LIMIT 10");

} catch (Exception $e) {
    die("Error fetching statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/statistics.css" rel="stylesheet">
    <link href="css/navigation.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <div class="header-title">
                <h1><i class="bi bi-graph-up"></i> Statistics</h1>
                <p>Overview of your tournament management performance</p>
            </div>
        </div>

        <div class="stats-grid">
            <!-- Overview Cards -->
            <div class="stats-card total-tournaments">
                <div class="card-icon">
                    <i class="bi bi-trophy"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $tournaments_stats['total_tournaments']; ?></h3>
                    <p>Total Tournaments</p>
                </div>
            </div>

            <div class="stats-card total-matches">
                <div class="card-icon">
                    <i class="bi bi-controller"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $matches_stats['total_matches']; ?></h3>
                    <p>Total Matches</p>
                </div>
            </div>

            <div class="stats-card completed-matches">
                <div class="card-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $matches_stats['completed_matches']; ?></h3>
                    <p>Completed Matches</p>
                </div>
            </div>

            <div class="stats-card pending-matches">
                <div class="card-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $matches_stats['pending_matches']; ?></h3>
                    <p>Pending Matches</p>
                </div>
            </div>
        </div>

        <!-- Games Performance -->
        <div class="stats-section">
            <h2>Games Performance</h2>
            <div class="games-stats">
                <?php if($games_query->num_rows > 0): ?>
                    <?php 
                    $first = true;
                    while($game = $games_query->fetch_assoc()): 
                        if(!$first): ?>
                            <div class="tournament-divider"></div>
                        <?php endif; ?>
                        <div class="tournament-section <?php echo strtolower(str_replace(' ', '-', $game['name'])); ?>">
                            <div class="tournament-header">
                                <div class="game-info">
                                    <img src="../images/games/<?php echo $game['icon']; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-icon">
                                    <h4><?php echo htmlspecialchars($game['name']); ?></h4>
                                </div>
                                <span class="tournament-status status-completed">Completed</span>
                            </div>
                            <div class="game-metrics">
                                <div class="metric tournaments">
                                    <span class="value"><?php echo $game['tournament_count']; ?></span>
                                    <span class="label">Total Tournaments</span>
                                </div>
                                <div class="metric matches">
                                    <span class="value"><?php echo $game['matches_count']; ?></span>
                                    <span class="label">Total Matches</span>
                                </div>
                                <div class="metric completed">
                                    <span class="value"><?php echo $game['completed_matches_count']; ?></span>
                                    <span class="label">Completed Matches</span>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $first = false;
                    endwhile; ?>
                <?php else: ?>
                    <div class="no-stats">
                        <i class="bi bi-bar-chart"></i>
                        <h3>No Games Data</h3>
                        <p>Start managing games to see statistics</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update the leaderboard section -->
        <div class="stats-section">
            <h2>Game Leaderboards</h2>
            
            <?php
            // Fetch specific games (Tekken 8, CODM, Mobile Legends)
            $games_query = $conn->query("
                SELECT g.* 
                FROM games g
                WHERE g.name IN ('Tekken 8', 'Call of Duty Mobile', 'Mobile Legends')
                ORDER BY FIELD(g.name, 'Tekken 8', 'Call of Duty Mobile', 'Mobile Legends')"
            );

            while($game = $games_query->fetch_assoc()):
                // Fetch leaderboard for each game
                $leaderboard_query = $conn->prepare("
                    SELECT 
                        t.id,
                        t.team_name,
                        t.team_logo,
                        tour.name as tournament_name,
                        COUNT(DISTINCT tb.tournament_id) as tournaments_played,
                        COUNT(CASE WHEN tb.winner_id = t.id THEN 1 END) as matches_won,
                        COUNT(CASE WHEN tb.team1_id = t.id OR tb.team2_id = t.id THEN 1 END) as total_matches,
                        SUM(CASE 
                            WHEN tb.team1_id = t.id THEN tb.team1_score
                            WHEN tb.team2_id = t.id THEN tb.team2_score
                            ELSE 0 
                        END) as total_score,
                        SUM(CASE 
                            WHEN tb.winner_id = t.id THEN 3  -- 3 points for a win
                            WHEN tb.status = 'completed' AND tb.winner_id != t.id THEN 0  -- 0 points for a loss
                            ELSE 0 
                        END) as total_points
                    FROM teams t
                    INNER JOIN tournament_teams tt ON t.id = tt.team_id
                    INNER JOIN tournaments tour ON tt.tournament_id = tour.id AND tour.game_id = ?
                    INNER JOIN tournament_single_elimination tb ON tour.id = tb.tournament_id 
                        AND (tb.team1_id = t.id OR tb.team2_id = t.id)
                    WHERE tb.status = 'completed'
                    GROUP BY t.id
                    ORDER BY total_points DESC, matches_won DESC, total_score DESC
                    LIMIT 5"
                );
                
                $leaderboard_query->bind_param("i", $game['id']);
                $leaderboard_query->execute();
                $leaderboard = $leaderboard_query->get_result();
            ?>
            
            <div class="game-leaderboard <?php echo strtolower(str_replace(' ', '-', $game['name'])); ?>">
                <div class="leaderboard-header">
                    <div class="game-info">
                        <img src="../images/games/<?php echo $game['icon']; ?>" 
                             alt="<?php echo htmlspecialchars($game['name']); ?>" 
                             class="game-icon">
                        <h3><?php echo htmlspecialchars($game['name']); ?> Leaderboard</h3>
                    </div>
                    <span class="game-type">
                        <?php 
                        switch($game['name']) {
                            case 'Tekken 8':
                                echo '1v1';
                                break;
                            case 'Call of Duty Mobile':
                                echo '5v5';
                                break;
                            case 'Mobile Legends':
                                echo '5v5';
                                break;
                        }
                        ?>
                    </span>
                </div>

                <div class="leaderboard-table-wrapper">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Team</th>
                                <th>Tournament</th>
                                <th>Matches</th>
                                <th>Score</th>
                                <th>Wins</th>
                                <th>Win Rate</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            while($team = $leaderboard->fetch_assoc()): 
                                $win_rate = $team['total_matches'] > 0 
                                    ? round(($team['matches_won'] / $team['total_matches']) * 100, 1) 
                                    : 0;
                            ?>
                                <tr class="<?php echo $rank <= 3 ? 'top-' . $rank : ''; ?>">
                                    <td class="rank">
                                        <?php if($rank <= 3): ?>
                                            <i class="bi bi-trophy-fill rank-<?php echo $rank; ?>"></i>
                                        <?php else: ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="team-info">
                                        <img src="../images/team-logos/<?php echo $team['team_logo'] ?: 'default-team-logo.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($team['team_name']); ?>" 
                                             class="team-logo">
                                        <span><?php echo htmlspecialchars($team['team_name']); ?></span>
                                    </td>
                                    <td class="tournament-name"><?php echo htmlspecialchars($team['tournament_name']); ?></td>
                                    <td><?php echo $team['total_matches']; ?></td>
                                    <td class="score"><?php echo $team['total_score']; ?></td>
                                    <td><?php echo $team['matches_won']; ?></td>
                                    <td>
                                        <div class="win-rate">
                                            <div class="progress" style="width: <?php echo $win_rate; ?>%"></div>
                                            <span><?php echo $win_rate; ?>%</span>
                                        </div>
                                    </td>
                                    <td class="points"><?php echo $team['total_points']; ?></td>
                                </tr>
                            <?php 
                                $rank++;
                            endwhile; 
                            
                            if($leaderboard->num_rows === 0):
                            ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="bi bi-trophy-fill"></i>
                                        <p>No tournament data available yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/statistics.js"></script>
</body>
</html> 