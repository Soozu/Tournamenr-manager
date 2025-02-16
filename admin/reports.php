<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch overall statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM tournaments) as total_tournaments,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'ongoing') as active_tournaments,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'completed') as completed_tournaments,
        (SELECT COUNT(*) FROM matches) as total_matches,
        (SELECT COUNT(*) FROM matches WHERE status = 'completed') as completed_matches,
        (SELECT COUNT(*) FROM teams) as total_teams,
        (SELECT COUNT(*) FROM users WHERE role = 'gamemaster' AND status = 'active') as active_gamemasters
    FROM dual";
$stats = $conn->query($stats_query)->fetch_assoc();

// Fetch tournament activity by month
$activity_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as tournament_count,
        COUNT(DISTINCT CASE WHEN status = 'completed' THEN id END) as completed_count
    FROM tournaments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC";
$activity_result = $conn->query($activity_query);
$activity_data = [];
while ($row = $activity_result->fetch_assoc()) {
    $activity_data[] = $row;
}

// Fetch game distribution
$game_stats_query = "
    SELECT 
        g.name,
        COUNT(DISTINCT t.id) as tournament_count,
        COUNT(DISTINCT tt.team_id) as team_count,
        COUNT(DISTINCT m.id) as match_count
    FROM games g
    LEFT JOIN tournaments t ON g.id = t.game_id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    GROUP BY g.id
    ORDER BY tournament_count DESC";
$game_stats = $conn->query($game_stats_query);

// Fetch gamemaster performance
$gm_performance_query = "
    SELECT 
        u.username,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        COUNT(DISTINCT t.id) as tournaments_managed,
        GROUP_CONCAT(DISTINCT g.name) as games_managed
    FROM users u
    LEFT JOIN matches m ON u.id = m.gamemaster_id
    LEFT JOIN tournaments t ON m.tournament_id = t.id
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    WHERE u.role = 'gamemaster' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY completed_matches DESC";
$gm_performance = $conn->query($gm_performance_query);

// Calculate growth rates
$growth_query = "
    SELECT 
        (SELECT COUNT(*) FROM tournaments 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as new_tournaments,
        (SELECT COUNT(*) FROM teams
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as new_teams,
        (SELECT COUNT(*) FROM matches
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as new_matches
    FROM dual";
$growth = $conn->query($growth_query)->fetch_assoc();

// Prepare data for charts
$chart_labels = [];
$chart_data = [];
foreach ($activity_data as $data) {
    $chart_labels[] = date('M Y', strtotime($data['month']));
    $chart_data[] = $data['tournament_count'];
}

$game_distribution_labels = [];
$game_distribution_data = [];
while ($game = $game_stats->fetch_assoc()) {
    $game_distribution_labels[] = $game['name'];
    $game_distribution_data[] = $game['tournament_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/reports.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="reports-container">
            <div class="content-header">
                <h1><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
                <p>Track your tournament performance and statistics</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Tournaments</div>
                        <div class="stat-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_tournaments']; ?></div>
                    <div class="stat-trend <?php echo $growth['new_tournaments'] > 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="bi bi-arrow-<?php echo $growth['new_tournaments'] > 0 ? 'up' : 'down'; ?>"></i>
                        <span><?php echo $growth['new_tournaments']; ?> new this month</span>
                    </div>
                </div>

                <!-- [Similar stat cards for matches, teams, etc.] -->
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Tournament Activity</h3>
                        <div class="chart-controls">
                            <button class="chart-control-btn active" data-period="weekly">Weekly</button>
                            <button class="chart-control-btn" data-period="monthly">Monthly</button>
                        </div>
                    </div>
                    <canvas id="activityChart"></canvas>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Game Distribution</h3>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="gameDistChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gamemaster Performance Table -->
            <div class="performance-section">
                <h3>Game Master Performance</h3>
                <div class="table-responsive">
                    <table class="performance-table">
                        <thead>
                            <tr>
                                <th>Game Master</th>
                                <th>Matches Completed</th>
                                <th>Tournaments Managed</th>
                                <th>Games</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($gm = $gm_performance->fetch_assoc()): 
                                $performance = $gm['total_matches'] > 0 ? 
                                    ($gm['completed_matches'] / $gm['total_matches']) * 100 : 0;
                                
                                $performance_class = '';
                                if ($performance >= 80) {
                                    $performance_class = 'performance-high';
                                } elseif ($performance >= 50) {
                                    $performance_class = 'performance-medium';
                                } else {
                                    $performance_class = 'performance-low';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="gm-info">
                                            <?php echo htmlspecialchars($gm['username'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $gm['completed_matches']; ?>/<?php echo $gm['total_matches']; ?>
                                    </td>
                                    <td><?php echo $gm['tournaments_managed']; ?></td>
                                    <td title="<?php echo htmlspecialchars($gm['games_managed'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($gm['games_managed'] ?? 'No games assigned'); ?>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo $performance; ?>%"></div>
                                        </div>
                                        <span class="<?php echo $performance_class; ?>">
                                            <?php echo round($performance); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart data
        const monthlyData = {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Tournaments',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#4361ee',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(67, 97, 238, 0.1)'
            }]
        };

        // Weekly data calculation (example - adjust according to your needs)
        const weeklyLabels = [];
        const weeklyData = [];
        <?php 
        // Calculate weekly data from monthly data
        foreach ($activity_data as $data) {
            $month = strtotime($data['month']);
            $weeks = floor(date('t', $month) / 7);
            $weekly_avg = $data['tournament_count'] / $weeks;
            for ($i = 1; $i <= $weeks; $i++) {
                echo "weeklyLabels.push('Week " . $i . " " . date('M', $month) . "');\n";
                echo "weeklyData.push(" . round($weekly_avg) . ");\n";
            }
        }
        ?>

        const weeklyChartData = {
            labels: weeklyLabels,
            datasets: [{
                label: 'Tournaments',
                data: weeklyData,
                borderColor: '#4361ee',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(67, 97, 238, 0.1)'
            }]
        };

        // Chart configuration
        const chartConfig = {
            type: 'line',
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        };

        // Initialize activity chart
        const activityChart = new Chart(
            document.getElementById('activityChart'),
            {
                ...chartConfig,
                data: monthlyData
            }
        );

        // Add event listeners to chart control buttons
        document.querySelectorAll('.chart-control-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.chart-control-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update chart data based on selected period
                const period = this.dataset.period;
                if (period === 'weekly') {
                    activityChart.data = weeklyChartData;
                } else {
                    activityChart.data = monthlyData;
                }
                
                activityChart.update();
            });
        });

        // Initialize game distribution chart
        const gameDistChart = new Chart(document.getElementById('gameDistChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($game_distribution_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($game_distribution_data); ?>,
                    backgroundColor: [
                        'rgba(67, 97, 238, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(99, 102, 241, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#94a3b8',
                            boxWidth: 12,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                cutout: '60%',
                layout: {
                    padding: 20
                }
            }
        });
    </script>
</body>
</html> 