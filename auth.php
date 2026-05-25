<?php
include 'includes/db.php';
include 'includes/header.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role = $_POST['role'];

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$fullname, $email, $password, $role])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    } elseif (isset($_POST['login'])) {
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
}
?>

<div class="form-container card">
    <div class="auth-tabs" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
        <button id="show-login" class="btn btn-outline" style="flex: 1;">Login</button>
        <button id="show-register" class="btn btn-outline" style="flex: 1;">Register</button>
    </div>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #fca5a5;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #6ee7b7;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="login-form" method="POST" action="auth.php">
        <h2 style="margin-bottom: 1.5rem;">Welcome Back</h2>
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@university.edu">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Login to Account</button>
    </form>

    <!-- Register Form -->
    <form id="register-form" method="POST" action="auth.php" style="display: none;">
        <h2 style="margin-bottom: 1.5rem;">Create Account</h2>
        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="fullname" required placeholder="John Doe">
        </div>
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@university.edu">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <div class="input-group">
            <label>I am a:</label>
            <select name="role" required>
                <option value="student">Student (Freelancer)</option>
                <option value="client">Client (Hiring)</option>
            </select>
        </div>
        <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">Join UniLance</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
