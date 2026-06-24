<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';
$error = "";
$username = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_club'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT id, club_name, password, status FROM clubs WHERE username = ?";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($club = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $club['password'])) {
                if ($club['status'] === 'pending') {
                    $error = "🔒 Your club account is pending administrator approval. Please wait until verification completes.";
                } elseif ($club['status'] === 'rejected') {
                    $error = "🚫 Your club account request has been rejected by an administrator.";
                } elseif ($club['status'] === 'suspended' || $club['status'] === 'inactive') {
                    $error = "🚫 Your club account has been suspended by an administrator.";
                } else {
                    // Approved: Initialize session variables
                    $_SESSION['club_id'] = $club['id'];
                    $_SESSION['club_name'] = $club['club_name'];
                    $_SESSION['role'] = 'club';
                    
                    header("Location: club_dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Something went wrong. Please try again later.";
    }
}

include 'includes/header.php';
?>

<div class="form-container card fade-in" style="max-width: 500px; margin: 120px auto 40px; padding: 2.5rem;">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem;">Club Login</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Log in to manage your University Club account.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #fca5a5;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login_club.php">
        <div class="input-group">
            <label>Club Username</label>
            <input type="text" name="username" required placeholder="rotaract_colombo" value="<?php echo htmlspecialchars($username); ?>">
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" name="login_club" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
            Login to Club Account
        </button>
    </form>

    <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
        Not registered yet? <a href="register_club.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register Your Club</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
