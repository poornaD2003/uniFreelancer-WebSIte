<?php
include_once __DIR__ . '/includes/admin_common.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$msg = "";
$error_msg = "";
$edit_mode = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $update_fields_ok = true;
    
    if (empty($fullname) || empty($email)) {
        $error_msg = "Name and Email cannot be empty.";
        $update_fields_ok = false;
        $edit_mode = true;
    } else {
        // Email unique validation
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->bind_param("si", $email, $user_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        
        if ($stmtCheck->num_rows > 0) {
            $error_msg = "This email is already in use by another user.";
            $update_fields_ok = false;
            $edit_mode = true;
        }
        $stmtCheck->close();
    }

    if ($update_fields_ok) {
        // Handle password change if requested
        if (!empty($current_password) && !empty($new_password)) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtUpdate = $conn->prepare("UPDATE users SET fullname = ?, email = ?, password = ? WHERE id = ?");
                $stmtUpdate->bind_param("sssi", $fullname, $email, $hashed_pw, $user_id);
                if ($stmtUpdate->execute()) {
                    $msg = "Profile and password updated successfully!";
                } else {
                    $error_msg = "Failed to update profile details.";
                    $edit_mode = true;
                }
                $stmtUpdate->close();
            } else {
                $error_msg = "Incorrect current password.";
                $edit_mode = true;
            }
        } else {
            // Update profile details only
            $stmtUpdate = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $stmtUpdate->bind_param("ssi", $fullname, $email, $user_id);
            if ($stmtUpdate->execute()) {
                $msg = "Profile updated successfully!";
            } else {
                $error_msg = "Failed to update profile details.";
                $edit_mode = true;
            }
            $stmtUpdate->close();
        }
    }
}

// Fetch current user data
$stmtUser = $conn->prepare("SELECT fullname, email, profile_pic FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user_data = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (isset($user_data['profile_pic']) && !empty($user_data['profile_pic'])) {
    $pure_filename = basename($user_data['profile_pic']); 
    $avatar_pic = '/unilance/uploads/' . $pure_filename;
} else {
    $avatar_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
}
?>

<?php echo admin_theme_styles('dashboard'); ?>

<style>
    .profile-card {
        padding: 2.5rem;
        max-width: 650px;
        margin: 2rem auto;
        position: relative;
    }
    
    .profile-avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2.5rem;
        text-align: center;
    }

    .profile-avatar-img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--admin-primary);
        box-shadow: 0 8px 24px rgba(var(--admin-primary-rgb), 0.15);
        margin-bottom: 1rem;
    }

    .info-display-grid {
        display: grid;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .info-item {
        background: rgba(248, 250, 252, 0.6);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid rgba(226, 232, 240, 0.6);
    }

    .info-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
    }

    .info-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
    }

    .profile-form-group {
        margin-bottom: 1.5rem;
    }

    .profile-form-group label {
        display: block;
        font-weight: 700;
        color: #475569;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .profile-form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 1);
        border-radius: 12px;
        color: #1e293b;
        transition: all 0.2s ease;
    }

    .profile-form-control:focus {
        outline: none;
        border-color: var(--admin-primary);
        box-shadow: 0 0 0 3px rgba(var(--admin-primary-rgb), 0.15);
    }

    .btn-container {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .profile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #ffffff;
        border: none;
        font-weight: 700;
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.92rem;
    }

    .profile-btn-primary {
        background: var(--admin-gradient);
        box-shadow: 0 4px 12px rgba(var(--admin-primary-rgb), 0.25);
    }

    .profile-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(var(--admin-primary-rgb), 0.35);
    }

    .profile-btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .profile-btn-secondary:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .alert-banner {
        padding: 1rem 1.25rem;
        border-radius: 14px;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Tab show/hide utility styles */
    .view-mode-section {
        display: <?php echo $edit_mode ? 'none' : 'block'; ?>;
    }

    .edit-mode-section {
        display: <?php echo $edit_mode ? 'block' : 'none'; ?>;
    }
</style>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Admin Account Details</h1>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <a href="admin_dashboard.php" class="quick-link" style="width: fit-content; padding: 0.6rem 1.2rem; border-radius: 999px;">
            <span><i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Dashboard</span>
        </a>
    </div>

    <div class="admin-panel profile-card">
        <?php if(!empty($msg)): ?>
            <div class="alert-banner alert-success">
                <i class="fas fa-check-circle" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert-banner alert-error">
                <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="profile-avatar-section">
            <img src="<?php echo htmlspecialchars($avatar_pic); ?>" alt="Avatar" class="profile-avatar-img" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
            <h2 style="font-size: 1.4rem; color: #0f172a; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($user_data['fullname']); ?></h2>
            <p style="color: #64748b; font-size: 0.88rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">System Administrator</p>
        </div>

        <!-- READ ONLY VIEW -->
        <div class="view-mode-section" id="view-section">
            <div class="info-display-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['fullname']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">System Role</div>
                    <div class="info-value" style="text-transform: capitalize;">Administrator</div>
                </div>
            </div>
            
            <div class="btn-container">
                <button type="button" class="profile-btn profile-btn-primary" onclick="toggleEditMode(true)">
                    <i class="fas fa-edit"></i> Edit Profile Info
                </button>
            </div>
        </div>

        <!-- EDIT FORM VIEW -->
        <div class="edit-mode-section" id="edit-section">
            <form method="POST" autocomplete="off">
                <div class="profile-form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="profile-form-control" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" required>
                </div>

                <div class="profile-form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="profile-form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <div style="margin: 2rem 0 1rem; border-top: 1px dashed rgba(226, 232, 240, 1); padding-top: 1.5rem;">
                    <h3 style="font-size: 1rem; color: #0f172a; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">Change Password</h3>
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1.2rem;">Leave both password fields blank if you do not wish to change your password.</p>
                </div>

                <div class="profile-form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="profile-form-control" placeholder="••••••••" autocomplete="new-password">
                </div>

                <div class="profile-form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="profile-form-control" placeholder="••••••••">
                </div>

                <div class="btn-container" style="margin-top: 2rem;">
                    <button type="submit" name="save_profile" class="profile-btn profile-btn-primary">
                        <i class="fas fa-check"></i> Save Changes
                    </button>
                    <button type="button" class="profile-btn profile-btn-secondary" onclick="toggleEditMode(false)">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleEditMode(showEdit) {
    const viewSection = document.getElementById('view-section');
    const editSection = document.getElementById('edit-section');
    
    if (showEdit) {
        viewSection.style.display = 'none';
        editSection.style.display = 'block';
    } else {
        viewSection.style.display = 'block';
        editSection.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
