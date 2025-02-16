<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

if (!isset($_GET['match_id'])) {
    echo json_encode(['success' => false, 'message' => 'Match ID is required']);
    exit;
}

$match_id = (int)$_GET['match_id'];

try {
    // Verify the match belongs to a tournament managed by this gamemaster
    $check_query = "
        SELECT tb.id 
        FROM tournament_brackets tb
        INNER JOIN tournaments t ON tb.tournament_id = t.id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE tb.id = ? AND tgm.user_id = ?
        LIMIT 1";

    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $match_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 