<?php
session_start();
require_once 'config/database.php';

// Fetch game categories
$sql = "SELECT * FROM games WHERE status = 'active' ORDER BY name ASC";
$result = $conn->query($sql);
$gameCategories = [];
while($row = $result->fetch_assoc()) {
    $gameCategories[$row['game_code']] = $row;
}

// Separate games into physical and online
$physicalGames = [];
$onlineGames = [];
foreach ($gameCategories as $game) {
    if ($game['platform'] === 'Physical') { // Adjust this condition based on your actual data
        $physicalGames[] = $game;
    } else {
        $onlineGames[] = $game;
    }
}

// Fetch active tournaments with game information
$activeTournamentsSql = "
    SELECT 
        t.*,
        g.game_code,
        g.name as game_name,
        g.icon,
        g.color,
        g.platform,
        COALESCE((SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.id), 0) as registered_teams
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    WHERE t.status = 'registration'
    ORDER BY t.start_date ASC
    LIMIT 6";
$activeTournaments = $conn->query($activeTournamentsSql);

// Check for query errors
if (!$activeTournaments) {
    die("Error fetching tournaments: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">                   
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSG Tournament</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">hgfvghfghfgfgfghfStudent Sportfest</h1>
                <p class="hero-description">
                    Compete in the ultimate gaming championship! Join your fellow students in epic battles across multiple esports titles.
                </p>
                <a href="categories.php" class="hero-cta">
                    <i class="bi bi-controller"></i>
                    Browse Tournaments
                </a>
            </div>
        </div>
        <div class="floating-icons">
            <img src="images/games/mobile.png" alt="" class="game-icon-float">
            <img src="images/games/tekken.png" alt="" class="game-icon-float">
            <img src="images/games/valorant.png" alt="" class="game-icon-float">
            <img src="images/games/wildrift.png" alt="" class="game-icon-float">
            <img src="images/games/codm.png" alt="" class="game-icon-float">
            <img src="images/games/lol.png" alt="" class="game-icon-float">
        </div>
    </section>

    <!-- Game Categories Section -->
    <section class="game-categories">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-controller me-2"></i>Game Categories
                </h2>
                <a href="categories.php" class="btn btn-outline-light">
                    <i class="bi bi-grid me-2"></i>View All Categories
                </a>
            </div>
            
            <div class="categories-container">
                <!-- Physical Games Column -->
                <div class="category-column">
                    <div class="category-header">
                        <h3><i class="bi bi-person-walking me-2"></i>Physical Games</h3>
                        <span class="game-count"><?php echo count($physicalGames); ?> Games</span>
                    </div>
                    <div class="game-grid">
                        <?php foreach ($physicalGames as $game): ?>
                        <div class="game-card-wrapper">
                            <a href="tournaments.php?game=<?php echo $game['game_code']; ?>" class="game-card">
                                <div class="game-banner" style="background-color: <?php echo $game['color']; ?>">
                                    <img src="images/games/<?php echo $game['icon']; ?>" alt="<?php echo $game['name']; ?>" class="game-icon">
                                    <div class="game-overlay"></div>
                                </div>
                                <div class="game-info">
                                    <h3><?php echo $game['name']; ?></h3>
                                    <div class="game-meta">
                                        <span class="platform">
                                            <i class="bi bi-person-walking"></i>
                                            <?php echo $game['platform']; ?>
                                        </span>
                                        <span class="team-size">
                                            <i class="bi bi-people"></i>
                                            <?php echo $game['team_size']; ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Online Games Column -->
                <div class="category-column">
                    <div class="category-header">
                        <h3><i class="bi bi-pc-display me-2"></i>Online Games</h3>
                        <span class="game-count"><?php echo count($onlineGames); ?> Games</span>
                    </div>
                    <div class="game-grid">
                        <?php foreach ($onlineGames as $game): ?>
                        <div class="game-card-wrapper">
                            <a href="tournaments.php?game=<?php echo $game['game_code']; ?>" class="game-card">
                                <div class="game-banner" style="background-color: <?php echo $game['color']; ?>">
                                    <img src="images/games/<?php echo $game['icon']; ?>" alt="<?php echo $game['name']; ?>" class="game-icon">
                                    <div class="game-overlay"></div>
                                </div>
                                <div class="game-info">
                                    <h3><?php echo $game['name']; ?></h3>
                                    <div class="game-meta">
                                        <span class="platform">
                                            <i class="bi bi-<?php echo strtolower($game['platform']) === 'mobile' ? 'phone' : 'pc-display'; ?>"></i>
                                            <?php echo $game['platform']; ?>
                                        </span>
                                        <span class="team-size">
                                            <i class="bi bi-people"></i>
                                            <?php echo $game['team_size']; ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Tournaments Section -->
    <section class="active-tournaments">
        <div class="container">
            <div class="tournament-filters">
                <div class="filter-header">
                    <h2 class="section-title">
                        <i class="bi bi-trophy me-2"></i>Active Tournaments
                    </h2>
                    <div class="filter-actions">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="tournamentSearch" placeholder="Search tournaments...">
                        </div>
                        <select class="filter-select" id="platformFilter">
                            <option value="all">All Platforms</option>
                            <option value="Mobile">Mobile</option>
                            <option value="PC">PC</option>
                            <option value="Console">Console</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-tabs">
                    <button class="filter-tab active" data-game="all">
                        All Games
                    </button>
                    <?php foreach ($gameCategories as $game): ?>
                    <button class="filter-tab" data-game="<?php echo $game['game_code']; ?>"
                            data-platform="<?php echo $game['platform']; ?>">
                        <img src="images/games/<?php echo $game['icon']; ?>" alt="" class="game-icon-small">
                        <?php echo $game['name']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tournament-grid">
                <?php if ($activeTournaments && $activeTournaments->num_rows > 0): ?>
                    <?php while($tournament = $activeTournaments->fetch_assoc()): ?>
                    <div class="tournament-card" 
                         data-game="<?php echo htmlspecialchars($tournament['game_code']); ?>"
                         data-platform="<?php echo htmlspecialchars($tournament['platform']); ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($tournament['name'])); ?>">
                        <div class="tournament-header" style="background-color: <?php echo htmlspecialchars($tournament['color'] ?? '#1a1f2c'); ?>">
                            <img src="images/games/<?php echo htmlspecialchars($tournament['icon'] ?? 'default.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($tournament['game_name'] ?? 'Game'); ?>" 
                                 class="tournament-game-icon">
                            <div class="tournament-status <?php echo htmlspecialchars($tournament['status'] ?? 'pending'); ?>">
                                <?php echo ucfirst(htmlspecialchars($tournament['status'] ?? 'pending')); ?>
                            </div>
                        </div>
                        <div class="tournament-body">
                            <h3 class="tournament-title"><?php echo htmlspecialchars($tournament['name'] ?? 'Tournament'); ?></h3>
                            <div class="tournament-meta">
                                <div class="meta-item">
                                    <i class="bi bi-calendar-event"></i>
                                    <span><?php echo $tournament['start_date'] ? date('M d, Y', strtotime($tournament['start_date'])) : 'TBA'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-people"></i>
                                    <span><?php echo (int)($tournament['registered_teams'] ?? 0); ?>/<?php echo (int)($tournament['max_teams'] ?? 0); ?> Teams</span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-trophy"></i>
                                    <span>₱<?php echo number_format((float)($tournament['prize_pool'] ?? 0), 2); ?> Prize Pool</span>
                                </div>
                            </div>
                            <div class="tournament-actions">
                                <a href="tournament_details.php?id=<?php echo (int)($tournament['id'] ?? 0); ?>" class="btn btn-primary">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tournaments">
                        <i class="bi bi-calendar-x"></i>
                        <h3>No Active Tournaments</h3>
                        <p>Check back later for upcoming tournaments</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-trophy-fill me-2"></i>CSG Tournament</h5>
                    <p>Official esports platform for CSG Student Sportfest</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-discord"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tournamentSearch = document.getElementById('tournamentSearch');
        const platformFilter = document.getElementById('platformFilter');
        const filterTabs = document.querySelectorAll('.filter-tab');
        const tournamentCards = document.querySelectorAll('.tournament-card');
        let currentGame = 'all';
        let currentPlatform = 'all';
        let searchTerm = '';

        // Function to filter tournaments
        function filterTournaments() {
            let hasVisibleCards = false;
            
            tournamentCards.forEach(card => {
                const cardGame = card.dataset.game;
                const cardPlatform = card.dataset.platform;
                const cardName = card.dataset.name;
                
                // Check if the card matches all filter criteria
                const matchesGame = currentGame === 'all' || cardGame === currentGame;
                const matchesPlatform = currentPlatform === 'all' || cardPlatform === currentPlatform;
                const matchesSearch = !searchTerm || (cardName && cardName.includes(searchTerm.toLowerCase()));

                if (matchesGame && matchesPlatform && matchesSearch) {
                    card.style.display = '';
                    hasVisibleCards = true;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle no results message
            const noTournamentsDiv = document.querySelector('.no-tournaments');
            const tournamentGrid = document.querySelector('.tournament-grid');

            if (!hasVisibleCards) {
                if (!noTournamentsDiv) {
                    const message = document.createElement('div');
                    message.className = 'no-tournaments';
                    message.innerHTML = `
                        <i class="bi bi-search"></i>
                        <h3>No Tournaments Found</h3>
                        <p>Try adjusting your filters</p>
                    `;
                    tournamentGrid.appendChild(message);
                }
            } else {
                if (noTournamentsDiv) {
                    noTournamentsDiv.remove();
                }
            }
        }

        // Game filter tabs
        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                filterTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentGame = tab.dataset.game;
                filterTournaments();
            });
        });

        // Platform filter
        platformFilter.addEventListener('change', () => {
            currentPlatform = platformFilter.value;
            filterTournaments();
        });

        // Search functionality with debounce
        let searchTimeout;
        tournamentSearch.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchTerm = e.target.value.trim();
                filterTournaments();
            }, 300);
        });

        // Initial state - show all tournaments
        filterTournaments();
    });
    </script>
</body>
</html> 