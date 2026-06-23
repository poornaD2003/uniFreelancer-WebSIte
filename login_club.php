<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';
include 'includes/header.php';

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
?>

<div class="form-container card fade-in" style="max-width: 500px; margin: 120px auto 40px; padding: 2.5rem; background: #1a202c; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); color: white;">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem; color: #fff;">Club Login</h2>
    <p style="color: #a0aec0; margin-bottom: 2rem;">Log in to manage your University Club account.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; color: #fca5a5;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login_club.php">
        <div class="input-group" style="margin-bottom: 1.25rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Club Username</label>
            <input type="text" name="username" required placeholder="rotaract_colombo" value="<?php echo htmlspecialchars($username); ?>" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
        </div>

        <div class="input-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e0; font-weight: 600;">Password</label>
            <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
        </div>

        <button type="submit" name="login_club" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem; border-radius: 8px; font-weight: bold; background: #7c3aed; color: white; border: none; cursor: pointer; transition: background 0.2s;">
            Login to Club Account
        </button>
    </form>

    <p style="margin-top: 2rem; text-align: center; color: #a0aec0;">
        Not registered yet? <a href="register_club.php" style="color: #7c3aed; font-weight: 600; text-decoration: none;">Register Your Club</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
