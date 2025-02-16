<?php
require_once('config/database.php');

// Get tournament ID from URL
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

// Fetch tournament details
$tournament_query = "SELECT t.*, g.name as game_name, g.icon as game_icon 
                    FROM tournaments t
                    LEFT JOIN games g ON t.game_id = g.id
                    WHERE t.id = ?";
$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

// Fetch team statistics
$teams_query = "SELECT 
                t.id,
                t.team_name,
                t.team_logo,
                COUNT(DISTINCT m.id) as matches_played,
                COUNT(CASE WHEN m.winner_id = t.id THEN 1 END) as matches_won,
                SUM(CASE 
                    WHEN m.team1_id = t.id THEN m.team1_score
                    WHEN m.team2_id = t.id THEN m.team2_score
                    ELSE 0 
                END) as total_score,
                SUM(CASE 
                    WHEN m.winner_id = t.id THEN 3  -- 3 points for a win
                    WHEN m.status = 'completed' AND m.winner_id != t.id THEN 0  -- 0 points for a loss
                    ELSE 0 
                END) as total_points
                FROM teams t
                INNER JOIN tournament_teams tt ON t.id = tt.team_id
                LEFT JOIN tournament_single_elimination m ON (m.team1_id = t.id OR m.team2_id = t.id)
                WHERE tt.tournament_id = ?
                GROUP BY t.id
                ORDER BY total_points DESC, matches_won DESC, total_score DESC";
$stmt = $conn->prepare($teams_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$teams = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Leaderboard - <?php echo htmlspecialchars($tournament['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/leaderboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="leaderboard-header">
            <div class="tournament-info">
                <?php if($tournament['game_icon']): ?>
                    <img src="images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>" 
                         class="game-icon">
                <?php endif; ?>
                <div>
                    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
                    <p class="game-name"><?php echo htmlspecialchars($tournament['game_name']); ?></p>
                </div>
            </div>
            <div class="leaderboard-actions">
                <a href="bracket.php?tournament_id=<?php echo $tournament_id; ?>" class="btn btn-info">
                    <i class="bi bi-diagram-3"></i> View Bracket
                </a>
                <a href="tournaments.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Tournaments
                </a>
            </div>
        </div>

        <div class="leaderboard-container">
            <?php if ($teams->num_rows > 0): ?>
                <div class="leaderboard-table-wrapper">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Team</th>
                                <th>Matches</th>
                                <th>Wins</th>
                                <th>Score</th>
                                <th>Points</th>
                                <th>Win Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            while ($team = $teams->fetch_assoc()):
                                $win_rate = $team['matches_played'] > 0 
                                    ? round(($team['matches_won'] / $team['matches_played']) * 100, 1)
                                    : 0;
                            ?>
                                <tr class="<?php echo $rank <= 3 ? 'top-' . $rank : ''; ?>">
                                    <td class="rank">
                                        <?php if ($rank <= 3): ?>
                                            <i class="bi bi-trophy-fill rank-<?php echo $rank; ?>"></i>
                                        <?php else: ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="team-info">
                                        <?php if ($team['team_logo']): ?>
                                            <img src="images/team-logos/<?php echo htmlspecialchars($team['team_logo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($team['team_name']); ?>" 
                                                 class="team-logo">
                                        <?php endif; ?>
                                        <span class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></span>
                                    </td>
                                    <td><?php echo $team['matches_played']; ?></td>
                                    <td><?php echo $team['matches_won']; ?></td>
                                    <td class="score"><?php echo $team['total_score']; ?></td>
                                    <td class="points"><?php echo $team['total_points']; ?></td>
                                    <td>
                                        <div class="win-rate">
                                            <div class="progress" style="width: <?php echo $win_rate; ?>%"></div>
                                            <span><?php echo $win_rate; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="bi bi-trophy"></i>
                    <h3>No Teams Available</h3>
                    <p>There are no teams registered for this tournament yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 