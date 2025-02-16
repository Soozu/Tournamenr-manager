<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

$match_id = $_GET['id'] ?? 0;

try {
    // Get match details with team names
    $query = "
        SELECT 
            tb.*,
            t1.team_name as team1_name,
            t2.team_name as team2_name
        FROM tournament_brackets tb
        LEFT JOIN teams t1 ON tb.team1_id = t1.id
        LEFT JOIN teams t2 ON tb.team2_id = t2.id
        WHERE tb.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();

    if (!$match) {
        throw new Exception("Match not found");
    }

    echo json_encode([
        'success' => true,
        'match' => $match
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 