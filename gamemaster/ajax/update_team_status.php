<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$registration_id = $data['registration_id'] ?? 0;
$status = $data['status'] ?? '';

if (!in_array($status, ['pending', 'approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn->begin_transaction();

    // Get current team status
    $current_status_query = "SELECT status FROM tournament_teams WHERE id = ?";
    $stmt = $conn->prepare($current_status_query);
    $stmt->bind_param("i", $registration_id);
    $stmt->execute();
    $current_status = $stmt->get_result()->fetch_object()->status;

    // Update team status
    $update_query = "UPDATE tournament_teams SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $registration_id);
    $stmt->execute();

    // If changing to rejected, remove from tournament
    if ($status === 'rejected') {
        $delete_query = "DELETE FROM tournament_teams WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $registration_id);
        $stmt->execute();
    }

    // Create notification
    $notification_query = "
        INSERT INTO notifications (type, reference_id, message, created_at)
        VALUES (?, ?, ?, NOW())";
    $type = 'team_status_change';
    $message = "Team status changed from $current_status to $status";
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("sis", $type, $registration_id, $message);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 