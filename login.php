<?php
include 'includes/db.php';
include 'includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<div class="form-container card fade-in">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem;">Welcome Back</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Login to your UniLance account.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #fca5a5;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@university.edu">
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
</div>

<?php include 'includes/footer.php'; ?>
