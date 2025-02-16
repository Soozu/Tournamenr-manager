<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Initialize error/success messages
$error_msg = '';
$success_msg = '';

try {
    // Fetch all tournaments with related data
    $tournaments_query = "
        SELECT 
            t.*,
            g.name as game_name,
            g.icon as game_icon,
            g.platform as game_platform,
            COUNT(DISTINCT tt.team_id) as team_count,
            COUNT(DISTINCT m.id) as match_count,
            GROUP_CONCAT(DISTINCT u.username) as gamemaster_names
        FROM tournaments t
        JOIN games g ON t.game_id = g.id
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
        LEFT JOIN matches m ON t.id = m.tournament_id
        LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        LEFT JOIN users u ON tgm.user_id = u.id AND u.role = 'gamemaster'
        GROUP BY t.id, g.name, g.icon, g.platform
        ORDER BY t.created_at DESC";

    $tournaments = $conn->query($tournaments_query);
    
    // Fetch games for filter
    $games_query = "SELECT id, name FROM games ORDER BY name";
    $games = $conn->query($games_query);

} catch (Exception $e) {
    $error_msg = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/tournaments.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Error/Success Messages -->
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-header">
                <div class="header-title">
                    <h1><i class="bi bi-trophy-fill"></i> Tournaments</h1>
                    <p>Manage and monitor all tournaments</p>
                </div>
                <div class="header-actions">
                    <button class="btn-create" onclick="location.href='create_tournament.php'">
                        <i class="bi bi-plus-lg"></i>
                        Create Tournament
                    </button>
                </div>
            </div>

            <!-- Tournament Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchTournament" placeholder="Search tournaments...">
                    </div>
                </div>
                <div class="filter-controls">
                    <div class="platform-buttons">
                        <button class="platform-btn active" data-platform="all">
                            <i class="bi bi-grid-fill"></i> All Types
                        </button>
                        <button class="platform-btn" data-platform="physical">
                            <i class="bi bi-person-walking"></i> Physical
                        </button>
                        <button class="platform-btn" data-platform="online">
                            <i class="bi bi-pc-display"></i> Online
                        </button>
                    </div>
                    <div class="status-filter">
                        <select class="filter-select" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="registration">Registration Open</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tournaments Grid -->
            <div class="tournaments-container">
                <!-- Physical Games Section -->
                <div class="platform-section physical-games">
                    <div class="section-header">
                        <h3><i class="bi bi-person-walking"></i> Physical Tournaments</h3>
                    </div>
                    <div class="tournaments-grid">
                        <?php 
                        $tournaments->data_seek(0);
                        $hasPhysicalTournaments = false;
                        while($tournament = $tournaments->fetch_assoc()): 
                            if($tournament['game_platform'] === 'Physical'):
                                $hasPhysicalTournaments = true;
                        ?>
                            <div class="tournament-card">
                                <div class="tournament-header">
                                    <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                         class="game-icon">
                                    <h3 class="tournament-title"><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                    <span class="status-badge <?php echo $tournament['status']; ?>">
                                        <?php echo ucfirst($tournament['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="tournament-info">
                                    <div class="info-row">
                                        <i class="bi bi-controller"></i>
                                        <?php echo htmlspecialchars($tournament['game_name']); ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-person-fill"></i>
                                        <?php echo $tournament['gamemaster_names'] ? htmlspecialchars($tournament['gamemaster_names']) : '<span class="unassigned">Unassigned</span>'; ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?>
                                    </div>
                                </div>

                                <div class="tournament-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $tournament['team_count']; ?></div>
                                        <div class="stat-label">Teams</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $tournament['match_count']; ?></div>
                                        <div class="stat-label">Matches</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value prize-pool">₱<?php echo number_format($tournament['prize_pool']); ?></div>
                                        <div class="stat-label">Prize Pool</div>
                                    </div>
                                </div>

                                <div class="tournament-actions">
                                    <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" class="btn-view">
                                        <i class="bi bi-eye-fill"></i> View Details
                                    </a>
                                    <button class="action-btn" onclick="location.href='edit_tournament.php?id=<?php echo $tournament['id']; ?>'">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="action-btn" onclick="confirmDelete(<?php echo $tournament['id']; ?>, '<?php echo htmlspecialchars($tournament['name']); ?>')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endwhile;
                        
                        if (!$hasPhysicalTournaments):
                        ?>
                            <div class="no-tournaments">
                                <i class="bi bi-trophy"></i>
                                <h3>No Physical Tournaments</h3>
                                <p>No physical tournaments have been created yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Online Games Section -->
                <div class="platform-section online-games">
                    <div class="section-header">
                        <h3><i class="bi bi-pc-display"></i> Online Tournaments</h3>
                    </div>
                    <div class="tournaments-grid">
                        <?php 
                        $tournaments->data_seek(0);
                        $hasOnlineTournaments = false;
                        while($tournament = $tournaments->fetch_assoc()): 
                            if($tournament['game_platform'] !== 'Physical'):
                                $hasOnlineTournaments = true;
                        ?>
                            <div class="tournament-card">
                                <div class="tournament-header">
                                    <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                         class="game-icon">
                                    <h3 class="tournament-title"><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                    <span class="status-badge <?php echo $tournament['status']; ?>">
                                        <?php echo ucfirst($tournament['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="tournament-info">
                                    <div class="info-row">
                                        <i class="bi bi-controller"></i>
                                        <?php echo htmlspecialchars($tournament['game_name']); ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-person-fill"></i>
                                        <?php echo $tournament['gamemaster_names'] ? htmlspecialchars($tournament['gamemaster_names']) : '<span class="unassigned">Unassigned</span>'; ?>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?>
                                    </div>
                                </div>

                                <div class="tournament-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $tournament['team_count']; ?></div>
                                        <div class="stat-label">Teams</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $tournament['match_count']; ?></div>
                                        <div class="stat-label">Matches</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value prize-pool">₱<?php echo number_format($tournament['prize_pool']); ?></div>
                                        <div class="stat-label">Prize Pool</div>
                                    </div>
                                </div>

                                <div class="tournament-actions">
                                    <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" class="btn-view">
                                        <i class="bi bi-eye-fill"></i> View Details
                                    </a>
                                    <button class="action-btn" onclick="location.href='edit_tournament.php?id=<?php echo $tournament['id']; ?>'">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="action-btn" onclick="confirmDelete(<?php echo $tournament['id']; ?>, '<?php echo htmlspecialchars($tournament['name']); ?>')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endwhile;

                        if (!$hasOnlineTournaments):
                        ?>
                            <div class="no-tournaments">
                                <i class="bi bi-trophy"></i>
                                <h3>No Online Tournaments</h3>
                                <p>No online tournaments have been created yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Delete Tournament
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div>Are you sure you want to delete "<span id="tournamentName" style="color: var(--accent-color);"></span>"?</div>
                    <div class="warning-text">
                        <i class="bi bi-exclamation-circle"></i>
                        This action cannot be undone. All related data including matches, teams, and settings will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancel
                    </button>
                    <button type="button" class="modal-btn modal-btn-delete" id="confirmDeleteBtn">
                        <i class="bi bi-trash-fill"></i> Delete Tournament
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchTournament');
            const statusFilter = document.getElementById('statusFilter');
            const platformButtons = document.querySelectorAll('.platform-btn');
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
                const statusTerm = statusFilter.value;

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
                    const cardTitle = card.querySelector('h3').textContent.toLowerCase();
                    const cardStatus = card.dataset.status;
                    const cardSection = card.closest('.platform-section');

                    const matchesSearch = cardTitle.includes(searchTerm);
                    const matchesStatus = statusTerm === 'all' || cardStatus === statusTerm;
                    const sectionVisible = cardSection.style.display !== 'none';

                    if (matchesSearch && matchesStatus && sectionVisible) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });

        // Delete tournament
        function confirmDelete(tournamentId, tournamentName) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('tournamentName').textContent = tournamentName;
            
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            deleteBtn.onclick = function() {
                // Show loading state with gaming-style animation
                this.innerHTML = `
                    <i class="bi bi-hourglass-split"></i>
                    <span class="loading-text">Deleting</span>
                `;
                this.disabled = true;
                this.style.cursor = 'not-allowed';
                
                // Add loading animation class
                this.classList.add('loading');
                
                // Redirect to delete after brief delay for animation
                setTimeout(() => {
                    window.location.href = `delete_tournament.php?id=${tournamentId}`;
                }, 500);
            };
            
            modal.show();
        }
    </script>
</body>
</html> 