<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['tournamentId'])) {
        throw new Exception('Tournament ID is required');
    }

    $tournamentId = $data['tournamentId'];

    // Start transaction
    $conn->begin_transaction();

    // Delete related records first
    $queries = [
        "DELETE FROM matches WHERE tournament_id = ?",
        "DELETE FROM tournament_teams WHERE tournament_id = ?",
        "DELETE FROM tournaments WHERE id = ?"
    ];

    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $tournamentId);
        if (!$stmt->execute()) {
            throw new Exception('Error deleting tournament data');
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tournament deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 