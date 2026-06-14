<?php
// 1. Session start කිරීම
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database & Header ඇතුළත් කිරීම
include 'includes/db.php';       // MySQLi connection ($conn)
include 'includes/header.php';

// 3. Session ආරක්ෂාව පරීක්ෂාව (Role එක client ද බලනවා)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error_msg = ""; 

// 4. Form Submissions හැසිරවීම (MySQLi Prepared Statements)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Business Profile Info Update කිරීම
    if (isset($_POST['update_client'])) {
        $fullname = trim($_POST['fullname']);
        $b_name = trim($_POST['business_name']);
        $b_address = trim($_POST['business_address']);
        $b_type = trim($_POST['business_type']);
        $b_phone = trim($_POST['business_phone']);
        $b_website = trim($_POST['business_website']);

        // 1. users table එක update කිරීම
        $stmt1 = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt1->bind_param("si", $fullname, $user_id);
        $stmt1->execute();
        $stmt1->close();
        
        // 2. client_profiles table එකට Upsert කිරීම
        $stmt2 = $conn->prepare("INSERT INTO client_profiles (user_id, business_name, business_address, business_type, business_phone, business_website) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE business_name = ?, business_address = ?, business_type = ?, business_phone = ?, business_website = ?");
        
        $stmt2->bind_param("issssssssss", $user_id, $b_name, $b_address, $b_type, $b_phone, $b_website, $b_name, $b_address, $b_type, $b_phone, $b_website);
        $stmt2->execute();
        $stmt2->close();
        
        $msg = "Business profile updated successfully!";
    }

    // Password වෙනස් කිරීම
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $hashed_pw, $user_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $msg = "Password updated successfully!";
        } else {
            $error_msg = "Incorrect current password.";
        }
    }
}

// 5. දැනට පවතින දත්ත Fetch කිරීම (Inputs පිරවීමට)
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user_data = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$stmtProfile = $conn->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmtProfile->bind_param("i", $user_id);
$stmtProfile->execute();
$profile_data = $stmtProfile->get_result()->fetch_assoc();
$stmtProfile->close();

if (!$profile_data) {
    $profile_data = ['business_name'=>'', 'business_address'=>'', 'business_type'=>'', 'business_phone'=>'', 'business_website'=>''];
}
?>

<div class="container card fade-in" style="max-width: 750px; margin: 140px auto 40px; padding: 2.5rem;">
    <h2 style="margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">Client Profile Management</h2>
    
    <?php if(!empty($msg)): ?>
        <div class="success-badge" style="display: block; width: 100%; padding: 0.6rem 1rem; margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div class="success-badge" style="display: block; width: 100%; padding: 0.6rem 1rem; margin-bottom: 1.5rem; color: #ef4444; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2);">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Business Profile Info</h3>
        
        <div class="input-group">
            <label>Contact Person Name</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>" required>
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
            <textarea name="business_address" style="min-height: 90px; resize: vertical;"><?php echo htmlspecialchars($profile_data['business_address']); ?></textarea>
        </div>
        
        <button type="submit" name="update_client" class="btn btn-primary">Save Business Details</button>
    </form>

    <form method="POST">
        <h3 style="color: var(--primary); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Security Settings</h3>
        
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