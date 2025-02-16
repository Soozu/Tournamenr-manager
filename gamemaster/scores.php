<?php
session_start();
require_once('../config/database.php');  // Add database connection
require_once('../includes/auth_check.php');
requireGamemaster();

// Get matches with scores for the gamemaster's assigned games
$scores_query = $conn->query("
    SELECT 
        m.*,
        t.name as tournament_name,
        g.name as game_name,
        g.icon as game_icon,
        t1.team_name as team1_name,
        t2.team_name as team2_name,
        CASE 
            WHEN m.team1_score > m.team2_score THEN t1.team_name
            WHEN m.team2_score > m.team1_score THEN t2.team_name
            WHEN m.team1_score = m.team2_score AND m.status = 'completed' THEN 'Draw'
            ELSE 'Pending'
        END as winner
    FROM matches m
    INNER JOIN tournaments t ON m.tournament_id = t.id
    INNER JOIN games g ON t.game_id = g.id
    INNER JOIN teams t1 ON m.team1_id = t1.id
    INNER JOIN teams t2 ON m.team2_id = t2.id
    INNER JOIN gamemaster_games gg ON g.id = gg.game_id
    WHERE gg.user_id = {$_SESSION['user_id']}
    ORDER BY m.match_date DESC, m.match_time DESC
");

if (!$scores_query) {
    die("Error fetching scores: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scores - Game Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/scores.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="scores-header">
            <div class="header-title">
                <h1><i class="bi bi-trophy"></i> Match Scores</h1>
                <p>View and update match scores for your assigned games</p>
            </div>
            <div class="header-actions">
                <button class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </div>

        <div class="scores-grid">
            <?php if($scores_query->num_rows > 0): ?>
                <?php while($match = $scores_query->fetch_assoc()): ?>
                    <div class="score-card">
                        <div class="score-header">
                            <div class="game-info">
                                <img src="../images/games/<?php echo $match['game_icon']; ?>" alt="<?php echo $match['game_name']; ?>" class="game-icon">
                                <span class="tournament-name"><?php echo $match['tournament_name']; ?></span>
                            </div>
                            <div class="match-status <?php echo $match['status']; ?>">
                                <?php echo ucfirst($match['status']); ?>
                            </div>
                        </div>
                        
                        <div class="score-content">
                            <div class="teams-score">
                                <div class="team team1">
                                    <span class="team-name"><?php echo $match['team1_name']; ?></span>
                                    <div class="score <?php echo ($match['team1_score'] > $match['team2_score']) ? 'winner' : ''; ?>">
                                        <?php echo $match['team1_score']; ?>
                                    </div>
                                </div>
                                <div class="vs">VS</div>
                                <div class="team team2">
                                    <span class="team-name"><?php echo $match['team2_name']; ?></span>
                                    <div class="score <?php echo ($match['team2_score'] > $match['team1_score']) ? 'winner' : ''; ?>">
                                        <?php echo $match['team2_score']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="match-details">
                                <div class="detail-item">
                                    <i class="bi bi-calendar"></i>
                                    <span><?php echo date('F d, Y', strtotime($match['match_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="bi bi-clock"></i>
                                    <span><?php echo date('h:i A', strtotime($match['match_time'])); ?></span>
                                </div>
                                <?php if($match['winner'] != 'Pending'): ?>
                                    <div class="detail-item winner">
                                        <i class="bi bi-trophy"></i>
                                        <span>Winner: <?php echo $match['winner']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="score-actions">
                            <?php if($match['status'] != 'completed'): ?>
                                <button class="btn-update" onclick="updateScore(<?php echo $match['id']; ?>)">
                                    <i class="bi bi-pencil"></i> Update Score
                                </button>
                            <?php endif; ?>
                            <button class="btn-history" onclick="viewHistory(<?php echo $match['id']; ?>)">
                                <i class="bi bi-clock-history"></i> Score History
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-scores">
                    <i class="bi bi-trophy"></i>
                    <h3>No Matches Found</h3>
                    <p>There are no matches available for score updates.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scores.js"></script>
</body>
</html> 