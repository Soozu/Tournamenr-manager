<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

try {
    $schedules_query = $conn->query("
        SELECT 
            tb.id,
            tb.tournament_id,
            tb.round_number,
            tb.match_order,
            tb.team1_id,
            tb.team2_id,
            tb.winner_id,
            tb.status,
            tb.match_date,
            tb.match_time,
            tb.team1_score,
            tb.team2_score,
            tb.best_of,
            tb.games_won_team1,
            tb.games_won_team2,
            tb.bracket_type as match_bracket,
            g.name as game_name,
            g.icon as game_icon,
            t.name as tournament_name,
            t.status as tournament_status,
            t.completed_at,
            t.prize_pool,
            t.bracket_type,
            t1.team_name as team1_name,
            t1.team_logo as team1_logo,
            t2.team_name as team2_name,
            t2.team_logo as team2_logo,
            CASE 
                WHEN tb.winner_id = t1.id THEN t1.team_name
                WHEN tb.winner_id = t2.id THEN t2.team_name
                ELSE NULL 
            END as winner_name
        FROM tournament_brackets tb
        INNER JOIN tournaments t ON tb.tournament_id = t.id
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        LEFT JOIN teams t1 ON tb.team1_id = t1.id
        LEFT JOIN teams t2 ON tb.team2_id = t2.id
        WHERE tgm.user_id = " . (int)$_SESSION['user_id'] . "
        ORDER BY 
            t.status DESC,
            tb.match_date DESC,
            tb.match_time DESC,
            tb.round_number ASC,
            tb.match_order ASC
    ");

    if (!$schedules_query) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    die("Error fetching schedules: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/schedules.css" rel="stylesheet">
    <link href="css/navigation.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/main.min.css' rel='stylesheet' />
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/main.min.css' rel='stylesheet' />
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <div class="header-title">
                <h1><i class="bi bi-calendar3"></i> Match Schedules</h1>
                <p>View tournament matches</p>
            </div>
            <div class="header-actions">
                <select class="match-filter" id="matchFilter">
                    <option value="all">All Matches</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="pending">Upcoming</option>
                    <option value="completed">Completed</option>
                </select>
                <button class="calendar-btn" onclick="openCalendarView()">
                    <i class="bi bi-calendar-week"></i> Calendar View
                </button>
            </div>
        </div>

        <div class="schedule-timeline">
            <?php 
            $current_date = null;
            if($schedules_query->num_rows > 0):
                while($schedule = $schedules_query->fetch_assoc()):
                    if($schedule['match_date']):
                        $schedule_date = date('Y-m-d', strtotime($schedule['match_date']));
                        if($schedule_date != $current_date):
                            if($current_date !== null) echo '</div>'; // Close previous date group
                            $current_date = $schedule_date;
            ?>
                        <div class="date-header">
                            <div class="date-badge">
                                <span class="day"><?php echo date('d', strtotime($schedule_date)); ?></span>
                                <span class="month"><?php echo date('M', strtotime($schedule_date)); ?></span>
                            </div>
                            <div class="date-line"></div>
                        </div>
                        <div class="schedule-group">
            <?php 
                        endif;
                    endif;
            ?>
                    <div class="schedule-card <?php echo $schedule['status']; ?>">
                        <div class="game-info">
                            <img src="../images/games/<?php echo $schedule['game_icon']; ?>" 
                                 alt="<?php echo htmlspecialchars($schedule['game_name']); ?>" 
                                 class="game-icon">
                            <div class="game-details">
                                <span class="game-name"><?php echo htmlspecialchars($schedule['game_name']); ?></span>
                                <span class="tournament-name"><?php echo htmlspecialchars($schedule['tournament_name']); ?></span>
                                <?php if($schedule['tournament_status'] === 'completed'): ?>
                                    <span class="completion-date">
                                        Completed: <?php echo date('M d, Y', strtotime($schedule['completed_at'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="match-details">
                            <div class="round-info">
                                Round <?php echo $schedule['round_number']; ?> - Match <?php echo $schedule['match_order']; ?>
                            </div>
                            
                            <div class="teams-vs">
                                <div class="team team1 <?php echo ($schedule['winner_id'] == $schedule['team1_id']) ? 'winner' : ''; ?>">
                                    <span class="team-name"><?php echo htmlspecialchars($schedule['team1_name'] ?? 'TBD'); ?></span>
                                    <?php if($schedule['status'] === 'completed'): ?>
                                        <span class="team-score"><?php echo $schedule['team1_score']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="vs-badge">VS</div>
                                <div class="team team2 <?php echo ($schedule['winner_id'] == $schedule['team2_id']) ? 'winner' : ''; ?>">
                                    <span class="team-name"><?php echo htmlspecialchars($schedule['team2_name'] ?? 'TBD'); ?></span>
                                    <?php if($schedule['status'] === 'completed'): ?>
                                        <span class="team-score"><?php echo $schedule['team2_score']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="match-info">
                                <span class="match-time">
                                    <i class="bi bi-clock"></i>
                                    <?php 
                                    if(!empty($schedule['match_date']) && !empty($schedule['match_time'])) {
                                        echo date('M d, Y h:i A', strtotime($schedule['match_date'] . ' ' . $schedule['match_time'])); 
                                    } else {
                                        echo 'Time TBD';
                                    }
                                    ?>
                                </span>
                                <?php if($schedule['status'] === 'completed' && $schedule['winner_id']): ?>
                                    <span class="winner-label">
                                        <i class="bi bi-trophy-fill"></i>
                                        Winner: <?php echo htmlspecialchars($schedule['winner_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="schedule-actions">
                            <span class="status-badge <?php echo $schedule['status']; ?>">
                                <?php echo ucfirst($schedule['status']); ?>
                            </span>
                        </div>
                    </div>
            <?php 
                endwhile;
                if($current_date !== null) echo '</div>'; // Close last date group
            endif;
            ?>
                <div class="no-schedules">
                    <i class="bi bi-calendar-x"></i>
                    <h3>No Matches Found</h3>
                    <p>There are no matches to display.</p>
                </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/schedules.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/main.min.js'></script>
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel">Match Calendar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="matchCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 