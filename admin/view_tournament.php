<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();
require_once('../vendor/autoload.php'); // For TCPDF
use TCPDF as TCPDF;

// Get tournament ID from URL
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch tournament details with related information
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        g.platform as game_platform,
        COUNT(DISTINCT tt.team_id) as registered_teams,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, ':', u.id)) as gamemasters
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN users u ON tgm.user_id = u.id
    WHERE t.id = ?
    GROUP BY t.id";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header("Location: tournaments.php?error=Tournament not found");
    exit;
}

// Fetch registered teams
$teams_query = "
    SELECT 
        t.*,
        tt.registration_date,
        tt.status as registration_status
    FROM teams t
    JOIN tournament_teams tt ON t.id = tt.team_id
    WHERE tt.tournament_id = ?
    ORDER BY tt.registration_date";

$teams_stmt = $conn->prepare($teams_query);
$teams_stmt->bind_param("i", $tournament_id);
$teams_stmt->execute();
$teams = $teams_stmt->get_result();

// Fetch recent matches
$matches_query = "
    SELECT 
        m.*,
        t1.team_name as team1_name,
        t2.team_name as team2_name,
        u.username as gamemaster_name
    FROM matches m
    LEFT JOIN teams t1 ON m.team1_id = t1.id
    LEFT JOIN teams t2 ON m.team2_id = t2.id
    LEFT JOIN users u ON m.gamemaster_id = u.id
    WHERE m.tournament_id = ?
    ORDER BY m.match_date DESC, m.match_time DESC
    LIMIT 5";

$matches_stmt = $conn->prepare($matches_query);
$matches_stmt->bind_param("i", $tournament_id);
$matches_stmt->execute();
$recent_matches = $matches_stmt->get_result();

function generateApprovalPDF($team, $tournament) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Tournament System');
    $pdf->SetAuthor('Tournament Admin');
    $pdf->SetTitle('Team Registration Approval');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add content
    $pdf->Cell(0, 10, 'TEAM REGISTRATION APPROVAL', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Team Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Team Information:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Team Name: ' . $team['team_name'], 0, 1);
    $pdf->Cell(0, 10, 'Captain: ' . $team['captain_name'], 0, 1);
    
    // Tournament Information
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Tournament Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Tournament: ' . $tournament['name'], 0, 1);
    $pdf->Cell(0, 10, 'Game: ' . $tournament['game_name'], 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('F d, Y', strtotime($tournament['start_date'])), 0, 1);
    
    // Tournament Rules
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Tournament Rules:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->writeHTML($tournament['rules']);
    
    return $pdf->Output('', 'S');
}

