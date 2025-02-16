<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Get tournament ID from URL
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Bracket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/bracket.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="bracket-container">
                <h2 class="bracket-title">TOURNAMENT BRACKET</h2>
                <div class="status-badge">No Active Tournament</div>
                
                <div class="tournament-bracket">
                    <!-- Round 1 - Quarter Finals Left -->
                    <div class="round">
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                    </div>

                    <!-- Round 2 - Semi Finals Left -->
                    <div class="round">
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                    </div>

                    <!-- Round 3 - Finals -->
                    <div class="round">
                        <div class="trophy">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                    </div>

                    <!-- Round 4 - Semi Finals Right -->
                    <div class="round">
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                    </div>

                    <!-- Round 5 - Quarter Finals Right -->
                    <div class="round">
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                        <div class="match">
                            <div class="match-pair">
                                <div class="team">TBD</div>
                                <div class="team">TBD</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/bracket.js"></script>
</body>
</html> 