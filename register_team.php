<?php
session_start();
require_once 'config/database.php';

$tournament_id = isset($_GET['tournament']) ? (int)$_GET['tournament'] : 0;

// Fetch tournament details
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.team_size,
        g.platform
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    WHERE t.id = ? AND t.registration_open = 1";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Team - <?php echo htmlspecialchars($tournament['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/register_team.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="registration-container">
        <div class="container">
            <div class="registration-header">
                <h1>Team Registration</h1>
                <h2><?php echo htmlspecialchars($tournament['name']); ?></h2>
            </div>

            <div class="registration-content">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="registration-form-card">
                            <form id="teamRegistrationForm" action="process_registration.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                                <input type="hidden" name="debug" value="1">
                                
                                <!-- Team Information -->
                                <div class="form-section">
                                    <h3>Team Information</h3>
                                    <div class="mb-3">
                                        <label>Team Name*</label>
                                        <input type="text" name="team_name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Team Logo</label>
                                        <input type="file" name="team_logo" class="form-control" accept="image/*">
                                    </div>
                                </div>

                                <!-- Team Members -->
                                <div class="form-section">
                                    <h3>Team Members</h3>
                                    <p class="text-secondary">Required team size: <?php echo $tournament['team_size']; ?></p>
                                    
                                    <!-- Captain Information -->
                                    <div class="member-form captain-form">
                                        <h4>Team Captain</h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label>Full Name*</label>
                                                    <input type="text" name="captain_name" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label>Student No.*</label>
                                                    <input type="text" name="captain_id" class="form-control" required maxlength="9" pattern="^\d{9}$" title="Please enter a valid 9-digit student number (numbers only)">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label>Email*</label>
                                            <input type="email" name="captain_email" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Course & Section*</label>
                                            <input type="text" name="captain_course_section" class="form-control" required>
                                        </div>
                                    </div>

                                    <!-- Other Members -->
                                    <div id="membersForms">
                                        <?php for($i = 2; $i <= $tournament['team_size']; $i++): ?>
                                        <div class="member-form">
                                            <h4>Member <?php echo $i; ?></h4>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label>Full Name*</label>
                                                        <input type="text" name="member_name[]" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label>Student No.*</label>
                                                        <input type="text" name="member_id[]" class="form-control" required maxlength="9" pattern="^\d{9}$" title="Please enter a valid 9-digit student number (numbers only)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label>Email*</label>
                                                <input type="email" name="member_email[]" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Course & Section*</label>
                                                <input type="text" name="member_course_section[]" class="form-control" required>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle"></i> Submit Registration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Tournament Information -->
                        <div class="info-card">
                            <h3>Tournament Information</h3>
                            <div class="info-item">
                                <label>Game</label>
                                <span><?php echo htmlspecialchars($tournament['game_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Platform</label>
                                <span><?php echo htmlspecialchars($tournament['platform']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Team Size</label>
                                <span><?php echo $tournament['team_size']; ?> players</span>
                            </div>
                            <div class="info-item">
                                <label>Registration Deadline</label>
                                <span><?php echo date('F d, Y', strtotime($tournament['registration_deadline'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Prize Pool</label>
                                <span>₱<?php echo number_format($tournament['prize_pool'], 2); ?></span>
                            </div>
                        </div>

                        <!-- Registration Rules -->
                        <div class="info-card">
                            <h3>Registration Rules</h3>
                            <ul class="rules-list">
                                <li>All team members must have valid membership IDs</li>
                                <li>Team size must match the tournament requirement</li>
                                <li>Each player can only be registered to one team</li>
                                <li>Team captain will be the primary contact person</li>
                                <li>Registration is subject to approval by tournament admins</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('teamRegistrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const required = ['team_name', 'captain_name', 'captain_id'];
            let valid = true;
            
            required.forEach(field => {
                const input = this.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                }
            });
            
            if (valid) {
                this.submit();
            }
        });
    </script>
</body>
</html> 