<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';       
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error_msg = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 📸 NEW: PROFILE PICTURE UPLOAD LOGIC
    if (isset($_POST['upload_image'])) {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $file = $_FILES['profile_image'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExt, $allowedExtensions)) {
                if ($fileSize < 5000000) { // Max 5MB
                    $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;
                    
                    $uploadDirectory = 'uploads/';
                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0777, true);
                    }
                    
                    $fileDestination = $uploadDirectory . $newFileName;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        $stmtImg = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                        $stmtImg->bind_param("si", $newFileName, $user_id);
                        
                        if ($stmtImg->execute()) {
                            $_SESSION['profile_pic'] = $newFileName;
                            $msg = "Profile picture updated successfully!";
                        } else {
                            $error_msg = "Database update failed.";
                        }
                        $stmtImg->close();
                    } else {
                        $error_msg = "Failed to move uploaded file.";
                    }
                } else {
                    $error_msg = "Your file is too large. Max size is 5MB.";
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
            }
        } else {
            $error_msg = "Please select a valid image file to upload.";
        }
    }
    
    if (isset($_POST['update_client'])) {
        $fullname = trim($_POST['fullname']);
        $b_name = trim($_POST['business_name']);
        $b_address = trim($_POST['business_address']);
        $b_type = trim($_POST['business_type']);
        $b_phone = trim($_POST['business_phone']);
        $b_website = trim($_POST['business_website']);

        $stmt1 = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt1->bind_param("si", $fullname, $user_id);
        $stmt1->execute();
        $stmt1->close();
        
        // Update session fullname dynamically
        $_SESSION['fullname'] = $fullname;
        
        $stmt2 = $conn->prepare("INSERT INTO client_profiles (user_id, business_name, business_address, business_type, business_phone, business_website) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE business_name = ?, business_address = ?, business_type = ?, business_phone = ?, business_website = ?");
        
        $stmt2->bind_param("issssssssss", $user_id, $b_name, $b_address, $b_type, $b_phone, $b_website, $b_name, $b_address, $b_type, $b_phone, $b_website);
        $stmt2->execute();
        $stmt2->close();
        
        $msg = "Business profile updated successfully!";
    }

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

// Format the display profile picture URL
if (!empty($user_data['profile_pic']) && $user_data['profile_pic'] !== 'default.png') {
    $display_pic = '/unilance/uploads/' . basename($user_data['profile_pic']);
} else {
    $display_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
}
?>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/client_dashboard.css">

<div class="dashboard-wrapper">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <i class="ti ti-activity" style="font-size: 1.5rem;"></i> Client Analytics
    </div>
    <ul class="sidebar-menu">
      <li class="sidebar-item">
        <a href="client-dashboard.php"><i class="ti ti-smart-home"></i> Pipeline Hub</a>
      </li>
      <li class="sidebar-item">
        <a href="client-payments.php"><i class="ti ti-receipt"></i> Billing & Payments</a>
      </li>
      <li class="sidebar-item active">
        <a href="clientProfile.php"><i class="ti ti-user-cog"></i> Profile Settings</a>
      </li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="header-section" style="margin-bottom: 2rem;">
      <div>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 6px; color: var(--text);">Client Profile Management</h1>
        <p style="color: var(--muted); font-size: 0.9rem; font-weight: 500;">Manage your personal credentials and company profile metadata.</p>
      </div>
    </div>
    
    <div class="container card fade-in" style="max-width: 750px; margin: 0 auto 40px; padding: 2.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow);">

    
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

    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 2rem;">
        <h3 style="color: var(--primary); align-self: flex-start; margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Profile Picture</h3>
        
        <img src="<?php echo htmlspecialchars($display_pic); ?>" 
             alt="Current Profile Picture" 
             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary, #7c3aed); margin-bottom: 1rem;"
             onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
        
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; width: 100%; max-width: 320px;">
            <input type="file" name="profile_image" accept="image/png, image/jpeg, image/jpg" required style="font-size: 0.9rem; color: var(--text-muted);">
            <button type="submit" name="upload_image" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 0.5rem;">Upload New Image</button>
        </form>
    </div>

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
</div>

<?php include 'includes/footer.php'; ?>