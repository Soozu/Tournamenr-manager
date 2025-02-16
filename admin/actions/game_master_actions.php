<?php
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $gmId = isset($_POST['gmId']) ? intval($_POST['gmId']) : 0;

    if (!$gmId) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Game Master ID']);
        exit;
    }

    switch ($action) {
        case 'getActivity':
            getActivity($conn, $gmId);
            break;
        case 'getReports':
            getReports($conn, $gmId);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}

function getActivity($conn, $gmId) {
    try {
        // Verify gamemaster exists
        $gm_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'gamemaster'");
        $gm_check->bind_param("i", $gmId);
        $gm_check->execute();
        if ($gm_check->get_result()->num_rows === 0) {
            throw new Exception("Game Master not found");
        }

        // Get recent matches with error handling
        $matches_query = "
            SELECT 
                m.*, 
                t1.team_name as team1_name,
                t2.team_name as team2_name,
                m.status,
                m.match_date,
                COALESCE(TIMESTAMPDIFF(MINUTE, m.start_time, m.end_time), 0) as duration
            FROM matches m
            LEFT JOIN teams t1 ON m.team1_id = t1.id
            LEFT JOIN teams t2 ON m.team2_id = t2.id
            WHERE m.gamemaster_id = ?
            ORDER BY m.match_date DESC
            LIMIT 5";
        
        $stmt = $conn->prepare($matches_query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $gmId);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $recent_matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get activity timeline
        $timeline_query = "
            SELECT 
                'Match Created' as action,
                match_date as date,
                CONCAT(t1.team_name, ' vs ', t2.team_name) as details
            FROM matches m
            LEFT JOIN teams t1 ON m.team1_id = t1.id
            LEFT JOIN teams t2 ON m.team2_id = t2.id
            WHERE m.gamemaster_id = ?
            UNION ALL
            SELECT 
                'Report Filed' as action,
                mr.created_at as date,
                CONCAT('Report type: ', mr.report_type) as details
            FROM match_reports mr
            JOIN matches m ON mr.match_id = m.id
            WHERE m.gamemaster_id = ?
            ORDER BY date DESC
            LIMIT 10";
            
        $stmt = $conn->prepare($timeline_query);
        $stmt->bind_param("ii", $gmId, $gmId);
        $stmt->execute();
        $timeline = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate statistics with error handling
        $stats_query = "
            SELECT 
                COUNT(DISTINCT m.id) as total_matches,
                COALESCE(AVG(TIMESTAMPDIFF(MINUTE, m.start_time, m.end_time)), 0) as avg_duration,
                COUNT(DISTINCT mr.id) as total_reports,
                COALESCE(
                    (COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT m.id), 0)), 
                    0
                ) as completion_rate
            FROM matches m
            LEFT JOIN match_reports mr ON m.id = mr.match_id
            WHERE m.gamemaster_id = ?
            AND m.match_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("i", $gmId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'recent_matches' => $recent_matches,
                'timeline' => $timeline,
                'active_hours' => round($stats['avg_duration'] / 60, 1),
                'response_rate' => round($stats['completion_rate'], 1),
                'avg_match_duration' => round($stats['avg_duration']) . ' minutes',
                'total_matches' => $stats['total_matches'],
                'total_reports' => $stats['total_reports']
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error in getActivity: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to load activity data: ' . $e->getMessage()
        ]);
    }
}

function getReports($conn, $gmId) {
    try {
        $reports_query = "
            SELECT 
                mr.*,
                m.match_date,
                t1.team_name as team1_name,
                t2.team_name as team2_name
            FROM match_reports mr
            JOIN matches m ON mr.match_id = m.id
            LEFT JOIN teams t1 ON m.team1_id = t1.id
            LEFT JOIN teams t2 ON m.team2_id = t2.id
            WHERE m.gamemaster_id = ?
            ORDER BY mr.created_at DESC";
            
        $stmt = $conn->prepare($reports_query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $gmId);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $formatted_reports = array_map(function($report) {
            return [
                'id' => $report['id'],
                'report_type' => $report['report_type'],
                'description' => $report['description'],
                'status' => $report['status'],
                'created_at' => date('Y-m-d H:i', strtotime($report['created_at'])),
                'match_info' => sprintf(
                    '%s vs %s (%s)',
                    $report['team1_name'],
                    $report['team2_name'],
                    date('Y-m-d', strtotime($report['match_date']))
                ),
                'attachments' => $report['attachments']
            ];
        }, $reports);
        
        echo json_encode([
            'status' => 'success',
            'data' => $formatted_reports
        ]);
    } catch (Exception $e) {
        error_log("Error in getReports: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to load reports: ' . $e->getMessage()
        ]);
    }
}
