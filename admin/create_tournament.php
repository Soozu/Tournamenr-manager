<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch available games with additional details
$games_query = "
    SELECT g.*, 
           COUNT(DISTINCT t.id) as tournament_count,
           COUNT(DISTINCT gg.user_id) as gamemaster_count
    FROM games g
    LEFT JOIN tournaments t ON g.id = t.game_id
    LEFT JOIN gamemaster_games gg ON g.id = gg.game_id
    WHERE g.status = 'active'
    GROUP BY g.id
    ORDER BY g.name";
$games = $conn->query($games_query);

// Fetch available gamemasters with their game expertise
$gamemasters_query = "
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.avatar,
        u.status,
        GROUP_CONCAT(DISTINCT g.name) as game_expertise,
        COUNT(DISTINCT tgm.tournament_id) as active_tournaments,
        GROUP_CONCAT(DISTINCT g.id) as game_ids
    FROM users u
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id
    LEFT JOIN tournaments t ON tgm.tournament_id = t.id AND t.status != 'completed'
    WHERE u.role = 'gamemaster' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY u.username";
$gamemasters = $conn->query($gamemasters_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Validate tournament type based on game
        $game_query = "SELECT team_size FROM games WHERE id = ?";
        $stmt = $conn->prepare($game_query);
        $stmt->bind_param("i", $_POST['game_id']);
        $stmt->execute();
        $game_result = $stmt->get_result()->fetch_assoc();
        
        // Set tournament type based on team size
        $tournament_type = $game_result['team_size'];

        // Insert tournament with additional fields
        $stmt = $conn->prepare("
            INSERT INTO tournaments (
                game_id, 
                name, 
                start_date, 
                end_date, 
                max_teams, 
                prize_pool, 
                registration_deadline,
                rules, 
                team_size, 
                match_duration,
                tournament_type,
                status,
                registration_open,
                auto_assign_gm
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'registration', 1, ?)
        ");

        $stmt->bind_param(
            "isssidssissi",
            $_POST['game_id'],
            $_POST['name'],
            $_POST['start_date'],
            $_POST['end_date'],
            $_POST['max_teams'],
            $_POST['prize_pool'],
            $_POST['registration_deadline'],
            $_POST['rules'],
            $_POST['team_size'],
            $_POST['match_duration'],
            $tournament_type,
            $_POST['auto_assign_gm']
        );

        $stmt->execute();
        $tournament_id = $conn->insert_id;

        // Assign gamemasters if selected
        if (!empty($_POST['gamemasters'])) {
            $gm_stmt = $conn->prepare("
                INSERT INTO tournament_game_masters (tournament_id, user_id, status)
                VALUES (?, ?, 'active')
            ");

            foreach ($_POST['gamemasters'] as $gamemaster_id) {
                $gm_stmt->bind_param("ii", $tournament_id, $gamemaster_id);
                $gm_stmt->execute();
            }
        }

        // Insert tournament settings
        $settings_stmt = $conn->prepare("
            INSERT INTO tournament_settings (
                tournament_id,
                registration_open,
                team_size,
                auto_assign_gm,
                match_duration
            ) VALUES (?, 1, ?, ?, ?)
        ");

        $settings_stmt->bind_param(
            "iiii",
            $tournament_id,
            $_POST['team_size'],
            $_POST['auto_assign_gm'],
            $_POST['match_duration']
        );

        $settings_stmt->execute();

        $conn->commit();
        header("Location: tournaments.php?success=Tournament created successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error creating tournament: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tournament - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/create_tournament.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-trophy"></i> Create Tournament</h1>
                <p>Set up a new tournament and assign gamemasters</p>
            </div>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" class="tournament-form">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-group">
                        <label>Tournament Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Game</label>
                        <select name="game_id" class="form-control" required>
                            <option value="">Select Game</option>
                            <?php while($game = $games->fetch_assoc()): ?>
                                <option value="<?php echo $game['id']; ?>" 
                                        data-team-size="<?php echo $game['team_size']; ?>"
                                        data-platform="<?php echo $game['platform']; ?>">
                                    <?php echo htmlspecialchars($game['name']); ?> 
                                    (<?php echo $game['gamemaster_count']; ?> GMs available)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group col">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Tournament Settings</h2>
                    <div class="form-row">
                        <div class="form-group col">
                            <label>Max Teams</label>
                            <select name="max_teams" class="form-control" required>
                                <option value="4">4 Teams</option>
                                <option value="8">8 Teams</option>
                                <option value="16">16 Teams</option>
                            </select>
                        </div>
                        <div class="form-group col">
                            <label>Team Size</label>
                            <input type="number" name="team_size" class="form-control" required min="1" id="teamSize">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col">
                            <label>Prize Pool ($)</label>
                            <input type="number" name="prize_pool" class="form-control" required min="0" step="100">
                        </div>
                        <div class="form-group col">
                            <label>Match Duration (minutes)</label>
                            <input type="number" name="match_duration" class="form-control" required min="5" value="60">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Registration Deadline</label>
                        <input type="date" name="registration_deadline" class="form-control" required>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="auto_assign_gm" value="1" class="form-check-input" id="autoAssignGM">
                        <label class="form-check-label" for="autoAssignGM">
                            Auto-assign Game Masters to matches
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Assign Gamemasters</h2>
                    <div class="gamemaster-grid">
                        <?php while($gm = $gamemasters->fetch_assoc()): ?>
                            <div class="gamemaster-card" data-games="<?php echo $gm['game_ids']; ?>">
                                <div class="gamemaster-info">
                                    <img src="../images/avatars/<?php echo $gm['avatar']; ?>" 
                                         alt="<?php echo htmlspecialchars($gm['username']); ?>"
                                         class="avatar">
                                    <div class="info-text">
                                        <h4><?php echo htmlspecialchars($gm['username']); ?></h4>
                                        <p class="expertise"><?php echo $gm['game_expertise'] ?: 'No specific expertise'; ?></p>
                                        <p class="active-tournaments">Active Tournaments: <?php echo $gm['active_tournaments']; ?></p>
                                    </div>
                                </div>
                                <div class="select-gm">
                                    <input type="checkbox" name="gamemasters[]" value="<?php echo $gm['id']; ?>">
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Tournament Rules</h2>
                    <div class="form-group">
                        <textarea name="rules" class="form-control" rows="5" 
                                placeholder="Enter tournament rules and guidelines..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Tournament</button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector('select[name="game_id"]').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const teamSize = selectedOption.getAttribute('data-team-size');
        
        // Update team size input based on selected game
        const teamSizeInput = document.getElementById('teamSize');
        if (teamSize) {
            teamSizeInput.value = teamSize.replace(/[^0-9]/g, '');
        }
        
        // Filter gamemasters based on selected game
        const gameId = this.value;
        document.querySelectorAll('.gamemaster-card').forEach(card => {
            const gmGames = card.getAttribute('data-games').split(',');
            if (gmGames.includes(gameId)) {
                card.style.opacity = '1';
                card.querySelector('input[type="checkbox"]').disabled = false;
            } else {
                card.style.opacity = '0.5';
                card.querySelector('input[type="checkbox"]').disabled = true;
                card.querySelector('input[type="checkbox"]').checked = false;
            }
        });
    });

    // Validate dates
    document.querySelector('input[name="end_date"]').addEventListener('change', function() {
        const startDate = document.querySelector('input[name="start_date"]').value;
        if (startDate && this.value < startDate) {
            alert('End date must be after start date');
            this.value = '';
        }
    });

    document.querySelector('input[name="registration_deadline"]').addEventListener('change', function() {
        const startDate = document.querySelector('input[name="start_date"]').value;
        if (startDate && this.value > startDate) {
            alert('Registration deadline must be before tournament start date');
            this.value = '';
        }
    });
    </script>
</body>
</html> 