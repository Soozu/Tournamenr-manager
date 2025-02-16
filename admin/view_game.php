<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: manage_games.php');
    exit;
}

$game_id = $_GET['id'];

// Fetch game details with statistics
$game_query = "
    SELECT 
        g.*,
        COUNT(DISTINCT t.id) as tournament_count,
        COUNT(DISTINCT tt.team_id) as team_count,
        COUNT(DISTINCT m.id) as match_count,
        COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tournaments,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
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
    WHERE g.id = ?
    GROUP BY g.id";

$stmt = $conn->prepare($game_query);
$stmt->bind_param('i', $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

if (!$game) {
    header('Location: manage_games.php');
    exit;
}

// Fetch recent tournaments for this game
$tournaments_query = "
    SELECT 
        t.*,
        COUNT(DISTINCT tt.team_id) as team_count,
        COUNT(DISTINCT m.id) as match_count,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches
    FROM tournaments t
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    WHERE t.game_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 5";

$stmt = $conn->prepare($tournaments_query);
$stmt->bind_param('i', $game_id);
$stmt->execute();
$tournaments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> - Game Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/view_game.css" rel="stylesheet">
</head>
<body>
    <!-- Add this div for toast messages right after the body tag -->
    <div class="toast-container"></div>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-left">
                    <a href="manage_games.php" class="back-button">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h1><?php echo htmlspecialchars($game['name']); ?></h1>
                </div>
                <div class="header-actions">
                    <button class="btn-edit" onclick="editGame(<?php echo $game['id']; ?>)">
                        <i class="bi bi-pencil-fill"></i> Edit Game
                    </button>
                </div>
            </div>

            <div class="game-details-container">
                <!-- Game Overview Card -->
                <div class="game-overview card">
                    <div class="game-header">
                        <img src="../images/games/<?php echo $game['icon']; ?>" 
                             alt="<?php echo htmlspecialchars($game['name']); ?>"
                             class="game-icon">
                        <div class="game-info">
                            <div class="badges">
                                <span class="platform-badge <?php echo strtolower($game['platform']); ?>">
                                    <?php echo $game['platform']; ?>
                                </span>
                                <?php if($game['platform'] === 'Online' && !empty($game['device_type'])): ?>
                                    <span class="platform-type <?php echo strtolower($game['device_type']); ?>">
                                        <?php echo $game['device_type']; ?>
                                    </span>
                                <?php endif; ?>
                                <span class="status-badge <?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>
                            </div>
                            <p class="game-description"><?php echo htmlspecialchars($game['description']); ?></p>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $game['tournament_count']; ?></div>
                            <div class="stat-label">Total Tournaments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $game['team_count']; ?></div>
                            <div class="stat-label">Total Teams</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $game['match_count']; ?></div>
                            <div class="stat-label">Total Matches</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $game['completed_tournaments']; ?></div>
                            <div class="stat-label">Completed Tournaments</div>
                        </div>
                    </div>

                    <div class="game-meta">
                        <div class="meta-item">
                            <i class="bi bi-people-fill"></i>
                            <span>Team Size: <?php echo $game['team_size']; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>Added: <?php echo date('M d, Y', strtotime($game['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Tournaments -->
                <div class="recent-tournaments card">
                    <div class="card-header">
                        <h2>Recent Tournaments</h2>
                        <a href="tournaments.php?game=<?php echo $game_id; ?>" class="btn-view-all">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="tournaments-list">
                        <?php if($tournaments->num_rows > 0): ?>
                            <?php while($tournament = $tournaments->fetch_assoc()): ?>
                                <div class="tournament-item">
                                    <div class="tournament-info">
                                        <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                        <div class="tournament-meta">
                                            <span class="status-badge <?php echo strtolower($tournament['status']); ?>">
                                                <?php echo ucfirst($tournament['status']); ?>
                                            </span>
                                            <span class="meta-text">
                                                <i class="bi bi-people-fill"></i>
                                                <?php echo $tournament['team_count']; ?> teams
                                            </span>
                                            <span class="meta-text">
                                                <i class="bi bi-controller"></i>
                                                <?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['match_count']; ?> matches
                                            </span>
                                        </div>
                                    </div>
                                    <div class="tournament-actions">
                                        <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" class="btn-view">
                                            <i class="bi bi-eye-fill"></i> View Details
                                        </a>
                                        <button class="btn-delete" onclick="confirmDeleteTournament(<?php echo $tournament['id']; ?>, '<?php echo htmlspecialchars($tournament['name']); ?>')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="bi bi-calendar-x"></i>
                                <p>No tournaments found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add Edit Game Modal -->
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
                        <input type="hidden" id="editGamePlatform" name="platform">
                        
                        <div class="form-group mb-3">
                            <label for="editGameName" class="form-label">Game Name *</label>
                            <input type="text" class="form-control" id="editGameName" name="name" required>
                            <div class="invalid-feedback">Game name is required</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editGameDescription" class="form-label">Description *</label>
                            <textarea class="form-control" id="editGameDescription" name="description" rows="3" required></textarea>
                            <div class="invalid-feedback">Description is required</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editGameStatus" class="form-label">Status *</label>
                            <select class="form-control" id="editGameStatus" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback">Please select a status</div>
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

    <!-- Add Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTournamentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill"></i> Delete Tournament
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete tournament "<span id="tournamentToDelete"></span>"?</p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancel
                    </button>
                    <button type="button" class="modal-btn modal-btn-delete" onclick="deleteTournament()">
                        <i class="bi bi-trash-fill"></i> Delete Tournament
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editGame(gameId) {
            // Reset form validation
            const form = document.getElementById('editGameForm');
            form.classList.remove('was-validated');
            
            // Fetch game details
            fetch(`get_game.php?id=${gameId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(game => {
                    // Populate the edit form
                    document.getElementById('editGameId').value = game.id;
                    document.getElementById('editGameName').value = game.name;
                    document.getElementById('editGameDescription').value = game.description;
                    document.getElementById('editGameStatus').value = game.status;
                    document.getElementById('editGamePlatform').value = game.platform;
                    
                    // Show current icon preview
                    const iconPreview = document.getElementById('currentIconPreview');
                    iconPreview.innerHTML = game.icon ? 
                        `<img src="../images/games/${game.icon}" alt="Current Icon" style="height: 50px; border-radius: 8px;">` :
                        '<small class="text-muted">No current icon</small>';
                    
                    // Add animation to modal
                    const modal = document.getElementById('editGameModal');
                    modal.addEventListener('show.bs.modal', function () {
                        this.querySelector('.modal-dialog').style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.querySelector('.modal-dialog').style.transform = 'scale(1)';
                        }, 50);
                    });
                    
                    const editModal = new bootstrap.Modal(modal);
                    editModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error fetching game details', 'error');
                });
        }

        function saveGameEdit() {
            const form = document.getElementById('editGameForm');
            
            // Debug log
            console.log('Form Data:', {
                gameId: form.querySelector('#editGameId').value,
                name: form.querySelector('#editGameName').value,
                description: form.querySelector('#editGameDescription').value,
                status: form.querySelector('#editGameStatus').value,
                platform: form.querySelector('#editGamePlatform').value
            });

            // Check form validity
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);
            const saveBtn = document.querySelector('.modal-btn-save');
            
            // Debug log
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

            fetch('save_game.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data); // Debug log
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editGameModal'));
                    modal.hide();
                    showToast('Game updated successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Error saving changes', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check2"></i> Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error saving changes. Please try again.', 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check2"></i> Save Changes';
            });
        }

        // Updated showToast function to handle different types
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            
            const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
            const headerClass = type === 'success' ? 'bg-success' : 'bg-danger';
            
            toast.innerHTML = `
                <div class="toast-header ${headerClass}">
                    <i class="bi ${iconClass} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        let tournamentIdToDelete = null;

        function confirmDeleteTournament(id, name) {
            tournamentIdToDelete = id;
            document.getElementById('tournamentToDelete').textContent = name;
            const modal = new bootstrap.Modal(document.getElementById('deleteTournamentModal'));
            modal.show();
        }

        function deleteTournament() {
            if (!tournamentIdToDelete) return;
            
            const deleteBtn = document.querySelector('.modal-btn-delete');
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';

            fetch('delete_tournament.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tournamentId: tournamentIdToDelete
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTournamentModal'));
                    modal.hide();
                    showToast('Tournament deleted successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Error deleting tournament', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Delete';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error deleting tournament', 'error');
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Delete';
            });
        }
    </script>
</body>
</html> 