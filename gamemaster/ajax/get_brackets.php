<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

function returnError($message) {
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

$tournament_id = $_GET['tournament_id'] ?? 0;

try {
    // Verify tournament belongs to this gamemaster
    $verify_query = "
        SELECT t.id, t.status, t.brackets_generated
        FROM tournaments t
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE t.id = ? AND tgm.user_id = ?";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    if (!$tournament) {
        returnError("Tournament not found or unauthorized");
    }

    // Fetch bracket matches
    $query = "
        SELECT 
            tse.*,
            t1.team_name as team1_name,
            t1.team_logo as team1_logo,
            t2.team_name as team2_name,
            t2.team_logo as team2_logo
        FROM tournament_single_elimination tse
        LEFT JOIN teams t1 ON tse.team1_id = t1.id
        LEFT JOIN teams t2 ON tse.team2_id = t2.id
        WHERE tse.tournament_id = ?
        ORDER BY tse.round_number ASC, tse.match_order ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $brackets = [];
    while ($row = $result->fetch_assoc()) {
        $brackets[] = [
            'id' => $row['id'],
            'round_number' => $row['round_number'],
            'match_order' => $row['match_order'],
            'team1_id' => $row['team1_id'],
            'team2_id' => $row['team2_id'],
            'team1_name' => $row['team1_name'] ?? 'TBD',
            'team2_name' => $row['team2_name'] ?? 'TBD',
            'team1_logo' => $row['team1_logo'],
            'team2_logo' => $row['team2_logo'],
            'team1_score' => $row['team1_score'],
            'team2_score' => $row['team2_score'],
            'winner_id' => $row['winner_id'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tournament' => $tournament,
        'brackets' => $brackets
    ]);

} catch (Exception $e) {
    returnError($e->getMessage());
} 