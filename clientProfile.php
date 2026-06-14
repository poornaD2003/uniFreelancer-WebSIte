<?php
include 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_client'])) {
        $fullname = $_POST['fullname'];
        $b_name = $_POST['business_name'];
        $b_address = $_POST['business_address'];
        $b_type = $_POST['business_type'];
        $b_phone = $_POST['business_phone'];
        $b_website = $_POST['business_website'];

        $stmt1 = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt1->execute([$fullname, $user_id]);
        
        $stmt2 = $pdo->prepare("INSERT INTO client_profiles (user_id, business_name, business_address, business_type, business_phone, business_website) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE business_name = ?, business_address = ?, business_type = ?, business_phone = ?, business_website = ?");
        $stmt2->execute([$user_id, $b_name, $b_address, $b_type, $b_phone, $b_website, $b_name, $b_address, $b_type, $b_phone, $b_website]);
        $msg = "Business profile updated successfully!";
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$hashed_pw, $user_id]);
            $msg = "Password updated successfully!";
        } else {
            $msg = "Incorrect current password.";
        }
    }
}

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user_data = $stmtUser->fetch();

$stmtProfile = $pdo->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmtProfile->execute([$user_id]);
$profile_data = $stmtProfile->fetch() ?: ['business_name'=>'', 'business_address'=>'', 'business_type'=>'', 'business_phone'=>'', 'business_website'=>''];
?>

<div class="container card fade-in" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    <h2>Client Profile Management</h2>
    <?php if(!empty($msg)): ?><p style="color: #2ecc71; font-weight: bold;"><?php echo $msg; ?></p><?php endif; ?>

    <form method="POST" style="background: var(--glass-bg); padding: 20px; margin-bottom: 30px; border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--primary);">Business Profile Info</h3>
        <div class="input-group">
            <label>Contact Person Name</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" required>
        </div>
        <div class="input-group">
            <label>Business / Company Name</label>
            <input type="text" name="business_name" value="<?php echo htmlspecialchars($profile_data['business_name']); ?>" required>
        </div>
        <div class="input-group">
            <label>Business Type</label>
            <input type="text" name="business_type" value="<?php echo htmlspecialchars($profile_data['business_type']); ?>" required>
        </div>
        <div class="input-group">
            <label>Business Phone</label>
            <input type="text" name="business_phone" value="<?php echo htmlspecialchars($profile_data['business_phone']); ?>" required>
        </div>
        <div class="input-group">
            <label>Business Website</label>
            <input type="url" name="business_website" value="<?php echo htmlspecialchars($profile_data['business_website']); ?>">
        </div>
        <div class="input-group">
            <label>Business Address</label>
            <textarea name="business_address" style="width:100%; min-height: 80px; padding:12px; border-radius:8px; background: rgba(255,255,255,0.05); color:white; border: 1px solid var(--glass-border);"><?php echo htmlspecialchars($profile_data['business_address']); ?></textarea>
        </div>
        <button type="submit" name="update_client" class="btn btn-primary">Save Business Details</button>
    </form>

    <form method="POST" style="background: var(--glass-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--primary);">Security Settings</h3>
        <div class="input-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-outline">Change Password</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>