<?php
// Session එකක් දැනටමත් start කරලා නැත්නම් විතරක් start කරන්න
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php'; // මෙතන දැන් තියෙන්නේ $conn සහිත mysqli_connect එකයි
include 'includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // 1. User input පිරිසිදු කර ගැනීම
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 2. MySQLi Prepared Statement එකක් සකස් කිරීම
    $query = "SELECT id, fullname, password, role, status FROM users WHERE email = ?";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        // Parameter එක bind කිරීම ("s" යනු string එකක් බැවින්)
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        // Execute කිරීම
        mysqli_stmt_execute($stmt);
        
        // Result එක ලබා ගැනීම
        $result = mysqli_stmt_get_result($stmt);
        
        // User කෙනෙක් හමු වුනාදැයි බැලීම (Fetch row)
        if ($user = mysqli_fetch_assoc($result)) {
            
            // 3. Password එක Verify කිරීම
            if (password_verify($password, $user['password'])) {
                
                // Admin කෙනෙක් නම් කෙලින්ම යවනවා
                if ($user['role'] === 'admin') {
                    header("Location: admin_approve.php");
                    exit();
                }
                
                // Account Status එක පරීක්ෂා කිරීම
                if ($user['status'] === 'pending') {
                    $error = "🔒 Your account is pending administrator approval. Please wait until evaluation completes.";
                } elseif ($user['status'] === 'inactive') {
                    $error = "🚫 Your account has been suspended by an administrator.";
                } else {
                    // Authorized state ('active') -> Session වලට දත්ත දැමීම
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit();
                }
                
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        
        // Statement එක close කිරීම
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
</div>

<?php include 'includes/footer.php'; ?>