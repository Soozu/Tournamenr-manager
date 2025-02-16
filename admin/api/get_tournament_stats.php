<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
requireAdmin();

header('Content-Type: application/json');

$game_id = isset($_GET['game_id']) ? $_GET['game_id'] : 'all';

// Base query for tournaments
$base_query = "FROM tournaments t 
               JOIN games g ON t.game_id = g.id 
               WHERE 1=1";

// Add game filter if specific game is selected
if ($game_id !== 'all') {
    $base_query .= " AND g.id = " . intval($game_id);
}

// Get tournament statistics
$stats_query = "SELECT 
    SUM(CASE WHEN t.status = 'ongoing' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed
    " . $base_query;

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get upcoming tournaments
$upcoming_query = "SELECT 
    t.id,
    t.name,
    t.start_date,
    g.icon as game_icon,
    g.name as game_name,
    (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.id) as team_count
    " . $base_query . "
    AND t.status = 'registration'
    ORDER BY t.start_date ASC
    LIMIT 3";

$upcoming_result = $conn->query($upcoming_query);
$upcoming = [];

while ($tournament = $upcoming_result->fetch_assoc()) {
    $upcoming[] = [
        'id' => $tournament['id'],
        'name' => $tournament['name'],
        'start_date' => date('M d, Y', strtotime($tournament['start_date'])),
        'game_icon' => $tournament['game_icon'],
        'game_name' => $tournament['game_name'],
        'team_count' => $tournament['team_count']
    ];
}

echo json_encode([
    'stats' => [
        'active' => (int)$stats['active'],
        'completed' => (int)$stats['completed']
    ],
    'upcoming' => $upcoming
]); 