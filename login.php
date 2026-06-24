<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';
include 'includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT id, fullname, password, role, profile_pic,status FROM users WHERE email = ?";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            
            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_pic'] = $user['profile_pic'];
                
                if ($user['role'] === 'admin') {
                    header("Location:admin_dashboard.php");
                    exit();
                }
                
                if ($user['status'] === 'pending') {
                    $error = "🔒 Your account is pending administrator approval. Please wait until evaluation completes.";
                    unset($_SESSION['user_id']);
                    session_destroy();
                } elseif ($user['status'] === 'inactive') {
                    $error = "🚫 Your account has been suspended by an administrator.";
                    session_destroy();
                } else {
                    header("Location: student_freelancer_site.php");
                    exit();
                }
                
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = "Something went wrong. Please try again later.";
    }
}
?>

<div class="form-container card fade-in">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem;">Welcome Back</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Login to your UniLance account.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #fca5a5;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@university.edu" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" name="login" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Login to Account</button>
    </form>

    <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
        Don't have an account? <a href="register.php" style="color: var(--primary);">Register Now</a>
    </p>
    <p style="margin-top: 0.5rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
        Are you a University Club? <a href="register_club.php" style="color: var(--primary); text-decoration: none;">Register Club</a> | <a href="login_club.php" style="color: var(--primary); text-decoration: none;">Club Login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
