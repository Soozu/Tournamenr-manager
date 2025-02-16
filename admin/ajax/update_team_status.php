<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
require_once('../../vendor/autoload.php'); // For TCPDF
use TCPDF as TCPDF;
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['team_id'], $data['status'], $data['tournament_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$team_id = (int)$data['team_id'];
$new_status = $data['status'];
$tournament_id = (int)$data['tournament_id'];

// Validate status values
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid status. Allowed values are: ' . implode(', ', $allowed_statuses)
    ]);
    exit;
}

// Add at the top after getting the data
error_log("Received request: " . print_r($data, true));

// Add the required functions
function generateApprovalPDF($team, $tournament) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Tournament System');
    $pdf->SetAuthor('Tournament Admin');
    $pdf->SetTitle('Official Tournament Registration Approval');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // School Logo
    $logo_path = '../../images/logo/logo.png';
    $pdf->Image($logo_path, 95, 15, 20, 20, 'PNG'); // Reduced size and adjusted position
    
    // School Name
    $pdf->SetFont('helvetica', 'B', 14); // Reduced font size
    $pdf->Ln(22);
    $pdf->Cell(0, 8, 'CAVITE STATE UNIVERSITY', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12); // Reduced font size
    $pdf->Cell(0, 8, 'IMUS CAMPUS', 0, 1, 'C');
    
    // Title
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 12); // Reduced font size
    $pdf->Cell(0, 8, 'OFFICIAL TOURNAMENT REGISTRATION', 0, 1, 'C');
    $pdf->Cell(0, 8, 'APPROVAL CERTIFICATE', 0, 1, 'C');
    
    // Date and Reference
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Date: ' . date('F d, Y'), 0, 1, 'R');
    $ref_number = 'REF-' . date('Ymd') . '-' . str_pad($team['id'], 4, '0', STR_PAD_LEFT);
    $pdf->Cell(0, 5, 'Reference Number: ' . $ref_number, 0, 1, 'R');
    
    // Content
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(2);
    $pdf->WriteHTML("
        <p>This document certifies that the team registration for the following tournament has been officially approved:</p>
        
        <h3 style='font-size: 11pt; margin: 5px 0;'>Tournament Information</h3>
        <table cellpadding='3'>
            <tr>
                <td width='130'><strong>Tournament Name:</strong></td>
                <td>" . $tournament['name'] . "</td>
            </tr>
            <tr>
                <td><strong>Game:</strong></td>
                <td>" . $tournament['game_name'] . "</td>
            </tr>
            <tr>
                <td><strong>Schedule:</strong></td>
                <td>" . date('F d, Y', strtotime($tournament['start_date'])) . " - " . date('F d, Y', strtotime($tournament['end_date'])) . "</td>
            </tr>
            <tr>
                <td><strong>Prize Pool:</strong></td>
                <td>₱" . number_format($tournament['prize_pool']) . "</td>
            </tr>
        </table>

        <h3 style='font-size: 11pt; margin: 5px 0;'>Team Information</h3>
        <table cellpadding='3'>
            <tr>
                <td width='130'><strong>Team Name:</strong></td>
                <td>" . $team['team_name'] . "</td>
            </tr>
            <tr>
                <td><strong>Team Captain:</strong></td>
                <td>" . $team['captain_name'] . "</td>
            </tr>
            <tr>
                <td><strong>Student ID:</strong></td>
                <td>" . $team['captain_student_id'] . "</td>
            </tr>
        </table>
        
        <p style='margin-top: 5px;'><strong>Important Notes:</strong></p>
        <ul style='margin: 0; padding-left: 15px;'>
            <li>This approval is subject to compliance with all tournament rules and regulations.</li>
            <li>Team members must present valid student IDs during the tournament.</li>
            <li>Any violation of tournament rules may result in disqualification.</li>
        </ul>
    ");
    
    // Signature Section
    $pdf->Ln(5);
    $pdf->Cell(0, 5, 'Approved by:', 0, 1, 'L');
    
    // Add signature lines
    $pdf->Ln(10);
    $pdf->Cell(85, 0, '', 'B', 0, 'C');
    $pdf->Cell(20, 0, '', 0, 0, 'C');
    $pdf->Cell(85, 0, '', 'B', 1, 'C');
    
    $pdf->Cell(85, 5, 'Tournament Director', 0, 0, 'C');
    $pdf->Cell(20, 5, '', 0, 0, 'C');
    $pdf->Cell(85, 5, 'School Sports Coordinator', 0, 1, 'C');
    
    // Footer text
    $pdf->SetY(-25);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Cavite State University - Imus Campus', 0, 1, 'C');
    $pdf->Cell(0, 5, 'This is an official document of the Tournament Management System.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Any alterations to this document will make it invalid.', 0, 1, 'C');
    
    return $pdf->Output('', 'S');
}

