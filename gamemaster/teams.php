<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get assigned tournaments for the gamemaster
$tournaments_query = "
    SELECT 
        t.id,
        t.name,
        t.status,
        g.name as game_name,
        g.icon as game_icon
    FROM tournaments t
    INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    INNER JOIN games g ON t.game_id = g.id
    WHERE tgm.user_id = ?
    ORDER BY t.status ASC, t.start_date DESC";

$stmt = $conn->prepare($tournaments_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tournaments = $stmt->get_result();

// Get selected tournament
$selected_tournament = isset($_GET['tournament']) ? (int)$_GET['tournament'] : null;

// Get teams for the selected tournament or all assigned tournaments
$teams_query = "
    SELECT 
        t.*,
        g.id as game_id,
        g.name as game_name,
        g.icon as game_icon,
        g.team_size,
        g.platform,
        tour.id as tournament_id,
        tour.name as tournament_name,
        tour.status as tournament_status,
        tt.status as registration_status,
        tt.tournament_id as tt_tournament_id
    FROM teams t
    INNER JOIN tournament_teams tt ON t.id = tt.team_id
    INNER JOIN tournaments tour ON tt.tournament_id = tour.id
    INNER JOIN games g ON tour.game_id = g.id
    INNER JOIN tournament_game_masters tgm ON tour.id = tgm.tournament_id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN members mem ON tm.member_id = mem.id
    LEFT JOIN matches m ON (t.id = m.team1_id OR t.id = m.team2_id) 
        AND m.tournament_id = tour.id
    WHERE tgm.user_id = ? " . 
    ($selected_tournament ? "AND tour.id = ? " : "") .
    "GROUP BY t.id, tour.id
    ORDER BY g.platform DESC, t.created_at DESC";

$stmt = $conn->prepare($teams_query);
if ($selected_tournament) {
    $stmt->bind_param("ii", $_SESSION['user_id'], $selected_tournament);
} else {
    $stmt->bind_param("i", $_SESSION['user_id']);
}
$stmt->execute();
$teams = $stmt->get_result();

// Add this query to get tournament status
$tournament_query = "
    SELECT t.*, g.name as game_name 
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    WHERE t.id = ?";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/teams.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="content-header">
                <h1><i class="bi bi-people-fill"></i> Teams Management</h1>
                <div class="header-actions">
                    <select class="form-select tournament-select" onchange="window.location.href=this.value">
                        <option value="?">All Tournaments</option>
                        <?php while($tournament = $tournaments->fetch_assoc()): ?>
                            <option value="?tournament=<?php echo $tournament['id']; ?>" 
                                    <?php echo $selected_tournament == $tournament['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tournament['name']); ?> 
                                (<?php echo ucfirst($tournament['status']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="teamSearch" placeholder="Search teams...">
                    </div>
                </div>
            </div>

            <div class="tournament-sections">
                <!-- Physical Tournaments Section -->
                <div class="platform-section physical-tournaments">
                    <div class="section-header">
                        <h2><i class="bi bi-person-walking"></i> Physical Tournaments</h2>
                    </div>
                    <?php 
                    $teams->data_seek(0);
                    $has_physical = false;
                    while($team = $teams->fetch_assoc()):
                        if($team['platform'] === 'Physical'):
                            $has_physical = true;
                            if ($current_tournament !== $team['tournament_name']):
                                if ($current_tournament !== null) echo '</div></div>'; // Close previous tournament section
                                $current_tournament = $team['tournament_name'];
                    ?>
                            <div class="tournament-section">
                                <div class="tournament-header">
                                    <h3>
                                        <img src="../images/games/<?php echo htmlspecialchars($team['game_icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($team['game_name']); ?>"
                                             class="game-icon">
                                        <span class="tournament-title"><?php echo htmlspecialchars($team['tournament_name']); ?></span>
                                    </h3>
                                    <div class="header-actions">
                                        <span class="tournament-status <?php echo $team['tournament_status']; ?>">
                                            <?php echo ucfirst($team['tournament_status']); ?>
                                        </span>
                                        <a href="view_tournament.php?id=<?php echo htmlspecialchars($team['tt_tournament_id']); ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-gear"></i> Manage Teams
                                        </a>
                                    </div>
                                </div>
                                <div class="teams-grid">
                    <?php endif; ?>
                                    <div class="team-card" data-team-id="<?php echo $team['id']; ?>">
                                        <div class="team-header">
                                            <div class="team-identity">
                                                <div class="team-logo-wrapper">
                                                    <?php 
                                                    $logo_path = '../images/team-logos/';
                                                    $default_logo = 'default-team-logo.png';
                                                    $team_logo = $team['team_logo'] ? $team['team_logo'] : $default_logo;
                                                    $is_default = !$team['team_logo'] || !file_exists($logo_path . $team_logo);
                                                    
                                                    if ($is_default) {
                                                        $team_logo = $default_logo;
                                                    }
                                                    ?>
                                                    <img src="<?php echo $logo_path . htmlspecialchars($team_logo); ?>" 
                                                         alt="<?php echo htmlspecialchars($team['team_name']); ?>"
                                                         class="team-logo <?php echo $is_default ? 'default' : ''; ?>"
                                                         onerror="this.src='<?php echo $logo_path . $default_logo; ?>'; this.classList.add('default');">
                                                </div>
                                                <div class="team-info">
                                                    <h3 class="team-title"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                                    <div class="team-meta">
                                                        <span class="game-badge">
                                                            <img src="../images/games/<?php echo htmlspecialchars($team['game_icon']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($team['game_name']); ?>">
                                                            <span class="game-name"><?php echo htmlspecialchars($team['game_name']); ?></span>
                                                        </span>
                                                        <span class="registration-status <?php echo $team['registration_status']; ?>">
                                                            <?php echo ucfirst($team['registration_status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Add the action buttons -->
                                            <div class="team-actions">
                                                <button class="btn btn-sm btn-outline-primary view-members-btn" 
                                                        onclick="viewMembers(<?php echo $team['id']; ?>)">
                                                    <i class="bi bi-people"></i> View
                                                </button>
                                                
                                                <?php if($team['tournament_status'] === 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-danger delete-team-btn" 
                                                            onclick="deleteTeam(<?php echo $team['id']; ?>)">
                                                        <i class="bi bi-trash"></i> Del
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                    <?php 
                        endif;
                    endwhile;
                    if ($current_tournament !== null) echo '</div></div>';
                    if (!$has_physical): 
                    ?>
                        <div class="no-teams">
                            <i class="bi bi-trophy"></i>
                            <h3>No Physical Tournaments</h3>
                            <p>No physical tournaments are currently assigned to you.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Online Tournaments Section -->
                <div class="platform-section online-tournaments">
                    <div class="section-header">
                        <h2><i class="bi bi-pc-display"></i> Online Tournaments</h2>
                    </div>
                    <?php 
                    $teams->data_seek(0);
                    $current_tournament = null;
                    $has_online = false;
                    while($team = $teams->fetch_assoc()):
                        if($team['platform'] !== 'Physical'):
                            $has_online = true;
                            if ($current_tournament !== $team['tournament_name']):
                                if ($current_tournament !== null) echo '</div></div>'; // Close previous tournament section
                                $current_tournament = $team['tournament_name'];
                    ?>
                            <div class="tournament-section">
                                <div class="tournament-header">
                                    <h3>
                                        <img src="../images/games/<?php echo htmlspecialchars($team['game_icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($team['game_name']); ?>"
                                             class="game-icon">
                                        <span class="tournament-title"><?php echo htmlspecialchars($team['tournament_name']); ?></span>
                                    </h3>
                                    <div class="header-actions">
                                        <span class="tournament-status <?php echo $team['tournament_status']; ?>">
                                            <?php echo ucfirst($team['tournament_status']); ?>
                                        </span>
                                        <a href="view_tournament.php?id=<?php echo htmlspecialchars($team['tt_tournament_id']); ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-gear"></i> Manage Teams
                                        </a>
                                    </div>
                                </div>
                                <div class="teams-grid">
                    <?php endif; ?>
                                    <div class="team-card" data-team-id="<?php echo $team['id']; ?>">
                                        <div class="team-header">
                                            <div class="team-identity">
                                                <div class="team-logo-wrapper">
                                                    <?php 
                                                    $logo_path = '../images/team-logos/';
                                                    $default_logo = 'default-team-logo.png';
                                                    $team_logo = $team['team_logo'] ? $team['team_logo'] : $default_logo;
                                                    $is_default = !$team['team_logo'] || !file_exists($logo_path . $team_logo);
                                                    
                                                    if ($is_default) {
                                                        $team_logo = $default_logo;
                                                    }
                                                    ?>
                                                    <img src="<?php echo $logo_path . htmlspecialchars($team_logo); ?>" 
                                                         alt="<?php echo htmlspecialchars($team['team_name']); ?>"
                                                         class="team-logo <?php echo $is_default ? 'default' : ''; ?>"
                                                         onerror="this.src='<?php echo $logo_path . $default_logo; ?>'; this.classList.add('default');">
                                                </div>
                                                <div class="team-info">
                                                    <h3 class="team-title"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                                    <div class="team-meta">
                                                        <span class="game-badge">
                                                            <img src="../images/games/<?php echo htmlspecialchars($team['game_icon']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($team['game_name']); ?>">
                                                            <span class="game-name"><?php echo htmlspecialchars($team['game_name']); ?></span>
                                                        </span>
                                                        <span class="registration-status <?php echo $team['registration_status']; ?>">
                                                            <?php echo ucfirst($team['registration_status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Add the action buttons -->
                                            <div class="team-actions">
                                                <button class="btn btn-sm btn-outline-primary view-members-btn" 
                                                        onclick="viewMembers(<?php echo $team['id']; ?>)">
                                                    <i class="bi bi-people"></i> View
                                                </button>
                                                
                                                <?php if($team['tournament_status'] === 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-danger delete-team-btn" 
                                                            onclick="deleteTeam(<?php echo $team['id']; ?>)">
                                                        <i class="bi bi-trash"></i> Del
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                    <?php 
                        endif;
                    endwhile;
                    if ($current_tournament !== null) echo '</div></div>';
                    if (!$has_online): 
                    ?>
                        <div class="no-teams">
                            <i class="bi bi-trophy"></i>
                            <h3>No Online Tournaments</h3>
                            <p>No online tournaments are currently assigned to you.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const teamSearch = document.getElementById('teamSearch');
        const teamCards = document.querySelectorAll('.team-card');

        teamSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            teamCards.forEach(card => {
                const teamName = card.querySelector('.team-info h3').textContent.toLowerCase();
                const visible = teamName.includes(searchTerm);
                card.style.display = visible ? '' : 'none';
                
                // Handle empty tournament sections
                const section = card.closest('.tournament-section');
                if (section) {
                    const visibleCards = section.querySelectorAll('.team-card[style=""]').length;
                    section.style.display = visibleCards > 0 ? '' : 'none';
                }
            });
        });
    });
    </script>
    <script>
    function deleteTeam(teamId) {
        if (!confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
            return;
        }

        fetch('ajax/delete_team.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `team_id=${teamId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the team card from the UI
                const teamCard = document.querySelector(`.team-card[data-team-id="${teamId}"]`);
                if (teamCard) {
                    teamCard.remove();
                }
            } else {
                alert('Error deleting team: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting team. Please try again.');
        });
    }
    </script>

    <!-- Add this before </body> -->
    <div class="modal fade" id="membersModal" tabindex="-1" aria-labelledby="membersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="membersModalLabel">Team Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="membersModalBody">
                    <div class="members-list">
                        <!-- Members will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewMembers(teamId) {
        // Show loading state
        const modalBody = document.getElementById('membersModalBody');
        modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('membersModal'));
        modal.show();
        
        // Fetch team members
        fetch(`ajax/get_team_members.php?team_id=${teamId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let membersHtml = '<div class="members-list">';
                    data.members.forEach(member => {
                        membersHtml += `
                            <div class="member-item">
                                <div class="member-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="member-info">
                                    <span class="member-name">${member.full_name}</span>
                                    <span class="member-id">ID: ${member.membership_id}</span>
                                    ${member.is_captain ? '<span class="captain-badge">Captain</span>' : ''}
                                </div>
                            </div>
                        `;
                    });
                    membersHtml += '</div>';
                    modalBody.innerHTML = membersHtml;
                } else {
                    modalBody.innerHTML = '<div class="alert alert-danger">Error loading team members</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Error loading team members</div>';
            });
    }
    </script>

    <!-- Add this CSS -->
    <style>
    /* Platform Section Styling */
    .platform-section {
        margin-bottom: 2rem;  /* Reduced from 3rem */
        background: var(--card-bg);
        border-radius: 12px;
        padding: 1.25rem;     /* Reduced from 1.5rem */
    }

    /* Tournament Section Styling */
    .tournament-section {
        margin-bottom: 1.5rem;  /* Reduced from 2rem */
        background: var(--darker-bg);
        border-radius: 8px;
        padding: 1rem;
    }

    .tournament-section:last-child {
        margin-bottom: 0;
    }

    /* Tournament Header */
    .tournament-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }

    /* Teams Grid */
    .teams-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;           /* Reduced from 1.5rem */
    }

    /* Team Card */
    .team-card {
        padding: 1rem;       /* Reduced from 1.5rem */
        background: var(--card-bg);
        border-radius: 8px;
        border: 1px solid var(--border-color);
        margin-bottom: 0;    /* Remove margin since we're using grid gap */
    }

    .team-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .team-identity {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* Team Logo */
    .team-logo-wrapper {
        width: 48px;         /* Fixed size */
        height: 48px;        /* Fixed size */
        flex-shrink: 0;
    }

    .team-logo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }

    /* Team Info */
    .team-info {
        flex-grow: 1;
        min-width: 0;        /* Prevent text overflow */
    }

    .team-title {
        font-size: 1.1rem;   /* Reduced from 1.25rem */
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .team-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;        /* Reduced from 1rem */
        flex-wrap: wrap;
    }

    /* Game Badge */
    .game-badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
    }

    .game-badge img {
        width: 16px;         /* Reduced from 20px */
        height: 16px;        /* Reduced from 20px */
    }

    /* Status Badges */
    .registration-status,
    .tournament-status {
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
    }

    /* Action Buttons */
    .team-actions {
        display: flex;
        gap: 0.5rem;
    }

    .team-actions button {
        padding: 0.25rem 0.75rem;
        font-size: 0.85rem;
    }

    /* Empty State */
    .no-teams {
        padding: 2rem;       /* Reduced from 3rem */
        margin: 0.5rem;
    }

    .no-teams i {
        font-size: 2rem;     /* Reduced from 2.5rem */
        margin-bottom: 0.75rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .teams-grid {
            grid-template-columns: 1fr;
        }

        .tournament-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .team-header {
            flex-direction: column;
        }

        .team-actions {
            width: 100%;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }
    }
    </style>
</body>
</html> 