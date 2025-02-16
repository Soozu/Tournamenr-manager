<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get tournaments for this gamemaster
$tournaments_query = "
    SELECT 
        t.id,
        t.name,
        t.status,
        t.tournament_type,
        t.brackets_generated,
        g.name as game_name,
        g.icon as game_icon,
        COUNT(DISTINCT tt.team_id) as team_count
    FROM tournaments t
    INNER JOIN games g ON t.game_id = g.id
    INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id AND tt.status = 'approved'
    WHERE tgm.user_id = ? AND t.status IN ('active', 'ongoing')
    GROUP BY t.id";

$stmt = $conn->prepare($tournaments_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tournaments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Brackets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/brackets.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-trophy"></i> Tournament Brackets</h1>
            
            <!-- Tournament Controls -->
            <div class="tournament-controls">
                <div class="tournament-selector">
                    <select class="form-select" id="tournamentSelect">
                        <option value="">Select Tournament</option>
                        <?php while($tournament = $tournaments->fetch_assoc()): ?>
                            <option value="<?php echo $tournament['id']; ?>" 
                                    data-status="<?php echo $tournament['status']; ?>"
                                    data-brackets="<?php echo $tournament['brackets_generated']; ?>"
                                    data-teams="<?php echo $tournament['team_count']; ?>">
                                <?php echo htmlspecialchars($tournament['name']); ?> 
                                (<?php echo $tournament['team_count']; ?> teams)
                                <?php 
                                if($tournament['status'] === 'active') echo '- Ready to Generate';
                                if($tournament['status'] === 'ongoing') echo '- In Progress';
                                ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="action-buttons">
                    <button id="viewBrackets" class="btn btn-secondary" disabled>
                        <i class="bi bi-eye"></i> View Brackets
                    </button>
                    <button id="generateBrackets" class="btn btn-primary" disabled>
                        <i class="bi bi-diagram-3"></i> Generate Brackets
                    </button>
                    <button id="clearBrackets" class="btn btn-danger" disabled>
                        <i class="bi bi-trash"></i> Clear Brackets
                    </button>
                </div>
            </div>
        </div>

        <!-- Brackets Container -->
        <div class="tournament-brackets">
            <div class="bracket-container">
                <div class="rounds-wrapper">
                    <!-- Rounds will be dynamically populated -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/brackets.js"></script>
    <script>
        // Handle tournament selection
        document.getElementById('tournamentSelect').addEventListener('change', function() {
            const tournamentId = this.value;
            if (tournamentId) {
                loadBrackets(tournamentId);
            }
        });

        // Handle generate brackets button
        document.getElementById('generateBrackets').addEventListener('click', function() {
            const tournamentId = document.getElementById('tournamentSelect').value;
            if (tournamentId) {
                generateBrackets(tournamentId);
            }
        });

        // Handle clear brackets button
        document.getElementById('clearBrackets').addEventListener('click', function() {
            const tournamentId = document.getElementById('tournamentSelect').value;
            if (tournamentId && confirm('Are you sure you want to clear the brackets? This cannot be undone.')) {
                clearBrackets(tournamentId);
            }
        });
    </script>

    <!-- Update Match Modal -->
    <div class="modal fade" id="updateMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Match Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateMatchForm" onsubmit="return saveMatchResult(this)">
                    <div class="modal-body">
                        <input type="hidden" name="match_id">
                        
                        <!-- Team 1 Section -->
                        <div class="team-score-section">
                            <div class="team-name team1-name"></div>
                            <div class="score-options">
                                <button type="button" class="btn-win" onclick="setScore('team1', 3)">Win 3-0</button>
                                <button type="button" class="btn-win" onclick="setScore('team1', 2)">Win 2-1</button>
                            </div>
                            <input type="number" name="team1_score" class="score-display" readonly>
                        </div>

                        <div class="vs-divider">VS</div>

                        <!-- Team 2 Section -->
                        <div class="team-score-section">
                            <div class="team-name team2-name"></div>
                            <div class="score-options">
                                <button type="button" class="btn-win" onclick="setScore('team2', 3)">Win 3-0</button>
                                <button type="button" class="btn-win" onclick="setScore('team2', 2)">Win 2-1</button>
                            </div>
                            <input type="number" name="team2_score" class="score-display" readonly>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-reset" onclick="resetScores()">Reset</button>
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-save">Save Result</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 