function sendApprovalEmail($team, $tournament, $pdfContent) {
    require_once('../vendor/phpmailer/phpmailer/src/PHPMailer.php');
    require_once('../vendor/phpmailer/phpmailer/src/SMTP.php');
    require_once('../vendor/phpmailer/phpmailer/src/Exception.php');
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail_config = require_once('../config/mail.php');
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $mail_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mail_config['smtp_username'];
        $mail->Password = $mail_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mail_config['smtp_port'];
        
        // Recipients
        $mail->setFrom($mail_config['from_address'], $mail_config['from_name']);
        $mail->addAddress($team['captain_email'], $team['captain_name']);
        
        // Attach PDF
        $mail->addStringAttachment($pdfContent, 'tournament_approval.pdf');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Team Registration Approved - ' . $tournament['name'];
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #2c3e50;'>Congratulations!</h2>
                <p>Dear {$team['captain_name']},</p>
                <p>Your team <strong>'{$team['team_name']}'</strong> has been approved for the <strong>{$tournament['name']}</strong>.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Tournament Details:</h3>
                    <ul style='list-style: none; padding: 0;'>
                        <li>📅 Date: " . date('F d, Y', strtotime($tournament['start_date'])) . "</li>
                        <li>🎮 Game: {$tournament['game_name']}</li>
                        <li>🏆 Prize Pool: ₱" . number_format($tournament['prize_pool']) . "</li>
                    </ul>
                </div>
                
                <p>Please find attached:</p>
                <ol>
                    <li>Official Tournament Approval Document</li>
                    <li>Tournament Rules and Guidelines</li>
                </ol>
                
                <p style='color: #e74c3c;'><strong>Important:</strong> Please review all attached documents carefully.</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #7f8c8d; font-size: 0.9em;'>Best regards,<br>Tournament Management Team</p>
                </div>
            </div>
        ";
        
        // Plain text version
        $mail->AltBody = "
            Congratulations!
            
            Your team '{$team['team_name']}' has been approved for the {$tournament['name']}.
            
            Tournament Details:
            - Date: " . date('F d, Y', strtotime($tournament['start_date'])) . "
            - Game: {$tournament['game_name']}
            - Prize Pool: ₱" . number_format($tournament['prize_pool']) . "
            
            Please check the attached documents for the official approval and tournament rules.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

// Update the team status update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'], $_POST['status'])) {
    try {
        $team_id = (int)$_POST['team_id'];
        $new_status = $_POST['status'];
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update team status
        $update_stmt = $conn->prepare("
            UPDATE tournament_teams 
            SET status = ? 
            WHERE team_id = ? AND tournament_id = ?
        ");
        $update_stmt->bind_param("sii", $new_status, $team_id, $tournament_id);
        $update_stmt->execute();
        
        // If status is approved, send email with PDF
        if ($new_status === 'approved') {
            // Get team details with captain information
            $team_query = "
                SELECT t.*, 
                       m.full_name as captain_name, 
                       m.email as captain_email,
                       m.student_id as captain_student_id
                FROM teams t
                JOIN team_members tm ON t.id = tm.team_id AND tm.is_captain = 1
                JOIN members m ON tm.member_id = m.id
                WHERE t.id = ?
            ";
            $team_stmt = $conn->prepare($team_query);
            $team_stmt->bind_param("i", $team_id);
            $team_stmt->execute();
            $team = $team_stmt->get_result()->fetch_assoc();
            
            if (!$team) {
                throw new Exception("Team information not found");
            }
            
            // Generate and send PDF
            $pdfContent = generateApprovalPDF($team, $tournament);
            $emailSent = sendApprovalEmail($team, $tournament, $pdfContent);
            
            if (!$emailSent) {
                throw new Exception("Failed to send approval email");
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Team status updated and notification sent']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tournament - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <link href="css/view_tournament.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-title">
                    <h1>
                        <img src="../images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                             alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                             class="game-icon"
                             onerror="this.src='../images/default-game-icon.png'">
                        <?php echo htmlspecialchars($tournament['name']); ?>
                        <span class="status-badge <?php echo $tournament['status']; ?>">
                            <?php echo ucfirst($tournament['status']); ?>
                        </span>
                    </h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="location.href='edit_tournament.php?id=<?php echo $tournament_id; ?>'">
                        <i class="bi bi-pencil"></i> Edit Tournament
                    </button>
                </div>
            </div>

            <div class="tournament-details">
                <div class="row">
                    <!-- Tournament Overview -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3>Tournament Overview</h3>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Game</label>
                                        <span><?php echo htmlspecialchars($tournament['game_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Platform</label>
                                        <span><?php echo htmlspecialchars($tournament['game_platform']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Team Size</label>
                                        <span><?php echo $tournament['team_size']; ?> vs <?php echo $tournament['team_size']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Prize Pool</label>
                                        <span>₱<?php echo number_format($tournament['prize_pool']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Duration</label>
                                        <span><?php echo date('M d', strtotime($tournament['start_date'])); ?> - <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <label>Registration Deadline</label>
                                        <span><?php echo date('M d, Y', strtotime($tournament['registration_deadline'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Game Masters -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Game Masters</h3>
                            </div>
                            <div class="card-body">
                                <div class="gamemaster-list">
                                    <?php 
                                    if ($tournament['gamemasters']) {
                                        $gamemasters = explode(',', $tournament['gamemasters']);
                                        foreach ($gamemasters as $gm) {
                                            list($username, $id) = explode(':', $gm);
                                            echo "<div class='gamemaster-item'>";
                                            echo "<i class='bi bi-person-badge'></i>";
                                            echo "<span>" . htmlspecialchars($username) . "</span>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<p class='no-data'>No game masters assigned</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Matches -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Recent Matches</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_matches->num_rows > 0): ?>
                                    <div class="matches-list">
                                        <?php while ($match = $recent_matches->fetch_assoc()): ?>
                                            <div class="match-item">
                                                <div class="match-teams">
                                                    <span class="team"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                                    <span class="vs">VS</span>
                                                    <span class="team"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                                </div>
                                                <div class="match-info">
                                                    <span class="date"><?php echo date('M d, H:i', strtotime($match['match_date'] . ' ' . $match['match_time'])); ?></span>
                                                    <span class="status <?php echo $match['status']; ?>"><?php echo ucfirst($match['status']); ?></span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="no-data">No matches scheduled yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tournament Stats -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3>Tournament Stats</h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $tournament['registered_teams']; ?>/<?php echo $tournament['max_teams']; ?></div>
                                    <div class="stat-label">Teams Registered</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['total_matches']; ?></div>
                                    <div class="stat-label">Matches Completed</div>
                                </div>
                            </div>
                        </div>

                        <!-- Registered Teams -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Registered Teams</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($teams->num_rows > 0): ?>
                                    <div class="teams-list">
                                        <?php while ($team = $teams->fetch_assoc()): ?>
                                            <div class="team-item">
                                                <div class="team-card" data-status="<?php echo $team['registration_status']; ?>">
                                                    <div class="team-info">
                                                        <div class="team-logo-wrapper">
                                                            <?php 
                                                            $logo_path = '../images/team-logos/';
                                                            $default_logo = 'default-team-logo.png';
                                                            $team_logo = $team['team_logo'] ?? $default_logo;
                                                            
                                                            // Verify image exists and is valid
                                                            if (!file_exists($logo_path . $team_logo) || !is_file($logo_path . $team_logo)) {
                                                                $team_logo = $default_logo;
                                                            }
                                                            ?>
                                                            <img src="<?php echo $logo_path . htmlspecialchars($team_logo); ?>" 
                                                                 alt="<?php echo htmlspecialchars($team['team_name']); ?>"
                                                                 class="team-logo"
                                                                 onerror="this.src='<?php echo $logo_path . $default_logo; ?>'"
                                                                 loading="lazy">
                                                        </div>
                                                        <div class="team-details">
                                                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                                            <div class="team-meta">
                                                                <span class="captain-name">
                                                                    <i class="bi bi-person-badge"></i>
                                                                    <?php echo htmlspecialchars($team['captain_name']); ?>
                                                                </span>
                                                                <span class="member-count">
                                                                    <i class="bi bi-people"></i>
                                                                    <?php echo $team['member_count']; ?> members
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="team-actions">
                                                        <div class="status-badge <?php echo $team['registration_status']; ?>">
                                                            <?php echo ucfirst($team['registration_status']); ?>
                                                        </div>
                                                        <div class="action-buttons">
                                                            <?php if ($team['registration_status'] !== 'approved'): ?>
                                                                <button class="btn btn-success btn-sm" 
                                                                        data-action="approved"
                                                                        data-team-id="<?php echo $team['id']; ?>">
                                                                    <i class="bi bi-check-circle"></i> Approve
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($team['registration_status'] !== 'pending'): ?>
                                                                <button class="btn btn-warning btn-sm"
                                                                        data-action="pending"
                                                                        data-team-id="<?php echo $team['id']; ?>">
                                                                    <i class="bi bi-clock"></i> Set Pending
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($team['registration_status'] !== 'rejected'): ?>
                                                                <button class="btn btn-danger btn-sm"
                                                                        data-action="rejected"
                                                                        data-team-id="<?php echo $team['id']; ?>">
                                                                    <i class="bi bi-x-circle"></i> Reject
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="no-data">No teams registered yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSidebar = () => {
            document.querySelector('.sidebar').classList.toggle('active');
        };

        // Add toggle button for mobile if it doesn't exist
        if (window.innerWidth <= 768) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'sidebar-toggle btn btn-primary';
            toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
            toggleBtn.onclick = toggleSidebar;
            document.querySelector('.main-content').prepend(toggleBtn);
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const toggleBtn = document.querySelector('.sidebar-toggle');
        if (window.innerWidth <= 768 && !toggleBtn) {
            const newToggleBtn = document.createElement('button');
            newToggleBtn.className = 'sidebar-toggle btn btn-primary';
            newToggleBtn.innerHTML = '<i class="bi bi-list"></i>';
            newToggleBtn.onclick = () => document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').prepend(newToggleBtn);
        } else if (window.innerWidth > 768 && toggleBtn) {
            toggleBtn.remove();
            document.querySelector('.sidebar').classList.remove('active');
        }
    });

    // Define updateTeamStatus function globally
    function updateTeamStatus(teamId, newStatus, tournamentId) {
        // Show loading state
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

        fetch('ajax/update_team_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                team_id: teamId,
                status: newStatus,
                tournament_id: tournamentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert(data.message || 'Team status updated successfully!');
                // Reload the page to show updated status
                location.reload();
            } else {
                alert(data.message || 'Failed to update team status');
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the team status');
            // Reset button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Add event listeners when document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers to all action buttons
        document.querySelectorAll('.action-buttons button').forEach(button => {
            button.addEventListener('click', function() {
                const teamId = this.dataset.teamId;
                const action = this.dataset.action;
                const tournamentId = <?php echo $tournament_id; ?>;

                // Confirm rejection
                if (action === 'reject' && !confirm('Are you sure you want to reject this team?')) {
                    return;
                }

                updateTeamStatus(teamId, action, tournamentId);
            });
        });
    });
    </script>
</body>
</html> 