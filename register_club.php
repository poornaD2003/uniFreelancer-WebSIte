<?php
include 'includes/db.php';
include 'includes/header.php';

$error = "";
$success = "";
$club_name = "";
$username = "";
$club_code = "";
$description = "";
$contribution_rate = 10.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_club'])) {
    $club_name = trim($_POST['club_name']);
    $username = trim($_POST['username']);
    $club_code = trim($_POST['club_code']);
    $password = $_POST['password'];
    $description = trim($_POST['description']);
    $contribution_rate = (float)$_POST['contribution_rate'];

    if (empty($club_name) || empty($username) || empty($club_code) || empty($password)) {
        $error = "Club Name, Username, Secret Access Code, and Password are required.";
    } elseif ($contribution_rate < 0 || $contribution_rate > 100) {
        $error = "Contribution rate must be between 0% and 100%.";
    } else {
        // Check if username is already taken
        $check_user = $conn->prepare("SELECT id FROM clubs WHERE username = ?");
        if ($check_user) {
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $check_user->store_result();
            if ($check_user->num_rows > 0) {
                $error = "This club username is already taken. Please choose a different one.";
                $check_user->close();
            } else {
                $check_user->close();
                
                // Check if club_code is unique
                $check_code = $conn->prepare("SELECT id FROM clubs WHERE club_code = ?");
                if ($check_code) {
                    $check_code->bind_param("s", $club_code);
                    $check_code->execute();
                    $check_code->store_result();
                    
                    if ($check_code->num_rows > 0) {
                        $error = "This Secret Access Code is already in use by another club. Please choose a different code.";
                        $check_code->close();
                    } else {
                        $check_code->close();
                        
                        // Hash the password for security
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        
                        // Insert new club with 'pending' status
                        $insert_query = "INSERT INTO clubs (club_name, username, club_code, password, description, contribution_rate, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                        if ($insert_stmt = $conn->prepare($insert_query)) {
                            $insert_stmt->bind_param("sssssd", $club_name, $username, $club_code, $hashed_password, $description, $contribution_rate);
                            
                            if ($insert_stmt->execute()) {
                                $success = "Club registration submitted successfully! Waiting for administrator approval.";
                                // Reset fields
                                $club_name = $username = $club_code = $description = "";
                                $contribution_rate = 10.00;
                            } else {
                                $error = "Failed to save club registration. Please try again.";
                            }
                            $insert_stmt->close();
                        }
                    }
                }
            }
        }
    }
}
?>

<div class="form-container card fade-in" style="max-width: 600px; margin: 120px auto 40px; padding: 2.5rem; background: #1a202c; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); color: white;">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem; color: #fff;">Register a University Club</h2>
    <p style="color: #a0aec0; margin-bottom: 2rem;">Register your club/society to receive contributions from member students.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; color: #fca5a5;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; color: #6ee7b7;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register_club.php">
        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Club Name</label>
            <input type="text" name="club_name" required placeholder="e.g. Rotaract Club of Colombo" value="<?php echo htmlspecialchars($club_name); ?>" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
        </div>

        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Club Username (For Club Login)</label>
            <input type="text" name="username" required placeholder="e.g. rotaract_colombo" value="<?php echo htmlspecialchars($username); ?>" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
            <small style="color: #718096; display: block; margin-top: 0.25rem;">This username will be used to log into the club dashboard.</small>
        </div>

        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Club Account Password</label>
            <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
        </div>

        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Secret Access Code (For Student Members)</label>
            <input type="text" name="club_code" required placeholder="e.g. ROTARACT2026" value="<?php echo htmlspecialchars($club_code); ?>" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
            <small style="color: #718096; display: block; margin-top: 0.25rem;">Students will need to enter this exact code when joining this club during signup.</small>
        </div>

        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Deduction/Contribution Share (%)</label>
            <input type="number" name="contribution_rate" step="0.01" min="0" max="100" required value="<?php echo htmlspecialchars($contribution_rate); ?>" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
            <small style="color: #718096; display: block; margin-top: 0.25rem;">Percentage of student members' project earnings automatically routed to this club (e.g. 10.00%).</small>
        </div>

        <div class="input-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Club Description</label>
            <textarea name="description" placeholder="Describe the mission or purpose of your club..." style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; min-height: 100px; resize: vertical;"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <button type="submit" name="register_club" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem; border-radius: 8px; font-weight: bold; background: #7c3aed; color: white; border: none; cursor: pointer; transition: background 0.2s;">
            Submit Club Registration
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
