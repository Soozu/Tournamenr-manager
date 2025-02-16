<?php
session_start();
require_once 'config/database.php';

// Get all active games grouped by platform
$sql = "SELECT * FROM games WHERE status = 'active' ORDER BY platform DESC, name ASC";
$result = $conn->query($sql);

// Organize games by platform
$physicalGames = [];
$onlineGames = [];
while ($game = $result->fetch_assoc()) {
    if ($game['platform'] === 'Physical') {
        $physicalGames[] = $game;
    } else {
        $onlineGames[] = $game;
    }
}

// Get tournament counts for each game
$gameTournamentCounts = [];
$countSql = "SELECT game_id, COUNT(*) as tournament_count FROM tournaments GROUP BY game_id";
$countResult = $conn->query($countSql);
while($row = $countResult->fetch_assoc()) {
    $gameTournamentCounts[$row['game_id']] = $row['tournament_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Categories - CSG Tournament</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/categories.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Categories Header -->
    <div class="categories-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Game Categories</h1>
                    <p class="lead">Explore our diverse selection of competitive gaming titles</p>
                </div>
                <div class="col-lg-6">
                    <div class="categories-stats">
                        <div class="stat-item">
                            <i class="bi bi-controller"></i>
                            <div class="stat-details">
                                <h3><?php echo count($physicalGames) + count($onlineGames); ?></h3>
                                <p>Active Games</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-trophy"></i>
                            <div class="stat-details">
                                <h3><?php echo array_sum($gameTournamentCounts); ?></h3>
                                <p>Total Tournaments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Filter -->
    <div class="container mb-4">
        <div class="platform-filter">
            <button class="filter-btn active" data-platform="all">All Games</button>
            <button class="filter-btn" data-platform="physical">Physical Games</button>
            <button class="filter-btn" data-platform="online">Online Games</button>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="container categories-container">
        <!-- Physical Games Section -->
        <div class="platform-section physical-games" id="physical-games">
            <h2 class="section-title"><i class="bi bi-trophy"></i> Physical Games</h2>
            <div class="row g-4 animate-grid">
                <?php foreach($physicalGames as $game): ?>
                    <div class="col-lg-3 col-md-6">
                        <a href="tournaments.php?game=<?php echo $game['game_code']; ?>" class="category-card-link">
                            <div class="category-card" data-platform="physical">
                                <div class="category-banner" style="background-color: <?php echo $game['color']; ?>">
                                    <img src="images/games/<?php echo $game['icon']; ?>" alt="<?php echo $game['name']; ?>">
                                </div>
                                <div class="category-content">
                                    <h3><?php echo $game['name']; ?></h3>
                                    <div class="category-meta">
                                        <span class="team-size">
                                            <i class="bi bi-people"></i>
                                            <?php echo $game['team_size']; ?>
                                        </span>
                                    </div>
                                    <div class="tournament-text">
                                        <i class="bi bi-trophy"></i>
                                        <span><?php echo $gameTournamentCounts[$game['id']] ?? 0; ?> Tournaments</span>
                                    </div>
                                    <div class="view-tournaments">
                                        <span class="btn btn-primary">View Tournaments</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Online Games Section -->
        <div class="platform-section online-games" id="online-games">
            <h2 class="section-title"><i class="bi bi-controller"></i> Online Games</h2>
            <div class="row g-4 animate-grid">
                <?php foreach($onlineGames as $game): ?>
                    <div class="col-lg-3 col-md-6">
                        <a href="tournaments.php?game=<?php echo $game['game_code']; ?>" class="category-card-link">
                            <div class="category-card" data-platform="online">
                                <div class="category-banner" style="background-color: <?php echo $game['color']; ?>">
                                    <img src="images/games/<?php echo $game['icon']; ?>" alt="<?php echo $game['name']; ?>">
                                </div>
                                <div class="category-content">
                                    <h3><?php echo $game['name']; ?></h3>
                                    <div class="category-meta">
                                        <span class="platform-badge">
                                            <i class="bi bi-<?php echo strtolower($game['platform']) === 'mobile' ? 'phone' : 'pc-display'; ?>"></i>
                                            <?php echo $game['platform']; ?>
                                        </span>
                                        <span class="team-size">
                                            <i class="bi bi-people"></i>
                                            <?php echo $game['team_size']; ?>
                                        </span>
                                    </div>
                                    <div class="tournament-text">
                                        <i class="bi bi-trophy"></i>
                                        <span><?php echo $gameTournamentCounts[$game['id']] ?? 0; ?> Tournaments</span>
                                    </div>
                                    <div class="view-tournaments">
                                        <span class="btn btn-primary">View Tournaments</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const platform = button.dataset.platform;
                const physicalSection = document.getElementById('physical-games');
                const onlineSection = document.getElementById('online-games');

                // Show/hide sections based on filter
                if (platform === 'all') {
                    physicalSection.style.display = 'block';
                    onlineSection.style.display = 'block';
                } else if (platform === 'physical') {
                    physicalSection.style.display = 'block';
                    onlineSection.style.display = 'none';
                } else if (platform === 'online') {
                    physicalSection.style.display = 'none';
                    onlineSection.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html> 