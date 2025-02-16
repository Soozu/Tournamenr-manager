<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Enhanced tournament query with more details
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.platform as game_platform,
        g.team_size as game_team_size,
        g.icon as game_icon,
        COUNT(DISTINCT tt.team_id) as registered_teams,
        COUNT(DISTINCT m.id) as total_matches,
        GROUP_CONCAT(DISTINCT tgm.user_id) as assigned_gamemasters,
        ts.registration_open,
        ts.auto_assign_gm
    FROM tournaments t
    LEFT JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN tournament_settings ts ON t.id = ts.tournament_id
    WHERE t.id = ?
    GROUP BY t.id";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header("Location: tournaments.php?error=Tournament not found");
    exit;
}

// Convert assigned_gamemasters string to array
$assigned_gamemasters = $tournament['assigned_gamemasters'] ? 
    explode(',', $tournament['assigned_gamemasters']) : [];

// Fetch available games with additional info
$games_query = "
    SELECT g.*, 
           COUNT(DISTINCT gg.user_id) as available_gamemasters
    FROM games g
    LEFT JOIN gamemaster_games gg ON g.id = gg.game_id
    WHERE g.status = 'active'
    GROUP BY g.id
    ORDER BY g.name";
$games = $conn->query($games_query);

// Enhanced gamemaster query with expertise and current assignments
$gamemasters_query = "
    SELECT 
        u.id,
        u.username,
        u.avatar,
        u.status,
        GROUP_CONCAT(DISTINCT g.name) as game_expertise,
        GROUP_CONCAT(DISTINCT gg.game_id) as game_ids,
        COUNT(DISTINCT t2.id) as active_tournaments,
        IF(tgm.tournament_id IS NOT NULL, 1, 0) as is_assigned
    FROM users u
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id 
        AND tgm.tournament_id = ?
    LEFT JOIN tournament_game_masters tgm2 ON u.id = tgm2.user_id
    LEFT JOIN tournaments t2 ON tgm2.tournament_id = t2.id 
        AND t2.status = 'ongoing'
    WHERE u.role = 'gamemaster' 
    AND u.status = 'active'
    GROUP BY u.id
    ORDER BY u.username";

$gm_stmt = $conn->prepare($gamemasters_query);
$gm_stmt->bind_param("i", $tournament_id);
$gm_stmt->execute();
$gamemasters = $gm_stmt->get_result();

