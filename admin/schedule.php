<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Function to validate match date
function validateMatchDate($date, $tournament_id, $conn) {
    // Check if date is in the future
    if (strtotime($date) < time()) {
        return "Match date must be in the future";
    }
    
    // Check if tournament is still active
    $tournament_check = $conn->prepare("SELECT status FROM tournaments WHERE id = ?");
    $tournament_check->bind_param("i", $tournament_id);
    $tournament_check->execute();
    $result = $tournament_check->get_result();
    $tournament = $result->fetch_assoc();
    
    if ($tournament['status'] !== 'active') {
        return "Cannot schedule matches for inactive tournaments";
    }
    
    return true;
}

// Function to check team availability
function checkTeamAvailability($team_id, $match_date, $conn) {
    $buffer_hours = 3; // Time buffer between matches
    
    $check_query = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM matches 
        WHERE (team1_id = ? OR team2_id = ?) 
        AND ABS(TIMESTAMPDIFF(HOUR, match_date, ?)) < ?
    ");
    
    $check_query->bind_param("iisi", $team_id, $team_id, $match_date, $buffer_hours);
    $check_query->execute();
    $result = $check_query->get_result();
    $count = $result->fetch_assoc()['count'];
    
    return $count === 0;
}

// Function to generate tournament brackets and schedule
function generateTournamentSchedule($tournament_id, $conn) {
    try {
        // Get tournament teams
        $teams_query = $conn->prepare("
            SELECT tt.team_id, t.team_name 
            FROM tournament_teams tt
            JOIN teams t ON tt.team_id = t.id
            WHERE tt.tournament_id = ?
            ORDER BY RAND()"); // Randomize team order
        
        $teams_query->bind_param("i", $tournament_id);
        $teams_query->execute();
        $teams_result = $teams_query->get_result();
        $teams = $teams_result->fetch_all(MYSQLI_ASSOC);
        
        $team_count = count($teams);
        
        // Check minimum team requirement (at least 2 teams for 5v5)
        if ($team_count < 2) {
            throw new Exception("Not enough teams to generate schedule (minimum 2 teams needed)");
        }

        // Calculate rounds needed
        $rounds = ceil(log($team_count, 2));
        $total_slots = pow(2, $rounds);
        
        // Start transaction
        $conn->begin_transaction();
        
        // Get tournament details for scheduling
        $tournament_query = $conn->prepare("
            SELECT start_date, end_date, matches_per_day 
            FROM tournaments 
            WHERE id = ?");
        $tournament_query->bind_param("i", $tournament_id);
        $tournament_query->execute();
        $tournament = $tournament_query->get_result()->fetch_assoc();
        
        $start_date = new DateTime($tournament['start_date']);
        $matches_per_day = $tournament['matches_per_day'] ?? 4;
        $match_duration = 3; // Hours per match
        
        // Generate first round matches
        $current_date = clone $start_date;
        $matches_today = 0;
        
        for ($i = 0; $i < $team_count; $i += 2) {
            // Check if we need to move to next day
            if ($matches_today >= $matches_per_day) {
                $current_date->modify('+1 day');
                $matches_today = 0;
            }
            
            // Calculate match time
            $match_time = clone $current_date;
            $match_time->modify('+' . ($matches_today * $match_duration) . ' hours');
            
            // Insert match
            $match_query = $conn->prepare("
                INSERT INTO matches (
                    tournament_id, 
                    team1_id, 
                    team2_id, 
                    match_date, 
                    round_number,
                    bracket_position,
                    status
                ) VALUES (?, ?, ?, ?, 1, ?, 'pending')
            ");
            
            $team1_id = isset($teams[$i]) ? $teams[$i]['team_id'] : null;
            $team2_id = isset($teams[$i + 1]) ? $teams[$i + 1]['team_id'] : null;
            $bracket_position = $i / 2;
            
            $match_date = $match_time->format('Y-m-d H:i:s');
            $match_query->bind_param("iiisi", 
                $tournament_id, 
                $team1_id, 
                $team2_id, 
                $match_date,
                $bracket_position
            );
            $match_query->execute();
            
            $matches_today++;
        }
        
        // Create placeholder matches for future rounds
        for ($round = 2; $round <= $rounds; $round++) {
            $matches_in_round = $total_slots / pow(2, $round);
            
            for ($match = 0; $match < $matches_in_round; $match++) {
                // Move to next day if needed
                if ($matches_today >= $matches_per_day) {
                    $current_date->modify('+1 day');
                    $matches_today = 0;
                }
                
                $match_time = clone $current_date;
                $match_time->modify('+' . ($matches_today * $match_duration) . ' hours');
                
                $match_query = $conn->prepare("
                    INSERT INTO matches (
                        tournament_id,
                        match_date,
                        round_number,
                        bracket_position,
                        status
                    ) VALUES (?, ?, ?, ?, 'pending')
                ");
                
                $match_date = $match_time->format('Y-m-d H:i:s');
                $match_query->bind_param("isii", 
                    $tournament_id, 
                    $match_date,
                    $round,
                    $match
                );
                $match_query->execute();
                
                $matches_today++;
            }
        }
        
        // Update tournament status
        $update_tournament = $conn->prepare("
            UPDATE tournaments 
            SET status = 'ongoing', 
                bracket_generated = 1 
            WHERE id = ?");
        $update_tournament->bind_param("i", $tournament_id);
        $update_tournament->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Function to update bracket progression
function updateBracketProgression($match_id, $winner_id, $conn) {
    try {
        $conn->begin_transaction();
        
        // Get current match info
        $match_query = $conn->prepare("
            SELECT tournament_id, round_number, bracket_position 
            FROM matches 
            WHERE id = ?");
        $match_query->bind_param("i", $match_id);
        $match_query->execute();
        $match = $match_query->get_result()->fetch_assoc();
        
        // Calculate next match position
        $next_round = $match['round_number'] + 1;
        $next_position = floor($match['bracket_position'] / 2);
        
        // Update next match with winner
        $update_query = $conn->prepare("
            UPDATE matches 
            SET " . ($match['bracket_position'] % 2 == 0 ? "team1_id" : "team2_id") . " = ?
            WHERE tournament_id = ? 
            AND round_number = ? 
            AND bracket_position = ?");
        
        $update_query->bind_param("iiii", 
            $winner_id, 
            $match['tournament_id'], 
            $next_round, 
            $next_position
        );
        $update_query->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

try {
    // Fetch all scheduled matches with related data
    $matches_query = "
        SELECT 
            m.*,
            t.name as tournament_name,
            g.name as game_name,
            g.icon as game_icon,
            COALESCE(t1.team_name, 'TBD') as team1_name,
            COALESCE(t2.team_name, 'TBD') as team2_name,
            COALESCE(u.username, 'Unassigned') as gamemaster_name,
            t.gamemaster_id,
            t.status as tournament_status
        FROM matches m
        JOIN tournaments t ON m.tournament_id = t.id
        JOIN games g ON t.game_id = g.id
        LEFT JOIN teams t1 ON m.team1_id = t1.id
        LEFT JOIN teams t2 ON m.team2_id = t2.id
        LEFT JOIN users u ON t.gamemaster_id = u.id
        ORDER BY m.match_date ASC";
    
    $matches = $conn->query($matches_query);

    // Handle match creation/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $tournament_id = $_POST['tournament_id'];
                    $match_date = $_POST['match_date'];
                    $team1_id = $_POST['team1_id'] ?? null;
                    $team2_id = $_POST['team2_id'] ?? null;
                    
                    // Validate match date
                    $date_validation = validateMatchDate($match_date, $tournament_id, $conn);
                    if ($date_validation !== true) {
                        throw new Exception($date_validation);
                    }
                    
                    // Check team availability
                    if ($team1_id && !checkTeamAvailability($team1_id, $match_date, $conn)) {
                        throw new Exception("Team 1 has another match scheduled around this time");
                    }
                    if ($team2_id && !checkTeamAvailability($team2_id, $match_date, $conn)) {
                        throw new Exception("Team 2 has another match scheduled around this time");
                    }
                    
                    // Create match
                    $stmt = $conn->prepare("
                        INSERT INTO matches (tournament_id, team1_id, team2_id, match_date, status)
                        VALUES (?, ?, ?, ?, 'pending')
                    ");
                    $stmt->bind_param("iiis", $tournament_id, $team1_id, $team2_id, $match_date);
                    $stmt->execute();
                    break;

                case 'update':
                    $match_id = $_POST['match_id'];
                    $status = $_POST['status'];
                    $score_team1 = $_POST['score_team1'] ?? null;
                    $score_team2 = $_POST['score_team2'] ?? null;
                    
                    $stmt = $conn->prepare("
                        UPDATE matches 
                        SET status = ?, score_team1 = ?, score_team2 = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("siii", $status, $score_team1, $score_team2, $match_id);
                    $stmt->execute();
                    break;

                case 'delete':
                    $match_id = $_POST['match_id'];
                    
                    // Check if match can be deleted
                    $check = $conn->prepare("SELECT status FROM matches WHERE id = ?");
                    $check->bind_param("i", $match_id);
                    $check->execute();
                    $match_status = $check->get_result()->fetch_assoc()['status'];
                    
                    if ($match_status === 'completed') {
                        throw new Exception("Cannot delete completed matches");
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
                    $stmt->bind_param("i", $match_id);
                    $stmt->execute();
                    break;

                case 'generate_schedule':
                    try {
                        $tournament_id = $_POST['tournament_id'];
                        generateTournamentSchedule($tournament_id, $conn);
                        header("Location: schedule.php?success=schedule_generated");
                        exit;
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                    break;
                    
                case 'update_match':
                    try {
                        $match_id = $_POST['match_id'];
                        $winner_id = $_POST['winner_id'];
                        updateBracketProgression($match_id, $winner_id, $conn);
                        header("Location: schedule.php?success=match_updated");
                        exit;
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                    break;
            }
            
            // Redirect to prevent form resubmission
            header("Location: schedule.php?success=true");
            exit;
        }
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Fetch tournaments for filter
$tournaments = $conn->query("SELECT id, name FROM tournaments ORDER BY name");

// Fetch gamemasters for filter
$gamemasters = $conn->query("SELECT id, username FROM users WHERE role = 'gamemaster' ORDER BY username");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Schedule - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/schedule.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-title">
                    <h1><i class="bi bi-calendar-event-fill"></i> Match Schedule</h1>
                    <p>Manage tournament match schedules</p>
                </div>
                <div class="header-actions">
                    <button class="btn-create" onclick="location.href='create_match.php'">
                        <i class="bi bi-plus-lg"></i>
                        Schedule Match
                    </button>
                </div>
            </div>

            <!-- Schedule Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchMatch" placeholder="Search matches...">
                    </div>
                    <select class="filter-select" id="tournamentFilter">
                        <option value="all">All Tournaments</option>
                        <?php while($tournament = $tournaments->fetch_assoc()): ?>
                            <option value="<?php echo $tournament['id']; ?>">
                                <?php echo htmlspecialchars($tournament['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select class="filter-select" id="gamemasterFilter">
                        <option value="all">All Gamemasters</option>
                        <?php while($gm = $gamemasters->fetch_assoc()): ?>
                            <option value="<?php echo $gm['id']; ?>">
                                <?php echo htmlspecialchars($gm['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- Schedule Timeline -->
            <div class="schedule-timeline">
                <?php 
                $current_date = '';
                if($matches->num_rows > 0):
                    while($match = $matches->fetch_assoc()):
                        $match_date = date('Y-m-d', strtotime($match['match_date']));
                        if($match_date != $current_date):
                            $current_date = $match_date;
                ?>
                            <div class="date-header">
                                <div class="date-badge">
                                    <span class="day"><?php echo date('d', strtotime($match_date)); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($match_date)); ?></span>
                                </div>
                            </div>
                <?php endif; ?>
                        <div class="match-card" data-tournament="<?php echo $match['tournament_id']; ?>" 
                             data-status="<?php echo $match['status']; ?>"
                             data-gamemaster="<?php echo $match['gamemaster_id']; ?>">
                            <div class="match-time">
                                <?php echo date('H:i', strtotime($match['match_date'])); ?>
                            </div>
                            <div class="match-details">
                                <div class="tournament-info">
                                    <img src="../images/games/<?php echo $match['game_icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($match['game_name']); ?>"
                                         class="game-icon">
                                    <span class="tournament-name">
                                        <?php echo htmlspecialchars($match['tournament_name']); ?>
                                    </span>
                                </div>
                                <div class="teams-vs">
                                    <div class="team team1">
                                        <span class="team-name"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                        <span class="score"><?php echo $match['score_team1']; ?></span>
                                    </div>
                                    <div class="vs-badge">VS</div>
                                    <div class="team team2">
                                        <span class="score"><?php echo $match['score_team2']; ?></span>
                                        <span class="team-name"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                    </div>
                                </div>
                                <div class="match-meta">
                                    <span class="gamemaster">
                                        <i class="bi bi-person-badge"></i>
                                        <?php echo htmlspecialchars($match['gamemaster_name']); ?>
                                    </span>
                                    <span class="status-badge <?php echo $match['status']; ?>">
                                        <?php echo ucfirst($match['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="match-actions">
                                <button class="btn-edit" onclick="location.href='edit_match.php?id=<?php echo $match['id']; ?>'">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-delete" onclick="confirmDelete(<?php echo $match['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="no-matches">
                        <i class="bi bi-calendar-x"></i>
                        <h3>No Matches Scheduled</h3>
                        <p>Start by scheduling a new match</p>
                        <button class="btn-create" onclick="location.href='create_match.php'">
                            Schedule Match
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this match? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/schedule.js"></script>
</body>
</html> 