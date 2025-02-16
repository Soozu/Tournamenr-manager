<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Game ID not provided']);
    exit;
}

try {
    $game_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("
        SELECT 
            g.*,
            COUNT(DISTINCT t.id) as tournament_count,
            COUNT(DISTINCT tt.team_id) as team_count,
            COUNT(DISTINCT m.id) as match_count
        FROM games g
        LEFT JOIN tournaments t ON g.id = t.game_id
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
        LEFT JOIN matches m ON t.id = m.tournament_id
        WHERE g.id = ?
        GROUP BY g.id
    ");
    
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($game = $result->fetch_assoc()) {
        echo json_encode($game);
    } else {
        echo json_encode(['error' => 'Game not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error fetching game details: ' . $e->getMessage()]);
} 