// Fetch tournament statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT tt.team_id) as total_teams,
        COUNT(DISTINCT CASE WHEN tt.status = 'approved' THEN tt.team_id END) as approved_teams,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches
    FROM tournaments t
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    WHERE t.id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $tournament_id);
$stats_stmt->execute();
$tournament_stats = $stats_stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Update tournament
        $update_stmt = $conn->prepare("
            UPDATE tournaments SET 
                game_id = ?,
                name = ?,
                start_date = ?,
                end_date = ?,
                max_teams = ?,
                prize_pool = ?,
                registration_deadline = ?,
                rules = ?,
                team_size = ?,
                match_duration = ?,
                auto_assign_gm = ?
            WHERE id = ?
        ");

        $auto_assign = isset($_POST['auto_assign_gm']) ? 1 : 0;

        $update_stmt->bind_param(
            "isssidssiiii",
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
            $auto_assign,
            $tournament_id
        );

        $update_stmt->execute();

        // Update tournament settings
        $settings_stmt = $conn->prepare("
            UPDATE tournament_settings SET 
                team_size = ?,
                auto_assign_gm = ?,
                match_duration = ?
            WHERE tournament_id = ?
        ");

        $settings_stmt->bind_param(
            "iiii",
            $_POST['team_size'],
            $auto_assign,
            $_POST['match_duration'],
            $tournament_id
        );

        $settings_stmt->execute();

        // Update gamemaster assignments
        $delete_gm = $conn->prepare("DELETE FROM tournament_game_masters WHERE tournament_id = ?");
        $delete_gm->bind_param("i", $tournament_id);
        $delete_gm->execute();

        if (!empty($_POST['gamemasters'])) {
            $insert_gm = $conn->prepare("
                INSERT INTO tournament_game_masters (tournament_id, user_id, status) 
                VALUES (?, ?, 'active')
            ");

            foreach ($_POST['gamemasters'] as $gamemaster_id) {
                $insert_gm->bind_param("ii", $tournament_id, $gamemaster_id);
                $insert_gm->execute();
            }
        }

        $conn->commit();
        header("Location: tournaments.php?success=Tournament updated successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating tournament: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tournament - Tournament Manager</title>
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
                <h1><i class="bi bi-pencil-square"></i> Edit Tournament</h1>
                <p>Modify tournament details and assignments</p>
            </div>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" class="tournament-form">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-group">
                        <label>Tournament Name</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($tournament['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Game</label>
                        <select name="game_id" class="form-control" required>
                            <?php while($game = $games->fetch_assoc()): ?>
                                <option value="<?php echo $game['id']; ?>"
                                    <?php echo ($game['id'] == $tournament['game_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['name'] ?? ''); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo $tournament['start_date']; ?>" required>
                        </div>
                        <div class="form-group col">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo $tournament['end_date']; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Tournament Settings Section -->
                <div class="form-section">
                    <h2>Tournament Settings</h2>
                    <div class="form-row">
                        <div class="form-group col">
                            <label>Max Teams</label>
                            <select name="max_teams" class="form-control" required>
                                <option value="4" <?php echo $tournament['max_teams'] == 4 ? 'selected' : ''; ?>>4 Teams</option>
                                <option value="8" <?php echo $tournament['max_teams'] == 8 ? 'selected' : ''; ?>>8 Teams</option>
                                <option value="16" <?php echo $tournament['max_teams'] == 16 ? 'selected' : ''; ?>>16 Teams</option>
                            </select>
                        </div>
                        <div class="form-group col">
                            <label>Team Size</label>
                            <input type="number" name="team_size" class="form-control" 
                                   value="<?php echo $tournament['team_size']; ?>" required min="1">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col">
                            <label>Prize Pool (₱)</label>
                            <input type="number" name="prize_pool" class="form-control" 
                                   value="<?php echo $tournament['prize_pool']; ?>" required min="0">
                        </div>
                        <div class="form-group col">
                            <label>Match Duration (minutes)</label>
                            <input type="number" name="match_duration" class="form-control" 
                                   value="<?php echo $tournament['match_duration']; ?>" required min="5">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Registration Deadline</label>
                        <input type="date" name="registration_deadline" class="form-control" 
                               value="<?php echo $tournament['registration_deadline']; ?>" required>
                    </div>
                </div>

                <!-- Gamemaster Assignment Section -->
                <div class="form-section">
                    <h2>Assign Gamemasters</h2>
                    <div class="gamemaster-grid">
                        <?php while($gm = $gamemasters->fetch_assoc()): 
                            // Check if gamemaster is qualified for this game
                            $gm_game_ids = !empty($gm['game_ids']) ? explode(',', $gm['game_ids']) : [];
                            $is_qualified = in_array($tournament['game_id'], $gm_game_ids);
                        ?>
                            <div class="gamemaster-card <?php echo $is_qualified ? 'qualified' : 'not-qualified'; ?>">
                                <div class="gamemaster-info">
                                    <div class="avatar-container">
                                        <div class="avatar-placeholder">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <?php if($is_qualified): ?>
                                            <span class="qualified-badge" title="Qualified for this game">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="info-text">
                                        <h4><?php echo htmlspecialchars($gm['username'] ?? ''); ?></h4>
                                        <p class="expertise">
                                            <strong>Games:</strong> 
                                            <?php echo !empty($gm['game_expertise']) ? htmlspecialchars($gm['game_expertise']) : 'No games assigned'; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="select-gm">
                                    <input type="checkbox" 
                                           name="gamemasters[]" 
                                           value="<?php echo $gm['id']; ?>"
                                           <?php echo $gm['is_assigned'] ? 'checked' : ''; ?>
                                           <?php echo !$is_qualified ? 'disabled' : ''; ?>>
                                    <?php if(!$is_qualified): ?>
                                        <div class="warning-text">Not qualified for <?php echo htmlspecialchars($tournament['game_name'] ?? ''); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Rules Section -->
                <div class="form-section">
                    <h2>Tournament Rules</h2>
                    <div class="form-group">
                        <textarea name="rules" class="form-control" rows="5"><?php echo htmlspecialchars($tournament['rules'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Tournament</button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 