function sendApprovalEmail($team, $tournament, $pdfContent) {
    require_once('../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
    require_once('../../vendor/phpmailer/phpmailer/src/SMTP.php');
    require_once('../../vendor/phpmailer/phpmailer/src/Exception.php');
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail_config = require('../../config/mail.php');
    
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
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    $conn->begin_transaction();

    // Add before executing the update query
    error_log("Executing update with status: $new_status, team_id: $team_id, tournament_id: $tournament_id");

    // Update team status
    $update_stmt = $conn->prepare("
        UPDATE tournament_teams 
        SET status = ? 
        WHERE team_id = ? AND tournament_id = ?
    ");
    $update_stmt->bind_param("sii", $new_status, $team_id, $tournament_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception("Failed to update team status. Team not found.");
    }

    // Add after the update
    error_log("Affected rows: " . $update_stmt->affected_rows);

    // If status is approved, update the team's tournament_id
    if ($new_status === 'approved') {
        $update_team_query = "
            UPDATE teams 
            SET tournament_id = ? 
            WHERE id = ?";
        $stmt = $conn->prepare($update_team_query);
        $stmt->bind_param("ii", $tournament_id, $team_id);
        $stmt->execute();
    }

    // If status is rejected or pending, remove tournament_id
    if ($new_status === 'rejected' || $new_status === 'pending') {
        $update_team_query = "
            UPDATE teams 
            SET tournament_id = NULL 
            WHERE id = ?";
        $stmt = $conn->prepare($update_team_query);
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
    }

    // Check if all teams are approved
    $check_teams_query = "
        SELECT 
            COUNT(*) as total_teams,
            SUM(CASE WHEN tt.status = 'approved' THEN 1 ELSE 0 END) as approved_teams,
            t.max_teams
        FROM tournament_teams tt
        JOIN tournaments t ON tt.tournament_id = t.id
        WHERE tt.tournament_id = ?
        GROUP BY t.id";
    
    $stmt = $conn->prepare($check_teams_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // If all registered teams are approved and we've reached max_teams
    if ($result && $result['approved_teams'] == $result['max_teams']) {
        // Update tournament status to active
        $update_tournament_query = "
            UPDATE tournaments 
            SET status = 'active' 
            WHERE id = ? AND status = 'registration'";
        $stmt = $conn->prepare($update_tournament_query);
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();

        // Create notification for tournament activation
        $notification_query = "
            INSERT INTO notifications (
                type, 
                reference_id, 
                message, 
                created_at
            ) VALUES (?, ?, ?, NOW())";
        
        $type = 'tournament_status_update';
        $message = "Tournament is now active with all teams approved";
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("sis", $type, $tournament_id, $message);
        $stmt->execute();
    }

    // Create notification for team status update
    $notification_query = "
        INSERT INTO notifications (
            type, 
            reference_id, 
            message, 
            created_at
        ) VALUES (?, ?, ?, NOW())";
    
    $type = 'team_status_update';
    $message = "Team status updated to " . ucfirst($new_status);
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("sis", $type, $team_id, $message);
    $stmt->execute();

    // If status is approved, send email with PDF
    if ($new_status === 'approved') {
        // Get team and tournament details
        $team_query = "
            SELECT t.*, 
                   m.full_name as captain_name, 
                   m.membership_id as captain_student_id,
                   m.email as captain_email
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
        
        // Get tournament details
        $tournament_query = "
            SELECT t.*, g.name as game_name
            FROM tournaments t
            JOIN games g ON t.game_id = g.id
            WHERE t.id = ?
        ";
        $tourn_stmt = $conn->prepare($tournament_query);
        $tourn_stmt->bind_param("i", $tournament_id);
        $tourn_stmt->execute();
        $tournament = $tourn_stmt->get_result()->fetch_assoc();
        
        // Generate and send PDF
        $pdfContent = generateApprovalPDF($team, $tournament);
        $emailSent = sendApprovalEmail($team, $tournament, $pdfContent);
        
        if (!$emailSent) {
            throw new Exception("Failed to send approval email");
        }
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'tournament_activated' => ($result['approved_teams'] == $result['max_teams']),
        'message' => $new_status === 'approved' ? 
            'Team approved and notification sent!' : 
            'Team status updated successfully!'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in update_team_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 