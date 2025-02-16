<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch tournaments with enhanced details
$tournaments_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        g.platform as game_platform,
        COUNT(DISTINCT tt.team_id) as registered_teams,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, ':', u.id)) as gamemasters,
        CASE 
            WHEN t.status = 'completed' THEN 
                (SELECT team_name 
                 FROM teams 
                 WHERE id = (
                     SELECT team1_id 
                     FROM matches 
                     WHERE tournament_id = t.id 
                     AND round_number = (
                         SELECT MAX(round_number) 
                         FROM matches 
                         WHERE tournament_id = t.id
                     )
                     AND status = 'completed'
                     LIMIT 1
                 ))
            ELSE NULL
        END as winner_name
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN users u ON tgm.user_id = u.id
    GROUP BY t.id
    ORDER BY t.end_date DESC";

$tournaments = $conn->query($tournaments_query);

// Fetch games for filter
$games_query = "SELECT id, name FROM games ORDER BY name";
$games = $conn->query($games_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament History - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/history_tournament.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php 
        $current_page = 'history_tournament';
        include 'includes/sidebar.php'; 
        ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="bi bi-clock-history"></i> Tournament History</h2>
                <p>View completed and ongoing tournament records</p>
            </div>

            <div class="filters-section">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchTournament" placeholder="Search tournaments...">
                    </div>
                    
                    <div class="platform-filter">
                        <button class="filter-btn active" data-platform="all">
                            <i class="bi bi-grid"></i> All Types
                        </button>
                        <button class="filter-btn" data-platform="physical">
                            <i class="bi bi-person-walking"></i> Physical
                        </button>
                        <button class="filter-btn" data-platform="online">
                            <i class="bi bi-pc-display"></i> Online
                        </button>
                    </div>

                    <select class="filter-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="registration">Registration</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <div class="tournaments-container">
                <!-- Physical Games Section -->
                <div class="platform-section physical-games">
                    <div class="section-header">
                        <h3><i class="bi bi-person-walking"></i> Physical Tournaments</h3>
                    </div>
                    <div class="tournament-grid">
                        <?php 
                        // Reset pointer
                        $tournaments->data_seek(0);
                        while($tournament = $tournaments->fetch_assoc()): 
                            if($tournament['game_platform'] === 'Physical'):
                        ?>
                            <div class="tournament-card">
                                <div class="game-info">
                                    <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                         class="game-icon">
                                    <div class="game-details">
                                        <span class="tournament-name"><?php echo htmlspecialchars($tournament['name']); ?></span>
                                        <span class="game-type">
                                            <i class="bi bi-controller"></i>
                                            <?php echo htmlspecialchars($tournament['game_name']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="tournament-dates">
                                    <div class="date-item">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?>
                                    </div>
                                    <div class="date-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?>
                                    </div>
                                </div>

                                <div class="tournament-stats">
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value"><?php echo $tournament['registered_teams']; ?></div>
                                            <div class="stat-label">Teams</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="bi bi-trophy-fill"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value"><?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['total_matches']; ?></div>
                                            <div class="stat-label">Matches</div>
                                        </div>
                                    </div>
                                </div>

                                <span class="status-badge <?php echo strtolower($tournament['status']); ?>">
                                    <?php echo ucfirst($tournament['status']); ?>
                                </span>
                            </div>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>

                <!-- Online Games Section -->
                <div class="platform-section online-games">
                    <div class="section-header">
                        <h3><i class="bi bi-pc-display"></i> Online Tournaments</h3>
                    </div>
                    <div class="tournament-grid">
                        <?php 
                        // Reset pointer
                        $tournaments->data_seek(0);
                        while($tournament = $tournaments->fetch_assoc()): 
                            if($tournament['game_platform'] !== 'Physical'):
                        ?>
                            <div class="tournament-card">
                                <div class="game-info">
                                    <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                         class="game-icon">
                                    <div class="game-details">
                                        <span class="tournament-name"><?php echo htmlspecialchars($tournament['name']); ?></span>
                                        <span class="game-type">
                                            <i class="bi bi-controller"></i>
                                            <?php echo htmlspecialchars($tournament['game_name']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="tournament-dates">
                                    <div class="date-item">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?>
                                    </div>
                                    <div class="date-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?>
                                    </div>
                                </div>

                                <div class="tournament-stats">
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value"><?php echo $tournament['registered_teams']; ?></div>
                                            <div class="stat-label">Teams</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="bi bi-trophy-fill"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value"><?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['total_matches']; ?></div>
                                            <div class="stat-label">Matches</div>
                                        </div>
                                    </div>
                                </div>

                                <span class="status-badge <?php echo strtolower($tournament['status']); ?>">
                                    <?php echo ucfirst($tournament['status']); ?>
                                </span>
                            </div>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchTournament');
            const statusFilter = document.getElementById('statusFilter');
            const platformButtons = document.querySelectorAll('.filter-btn');
            const physicalSection = document.querySelector('.physical-games');
            const onlineSection = document.querySelector('.online-games');
            
            let currentPlatform = 'all';

            // Platform filter buttons
            platformButtons.forEach(button => {
                button.addEventListener('click', () => {
                    platformButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    currentPlatform = button.dataset.platform;
                    applyFilters();
                });
            });

            // Search and status filters
            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);

            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusTerm = statusFilter.value.toLowerCase();
                let hasVisibleCards = false;

                // Handle platform visibility
                if (currentPlatform === 'all') {
                    physicalSection.style.display = 'block';
                    onlineSection.style.display = 'block';
                } else if (currentPlatform === 'physical') {
                    physicalSection.style.display = 'block';
                    onlineSection.style.display = 'none';
                } else if (currentPlatform === 'online') {
                    physicalSection.style.display = 'none';
                    onlineSection.style.display = 'block';
                }

                // Filter cards within visible sections
                document.querySelectorAll('.tournament-card').forEach(card => {
                    const cardTitle = card.querySelector('.tournament-name').textContent.toLowerCase();
                    const cardStatus = card.querySelector('.status-badge').textContent.toLowerCase().trim();
                    const cardSection = card.closest('.platform-section');

                    const matchesSearch = cardTitle.includes(searchTerm);
                    const matchesStatus = statusTerm === 'all' || cardStatus === statusTerm;
                    const sectionVisible = cardSection.style.display !== 'none';

                    if (matchesSearch && matchesStatus && sectionVisible) {
                        card.style.display = 'flex';
                        hasVisibleCards = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Only check empty sections for visible platform sections
                if (currentPlatform === 'all' || currentPlatform === 'physical') {
                    checkEmptySection(physicalSection);
                }
                if (currentPlatform === 'all' || currentPlatform === 'online') {
                    checkEmptySection(onlineSection);
                }
            }

            function checkEmptySection(section) {
                if (!section) return;
                
                // Get all tournament cards in this section
                const allCards = section.querySelectorAll('.tournament-card');
                const visibleCards = Array.from(allCards).filter(card => 
                    window.getComputedStyle(card).display !== 'none'
                );
                
                // Find or create empty message
                let emptyMessage = section.querySelector('.empty-message');
                
                // Only show empty message if there are no visible cards
                if (visibleCards.length === 0) {
                    if (!emptyMessage) {
                        emptyMessage = createEmptyMessage();
                        section.querySelector('.tournament-grid').appendChild(emptyMessage);
                    }
                    emptyMessage.style.display = 'block';
                } else if (emptyMessage) {
                    emptyMessage.style.display = 'none';
                }
            }

            function createEmptyMessage() {
                const div = document.createElement('div');
                div.className = 'empty-message';
                div.innerHTML = `
                    <i class="bi bi-inbox"></i>
                    <p>No tournaments found</p>
                    <span>Try adjusting your filters</span>
                `;
                return div;
            }
        });
    </script>
</body>
</html> 