<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get notifications for the gamemaster
$notifications_query = "
    SELECT n.*, 
           t.name as tournament_name,
           tm.team_name,
           g.name as game_name
    FROM notifications n
    LEFT JOIN tournaments t ON n.reference_id = t.id 
        AND n.type LIKE 'tournament%'
    LEFT JOIN teams tm ON n.reference_id = tm.id 
        AND n.type LIKE 'team%'
    LEFT JOIN games g ON n.reference_id = g.id 
        AND n.type LIKE 'game%'
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    WHERE tgm.user_id = ? 
        OR n.user_id = ? 
        OR (n.user_id IS NULL AND n.type = 'system_alert')
    ORDER BY n.created_at DESC
    LIMIT 50";

$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();

// Mark notifications as read
$mark_read_query = "
    UPDATE notifications n
    LEFT JOIN tournaments t ON n.reference_id = t.id 
        AND n.type LIKE 'tournament%'
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    SET n.is_read = 1 
    WHERE tgm.user_id = ? 
        OR n.user_id = ? 
        OR (n.user_id IS NULL AND n.type = 'system_alert')
    AND n.is_read = 0";

$stmt = $conn->prepare($mark_read_query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/notifications.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-bell"></i> Notifications</h1>
            <p>Stay updated with tournament activities</p>
        </div>

        <div class="notifications-container">
            <?php if ($notifications->num_rows > 0): ?>
                <?php while($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notification-icon">
                            <?php
                            $icon_class = 'bi-bell';
                            switch($notification['type']) {
                                case 'tournament_status_update':
                                    $icon_class = 'bi-trophy';
                                    break;
                                case 'team_registration':
                                    $icon_class = 'bi-people';
                                    break;
                                case 'match_update':
                                    $icon_class = 'bi-controller';
                                    break;
                                case 'system_alert':
                                    $icon_class = 'bi-exclamation-triangle';
                                    break;
                            }
                            ?>
                            <i class="bi <?php echo $icon_class; ?>"></i>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            
                            <?php if ($notification['tournament_name']): ?>
                                <div class="notification-context">
                                    Tournament: <?php echo htmlspecialchars($notification['tournament_name']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="notification-time">
                                <?php 
                                $time = strtotime($notification['created_at']);
                                $now = time();
                                $diff = $now - $time;
                                
                                if ($diff < 60) {
                                    echo "Just now";
                                } elseif ($diff < 3600) {
                                    echo floor($diff/60) . " minutes ago";
                                } elseif ($diff < 86400) {
                                    echo floor($diff/3600) . " hours ago";
                                } else {
                                    echo date('M j, Y g:i A', $time);
                                }
                                ?>
                            </div>
                        </div>

                        <?php if (!$notification['is_read']): ?>
                            <div class="notification-status">
                                <span class="unread-badge"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="bi bi-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 