<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

$tournament_id = $_GET['tournament_id'] ?? 0;

try {
    $query = "SELECT COUNT(*) as count FROM tournament_brackets WHERE tournament_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'brackets_exist' => $result['count'] > 0
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 