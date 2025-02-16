<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

try {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT id, username, email, status, avatar FROM users WHERE id = ? AND role = 'facilitator'");
    $stmt->execute([$id]);
    $facilitator = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($facilitator);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 