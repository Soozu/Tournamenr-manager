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

// Fetch bracket matches
$matches_query = "SELECT m.*, 
                 t1.team_name as team1_name, t1.team_logo as team1_logo,
                 t2.team_name as team2_name, t2.team_logo as team2_logo,
                 w.team_name as winner_name
                 FROM tournament_single_elimination m
                 LEFT JOIN teams t1 ON m.team1_id = t1.id
                 LEFT JOIN teams t2 ON m.team2_id = t2.id
                 LEFT JOIN teams w ON m.winner_id = w.id
                 WHERE m.tournament_id = ?
                 ORDER BY m.round_number ASC, m.bracket_position ASC";
$stmt = $conn->prepare($matches_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$matches = $stmt->get_result();

// Organize matches by round
$bracket_data = [];
while ($match = $matches->fetch_assoc()) {
    $round = $match['round_number'];
    if (!isset($bracket_data[$round])) {
        $bracket_data[$round] = [];
    }
    $bracket_data[$round][] = $match;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Bracket - <?php echo htmlspecialchars($tournament['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/bracket.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="bracket-header">
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
            <div class="bracket-actions">
                <a href="leaderboard.php?tournament_id=<?php echo $tournament_id; ?>" class="btn btn-secondary">
                    <i class="bi bi-trophy"></i> View Leaderboard
                </a>
                <a href="tournaments.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Tournaments
                </a>
            </div>
        </div>

        <div class="bracket-container">
            <?php if (!empty($bracket_data)): ?>
                <div class="tournament-bracket">
                    <?php foreach ($bracket_data as $round_number => $round_matches): ?>
                        <div class="round">
                            <h3 class="round-title">
                                <?php
                                $total_rounds = count($bracket_data);
                                echo match ($round_number) {
                                    $total_rounds => 'Finals',
                                    $total_rounds - 1 => 'Semi-Finals',
                                    $total_rounds - 2 => 'Quarter-Finals',
                                    default => 'Round ' . $round_number,
                                };
                                ?>
                            </h3>
                            <?php foreach ($round_matches as $match): ?>
                                <div class="match">
                                    <div class="match-teams">
                                        <div class="team <?php echo $match['winner_id'] === $match['team1_id'] ? 'winner' : ''; ?>">
                                            <?php if ($match['team1_id']): ?>
                                                <div class="team-info">
                                                    <?php if ($match['team1_logo']): ?>
                                                        <img src="images/team-logos/<?php echo htmlspecialchars($match['team1_logo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($match['team1_name']); ?>" 
                                                             class="team-logo">
                                                    <?php endif; ?>
                                                    <span class="team-name"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                                </div>
                                                <span class="score"><?php echo $match['team1_score']; ?></span>
                                            <?php else: ?>
                                                <div class="team-info">
                                                    <span class="team-name">TBD</span>
                                                </div>
                                                <span class="score">-</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="team <?php echo $match['winner_id'] === $match['team2_id'] ? 'winner' : ''; ?>">
                                            <?php if ($match['team2_id']): ?>
                                                <div class="team-info">
                                                    <?php if ($match['team2_logo']): ?>
                                                        <img src="images/team-logos/<?php echo htmlspecialchars($match['team2_logo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($match['team2_name']); ?>" 
                                                             class="team-logo">
                                                    <?php endif; ?>
                                                    <span class="team-name"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                                </div>
                                                <span class="score"><?php echo $match['team2_score']; ?></span>
                                            <?php else: ?>
                                                <div class="team-info">
                                                    <span class="team-name">TBD</span>
                                                </div>
                                                <span class="score">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($match['status'] === 'completed'): ?>
                                        <div class="match-status completed">
                                            <i class="bi bi-check-circle"></i> Completed
                                        </div>
                                    <?php else: ?>
                                        <div class="match-status pending">
                                            <i class="bi bi-clock"></i> Pending
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-brackets">
                    <i class="bi bi-diagram-3"></i>
                    <h3>No Bracket Available</h3>
                    <p>The tournament bracket has not been generated yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 