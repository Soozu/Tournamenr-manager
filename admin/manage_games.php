<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

try {
    // Simplified query without gamemaster information
    $games_query = "
        SELECT 
            g.*,
            COUNT(DISTINCT t.id) as tournament_count,
            COUNT(DISTINCT tt.team_id) as team_count,
            COUNT(DISTINCT m.id) as match_count,
            CASE 
                WHEN g.platform = 'Online' THEN 
                    CASE 
                        WHEN g.name LIKE '%Mobile%' OR g.name LIKE '%Wild Rift%' OR g.name LIKE '%MLBB%' THEN 'Mobile'
                        ELSE 'PC'
                    END
                ELSE NULL
            END as device_type
        FROM games g
        LEFT JOIN tournaments t ON g.id = t.game_id
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
        LEFT JOIN matches m ON t.id = m.tournament_id
        GROUP BY g.id
        ORDER BY g.name ASC";
    
    $games = $conn->query($games_query);
} catch (Exception $e) {
    die("Error fetching games: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/manage_games.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-left">
                    <h1><i class="bi bi-trophy"></i> Games</h1>
                    <p>Manage and monitor all games</p>
                </div>
                <div class="header-right">
                    <div class="dropdown">
                        <button class="btn-create dropdown-toggle" type="button" id="createGameDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-plus"></i> Create Game
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="createGameDropdown">
                            <li><a class="dropdown-item" onclick="showAddGameModal('Physical')">
                                <i class="bi bi-person-walking"></i> Physical Game
                            </a></li>
                            <li><a class="dropdown-item" onclick="showAddGameModal('Online')">
                                <i class="bi bi-pc-display"></i> Online Game
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="search-filter-container">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search games...">
                </div>
                <div class="filter-box">
                    <select id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="platform-filter">
                <button class="filter-btn active" data-platform="all">
                    <i class="bi bi-grid"></i> All Games
                </button>
                <button class="filter-btn" data-platform="physical">
                    <i class="bi bi-person-walking"></i> Physical Games
                </button>
                <button class="filter-btn" data-platform="online">
                    <i class="bi bi-pc-display"></i> Online Games
                </button>
            </div>

            <div class="games-container">
                <!-- Physical Games Section -->
                <div class="games-section physical-games" id="physical-games">
                    <h2 class="section-title"><i class="bi bi-person-walking"></i> Physical Games</h2>
                    <div class="games-grid">
                        <?php 
                        // Reset the games result pointer
                        $games->data_seek(0);
                        while($game = $games->fetch_assoc()): 
                            if($game['platform'] === 'Physical'):
                        ?>
                            <div class="game-card">
                                <div class="game-header">
                                    <img src="../images/games/<?php echo $game['icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($game['name']); ?>"
                                         class="game-icon">
                                    <h3 class="game-title"><?php echo htmlspecialchars($game['name']); ?></h3>
                                    <span class="platform-badge <?php echo strtolower($game['platform']); ?>">
                                        <?php echo $game['platform']; ?>
                                    </span>
                                </div>
                                
                                <div class="game-content">
                                    <div class="game-stats">
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['tournament_count']; ?></div>
                                            <div class="stat-label">Tournaments</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['team_count']; ?></div>
                                            <div class="stat-label">Teams</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['match_count']; ?></div>
                                            <div class="stat-label">Matches</div>
                                        </div>
                                    </div>

                                    <div class="game-actions">
                                        <button class="btn-action btn-primary" onclick="location.href='view_game.php?id=<?php echo $game['id']; ?>'">
                                            <i class="bi bi-eye-fill"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>

                <!-- Online Games Section -->
                <div class="games-section online-games" id="online-games">
                    <h2 class="section-title"><i class="bi bi-pc-display"></i> Online Games</h2>
                    <div class="games-grid">
                        <?php 
                        // Reset the games result pointer
                        $games->data_seek(0);
                        while($game = $games->fetch_assoc()): 
                            if($game['platform'] !== 'Physical'):
                        ?>
                            <div class="game-card">
                                <div class="game-header">
                                    <img src="../images/games/<?php echo $game['icon']; ?>" 
                                         alt="<?php echo htmlspecialchars($game['name']); ?>"
                                         class="game-icon">
                                    <h3 class="game-title"><?php echo htmlspecialchars($game['name']); ?></h3>
                                    <span class="platform-badge <?php echo strtolower($game['platform']); ?>">
                                        <?php echo $game['platform']; ?>
                                    </span>
                                    <?php if($game['platform'] === 'Online' && !empty($game['device_type'])): ?>
                                        <span class="platform-type <?php echo strtolower($game['device_type']); ?>">
                                            <?php echo $game['device_type']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="game-content">
                                    <div class="game-stats">
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['tournament_count']; ?></div>
                                            <div class="stat-label">Tournaments</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['team_count']; ?></div>
                                            <div class="stat-label">Teams</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-value"><?php echo $game['match_count']; ?></div>
                                            <div class="stat-label">Matches</div>
                                        </div>
                                    </div>

                                    <div class="game-actions">
                                        <button class="btn-action btn-primary" onclick="location.href='view_game.php?id=<?php echo $game['id']; ?>'">
                                            <i class="bi bi-eye-fill"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Game Modal -->
    <div class="modal fade" id="gameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="gameForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="gameId" name="gameId">
                        <input type="hidden" id="gamePlatform" name="platform">
                        
                        <div class="mb-3">
                            <label for="gameName" class="form-label">Game Name</label>
                            <input type="text" class="form-control" id="gameName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="gameCode" class="form-label">Game Code</label>
                            <input type="text" class="form-control" id="gameCode" name="game_code" required 
                                   pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens allowed"
                                   onkeyup="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '')">
                            <small class="text-muted">Unique identifier for the game (e.g., basketball, volleyball)</small>
                        </div>

                        <div class="mb-3">
                            <label for="gameDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="gameDescription" name="description" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="gameIcon" class="form-label">Game Icon</label>
                            <input type="file" class="form-control" id="gameIcon" name="icon" accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <label for="gameColor" class="form-label">Theme Color</label>
                            <input type="color" class="form-control" id="gameColor" name="color" value="#ff4655" required>
                        </div>

                        <div class="mb-3">
                            <label for="teamSize" class="form-label">Team Size</label>
                            <select class="form-control" id="teamSize" name="team_size" required>
                                <option value="1v1">1v1</option>
                                <option value="2v2">2v2</option>
                                <option value="3v3">3v3</option>
                                <option value="5v5">5v5</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="gameStatus" class="form-label">Status</label>
                            <select class="form-control" id="gameStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Game</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Delete Game
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="gameToDelete"></span>"?</p>
                    <div class="warning-text">
                        <i class="bi bi-exclamation-circle"></i>
                        This action cannot be undone. All related data will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button type="button" class="modal-btn modal-btn-delete" id="confirmDeleteBtn">
                        <i class="bi bi-trash"></i> Delete Game
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Game Modal -->
    <div class="modal fade" id="viewGameModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Game Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="game-details">
                        <div class="game-icon-large">
                            <img src="" alt="Game Icon" id="viewGameIcon">
                        </div>
                        <div class="detail-row">
                            <span class="label">Name:</span>
                            <span id="viewGameName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Description:</span>
                            <span id="viewGameDescription"></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status:</span>
                            <span id="viewGameStatus"></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Added:</span>
                            <span id="viewGameDate"></span>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-value" id="viewTournamentCount">0</div>
                                <div class="stat-label">Tournaments</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value" id="viewTeamCount">0</div>
                                <div class="stat-label">Teams</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value" id="viewMatchCount">0</div>
                                <div class="stat-label">Matches</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add the Edit Modal -->
    <div class="modal fade" id="editGameModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil"></i> Edit Game
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editGameForm" enctype="multipart/form-data">
                        <input type="hidden" id="editGameId" name="gameId">
                        
                        <div class="form-group mb-3">
                            <label for="editGameName" class="form-label">Game Name</label>
                            <input type="text" class="form-control" id="editGameName" name="name" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editGameDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editGameDescription" name="description" rows="3" required></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editGameStatus" class="form-label">Status</label>
                            <select class="form-control" id="editGameStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editGameIcon" class="form-label">Game Icon</label>
                            <input type="file" class="form-control" id="editGameIcon" name="icon" accept="image/*">
                            <div class="current-icon mt-2" id="currentIconPreview">
                                <small class="text-muted">Current icon will be kept if no new image is uploaded</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button type="button" class="modal-btn modal-btn-save" onclick="saveGameEdit()">
                        <i class="bi bi-check2"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show Add Game Modal
        function showAddGameModal(platform) {
            document.getElementById('gameForm').reset();
            document.getElementById('gameId').value = '';
            document.getElementById('gamePlatform').value = platform;
            
            // Update modal title based on platform
            const modalTitle = document.querySelector('#gameModal .modal-title');
            const icon = platform === 'Physical' ? 'person-walking' : 'pc-display';
            modalTitle.innerHTML = `<i class="bi bi-${icon}"></i> Add New ${platform} Game`;
            
            // Set default color
            document.getElementById('gameColor').value = '#ff4655';
            
            // Show the modal
            const gameModal = new bootstrap.Modal(document.getElementById('gameModal'));
            gameModal.show();
        }

        // Edit Game
        function editGame(gameId) {
            fetch(`get_game.php?id=${gameId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(game => {
                    document.getElementById('editGameId').value = game.id;
                    document.getElementById('editGameName').value = game.name;
                    document.getElementById('editGameDescription').value = game.description;
                    document.getElementById('editGameStatus').value = game.status;
                    
                    // Show current icon preview
                    const iconPreview = document.getElementById('currentIconPreview');
                    iconPreview.innerHTML = game.icon ? 
                        `<img src="../images/games/${game.icon}" alt="Current Icon" style="height: 50px; border-radius: 8px;">` :
                        '<small class="text-muted">No current icon</small>';
                    
                    const editModal = new bootstrap.Modal(document.getElementById('editGameModal'));
                    editModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching game details. Please try again.');
                });
        }

        // Delete Game
        function confirmDelete(gameId, gameName) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            // Update modal content
            document.getElementById('gameToDelete').textContent = gameName;
            
            // Update delete button action
            document.getElementById('confirmDeleteBtn').onclick = function() {
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';
                
                window.location.href = `delete_game.php?id=${gameId}`;
            };
            
            modal.show();
        }

        // Add game form submission handler
        document.getElementById('gameForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if all required fields are filled
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Continue with form submission
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            
            const formData = new FormData(this);
            
            fetch('save_game.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error saving game');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save Game';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving game');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Game';
            });
        });

        function viewGame(gameId) {
            fetch(`get_game.php?id=${gameId}`)
                .then(response => response.json())
                .then(game => {
                    document.getElementById('viewGameIcon').src = `../images/games/${game.icon}`;
                    document.getElementById('viewGameName').textContent = game.name;
                    document.getElementById('viewGameDescription').textContent = game.description;
                    document.getElementById('viewGameStatus').textContent = game.status;
                    document.getElementById('viewGameDate').textContent = new Date(game.created_at).toLocaleDateString();
                    document.getElementById('viewTournamentCount').textContent = game.tournament_count;
                    document.getElementById('viewTeamCount').textContent = game.team_count;
                    document.getElementById('viewMatchCount').textContent = game.match_count;
                    
                    const viewModal = new bootstrap.Modal(document.getElementById('viewGameModal'));
                    viewModal.show();
                });
        }

        function saveGameEdit() {
            const form = document.getElementById('editGameForm');
            const formData = new FormData(form);
            const saveBtn = document.querySelector('.modal-btn-save');
            
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

            fetch('save_game.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error saving changes');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check2"></i> Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving changes. Please try again.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check2"></i> Save Changes';
            });
        }

        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const platformButtons = document.querySelectorAll('.filter-btn');
        let currentPlatform = 'all';

        // Platform filter
        platformButtons.forEach(button => {
            button.addEventListener('click', () => {
                platformButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentPlatform = button.dataset.platform;
                filterGames();
            });
        });

        function filterGames() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusTerm = statusFilter.value.toLowerCase();
            const physicalSection = document.getElementById('physical-games');
            const onlineSection = document.getElementById('online-games');
            
            // Show/hide sections based on platform filter
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

            // Filter games within visible sections
            document.querySelectorAll('.game-card').forEach(card => {
                const gameName = card.querySelector('.game-name').textContent.toLowerCase();
                const gameStatus = card.querySelector('.status-badge').textContent.toLowerCase();
                const gamePlatform = card.dataset.platform;
                
                const matchesSearch = gameName.includes(searchTerm);
                const matchesStatus = statusTerm === 'all' || gameStatus === statusTerm;
                const matchesPlatform = currentPlatform === 'all' || gamePlatform === currentPlatform;

                if (matchesSearch && matchesStatus && matchesPlatform) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show no results message if needed
            checkNoResults();
        }

        function checkNoResults() {
            const visibleGames = document.querySelectorAll('.game-card[style="display: flex;"]');
            const noResultsMsg = document.getElementById('noResultsMessage');
            
            if (visibleGames.length === 0) {
                if (!noResultsMsg) {
                    const message = document.createElement('div');
                    message.id = 'noResultsMessage';
                    message.className = 'no-results';
                    message.innerHTML = `
                        <i class="bi bi-search"></i>
                        <p>No games found</p>
                        <span>Try adjusting your search or filters</span>
                    `;
                    document.querySelector('.games-container').appendChild(message);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        // Add event listeners
        searchInput.addEventListener('input', debounce(filterGames, 300));
        statusFilter.addEventListener('change', filterGames);

        // Add debounce for search
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Apply debounce to search
        searchInput.addEventListener('input', debounce(filterGames, 300));

        // Add event listener to auto-generate game code from name
        document.getElementById('gameName').addEventListener('input', function() {
            const gameCode = this.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')         // Replace spaces with hyphens
                .replace(/-+/g, '-');         // Remove duplicate hyphens
            
            document.getElementById('gameCode').value = gameCode;
        });
    </script>
    <style>
        .game-meta {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin: 1rem 0;
            color: var(--text-secondary);
        }

        .game-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .game-meta i {
            font-size: 1.1rem;
            color: var(--accent-color);
            opacity: 0.8;
        }

        .unassigned {
            color: var(--accent-color);
            font-style: italic;
            font-size: 0.9rem;
            opacity: 0.8;
            padding: 2px 8px;
            background: rgba(255, 59, 59, 0.1);
            border-radius: 4px;
        }

        .gamemaster-names {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dropdown-item {
            cursor: pointer;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-item:hover {
            background-color: var(--bs-light);
        }

        .dropdown-item i {
            font-size: 1.1rem;
            color: var(--accent-color);
        }

        .modal-title i {
            margin-right: 0.5rem;
        }

        /* Style for the create game button */
        .btn-create {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-create:hover {
            background: var(--accent-color-dark);
        }

        .platform-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .filter-btn.active {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .games-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        /* Add styles for form validation */
        .is-invalid {
            border-color: #dc3545 !important;
        }

        .is-invalid + small {
            color: #dc3545;
        }
    </style>
</body>
</html> 