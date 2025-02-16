<?php
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tournament_id = intval($data['tournament_id']);

// Validate input
if (!$tournament_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid tournament ID']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update or insert tournament settings
    $stmt = $conn->prepare("
        INSERT INTO tournament_settings 
            (tournament_id, registration_open, team_size, auto_assign_gm, match_duration)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            registration_open = VALUES(registration_open),
            team_size = VALUES(team_size),
            auto_assign_gm = VALUES(auto_assign_gm),
            match_duration = VALUES(match_duration)
    ");

    $registration_open = $data['registration_open'] ?? true;
    $team_size = intval($data['team_size'] ?? 5);
    $auto_assign_gm = $data['auto_assign_gm'] ?? false;
    $match_duration = intval($data['match_duration'] ?? 60);

    $stmt->bind_param("iiiii", 
        $tournament_id, 
        $registration_open, 
        $team_size, 
        $auto_assign_gm, 
        $match_duration
    );
    
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save settings',
        'message' => $e->getMessage()
    ]);
} 