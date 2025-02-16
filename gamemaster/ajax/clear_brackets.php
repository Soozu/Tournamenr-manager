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

$tournament_id = $_POST['tournament_id'] ?? 0;

try {
    $conn->begin_transaction();

    // Verify tournament belongs to gamemaster and is not completed
    $verify_query = "
        SELECT t.id, t.status 
        FROM tournaments t
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE t.id = ? 
        AND tgm.user_id = ?
        AND t.status = 'ongoing'";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Tournament not found or cannot be cleared");
    }

    // Clear all brackets
    $clear_query = "DELETE FROM tournament_single_elimination WHERE tournament_id = ?";
    $stmt = $conn->prepare($clear_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();

    // Reset tournament status
    $update_query = "
        UPDATE tournaments 
        SET status = 'active',
            brackets_generated = 0,
            winner_id = NULL,
            completed_at = NULL
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();

    // Add notification
    $notification_query = "
        INSERT INTO notifications (type, reference_id, message, user_id)
        VALUES ('tournament_brackets_cleared', ?, 'Tournament brackets have been cleared', ?)";
    
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Brackets cleared successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    returnError($e->getMessage());
} 