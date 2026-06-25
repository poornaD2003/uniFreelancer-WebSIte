<?php
// ─────────────────────────────────────────────────────────────────────────────
// adminProfile.php — Admin Profile View & Edit Page
// Allows the logged-in admin to view their details and update name, email,
// or password. Email uniqueness is checked before saving.
// ─────────────────────────────────────────────────────────────────────────────
include_once __DIR__ . '/includes/admin_common.php';
include 'includes/header.php';

// ── STATE VARIABLES ──────────────────────────────────────────────────────────
$user_id   = $_SESSION['user_id']; // ID of the currently logged-in admin
$msg       = "";                   // Success message shown after a successful update
$error_msg = "";                   // Error message shown when validation or update fails
$edit_mode = false;                // Controls whether the edit form or read-only view is shown

// ── HANDLE PROFILE SAVE (POST) ───────────────────────────────────────────────
// Only runs when the admin submits the edit form (save_profile button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    // Sanitise input — trim whitespace from name and email fields
    $fullname         = trim($_POST['fullname']);
    $email            = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';

    $update_fields_ok = true; // Flag — set false on any validation failure

    // ── VALIDATE: Name and Email must not be empty ───────────────────────────
    if (empty($fullname) || empty($email)) {
        $error_msg        = "Name and Email cannot be empty.";
        $update_fields_ok = false;
        $edit_mode        = true; // Keep the form open so the admin can fix it
    } else {
        // ── VALIDATE: Email must be unique (not already used by another user) ─
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->bind_param("si", $email, $user_id); // Exclude own ID from the check
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            // Another user already owns this email address
            $error_msg        = "This email is already in use by another user.";
            $update_fields_ok = false;
            $edit_mode        = true;
        }
        $stmtCheck->close();
    }

    // ── SAVE CHANGES if validation passed ────────────────────────────────────
    if ($update_fields_ok) {
        if (!empty($current_password) && !empty($new_password)) {
            // ── Password change requested — verify the current password first ─
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($current_password, $user['password'])) {
                // Current password is correct — hash the new password using bcrypt
                $hashed_pw   = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtUpdate  = $conn->prepare("UPDATE users SET fullname = ?, email = ?, password = ? WHERE id = ?");
                $stmtUpdate->bind_param("sssi", $fullname, $email, $hashed_pw, $user_id);
                if ($stmtUpdate->execute()) {
                    $msg = "Profile and password updated successfully!";
                } else {
                    $error_msg = "Failed to update profile details.";
                    $edit_mode = true;
                }
                $stmtUpdate->close();
            } else {
                // Current password did not match — reject the change
                $error_msg = "Incorrect current password.";
                $edit_mode = true;
            }
        } else {
            // ── No password change — update name and email only ───────────────
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

// ── FETCH CURRENT ADMIN DATA ─────────────────────────────────────────────────
// Always re-fetch after possible update so the displayed values are current
$stmtUser = $conn->prepare("SELECT fullname, email, profile_pic FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user_data = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// ── RESOLVE AVATAR URL ───────────────────────────────────────────────────────
// Use the uploaded profile picture if one exists, otherwise fall back to a generic icon
if (isset($user_data['profile_pic']) && !empty($user_data['profile_pic'])) {
    $pure_filename = basename($user_data['profile_pic']); // Strip any path prefix for security
    $avatar_pic    = '/unilance/uploads/' . $pure_filename;
} else {
    // Default placeholder avatar from Flaticon CDN
    $avatar_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
}
?>

<?php
// Inject the 'dashboard' colour theme CSS (green palette) and shared admin layout
echo admin_theme_styles('dashboard');
?>

<style>
    /* ── Profile page layout ── */
    .profile-card {
        padding: 2.5rem;
        max-width: 650px;
        margin: 2rem auto;  /* Centred, constrained width card */
        position: relative;
    }

    /* Avatar section — centred column layout */
    .profile-avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2.5rem;
        text-align: center;
    }

    /* Circular avatar image with a primary-colour border ring */
    .profile-avatar-img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--admin-primary);
        box-shadow: 0 8px 24px rgba(var(--admin-primary-rgb), 0.15);
        margin-bottom: 1rem;
    }

    /* Read-only info fields grid */
    .info-display-grid {
        display: grid;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    /* Individual read-only info item (label + value) */
    .info-item {
        background: rgba(248, 250, 252, 0.6);
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid rgba(226, 232, 240, 0.6);
    }

    /* Small uppercase label above each value */
    .info-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
    }

    /* The actual field value displayed */
    .info-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* Spacing between form fields */
    .profile-form-group { margin-bottom: 1.5rem; }

    /* Form field labels */
    .profile-form-group label {
        display: block;
        font-weight: 700;
        color: #475569;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Text/email/password input fields */
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

    /* Focus ring uses the primary theme colour */
    .profile-form-control:focus {
        outline: none;
        border-color: var(--admin-primary);
        box-shadow: 0 0 0 3px rgba(var(--admin-primary-rgb), 0.15);
    }

    /* Row of action buttons */
    .btn-container { display: flex; gap: 1rem; flex-wrap: wrap; }

    /* Base profile button */
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

    /* Primary button — uses the page gradient (Save Changes, Edit) */
    .profile-btn-primary {
        background: var(--admin-gradient);
        box-shadow: 0 4px 12px rgba(var(--admin-primary-rgb), 0.25);
    }

    .profile-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(var(--admin-primary-rgb), 0.35);
    }

    /* Secondary button — muted grey (Cancel) */
    .profile-btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .profile-btn-secondary:hover { background: #e2e8f0; color: #1e293b; }

    /* Alert banners displayed above the form on success or error */
    .alert-banner {
        padding: 1rem 1.25rem;
        border-radius: 14px;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
    }

    /* Green success banner */
    .alert-success {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    /* Red error banner */
    .alert-error {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* ── Show/hide read-only view vs. edit form ──
       PHP sets the initial display based on $edit_mode.
       JavaScript toggles them client-side without a page reload.
    */
    .view-mode-section { display: <?php echo $edit_mode ? 'none' : 'block'; ?>; }
    .edit-mode-section { display: <?php echo $edit_mode ? 'block' : 'none'; ?>; }
</style>

<div class="admin-shell">
    <!-- Page title -->
    <div class="admin-page-header">
        <h1 class="admin-page-title">Admin Account Details</h1>
    </div>

    <!-- Back to Dashboard navigation link -->
    <div style="margin-bottom: 2rem;">
        <a href="admin_dashboard.php" class="quick-link" style="width: fit-content; padding: 0.6rem 1.2rem; border-radius: 999px;">
            <span><i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Dashboard</span>
        </a>
    </div>

    <!-- Main profile card panel -->
    <div class="admin-panel profile-card">

        <!-- Success alert — shown after a successful profile update -->
        <?php if(!empty($msg)): ?>
            <div class="alert-banner alert-success">
                <i class="fas fa-check-circle" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Error alert — shown when validation fails or the DB update errors -->
        <?php if(!empty($error_msg)): ?>
            <div class="alert-banner alert-error">
                <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Avatar + Name + Role section (always visible) -->
        <div class="profile-avatar-section">
            <!-- Avatar image with onerror fallback in case the file is missing -->
            <img src="<?php echo htmlspecialchars($avatar_pic); ?>" alt="Avatar" class="profile-avatar-img"
                onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
            <h2 style="font-size: 1.4rem; color: #0f172a; margin-bottom: 0.25rem;">
                <?php echo htmlspecialchars($user_data['fullname']); ?>
            </h2>
            <p style="color: #64748b; font-size: 0.88rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                System Administrator
            </p>
        </div>

        <!-- ── READ-ONLY VIEW ──────────────────────────────────────────────── -->
        <!-- Shown by default; hidden when the admin clicks "Edit Profile Info" -->
        <div class="view-mode-section" id="view-section">
            <div class="info-display-grid">
                <!-- Full Name display field -->
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['fullname']); ?></div>
                </div>
                <!-- Email Address display field -->
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>
                <!-- System Role — always 'Administrator' for this page -->
                <div class="info-item">
                    <div class="info-label">System Role</div>
                    <div class="info-value" style="text-transform: capitalize;">Administrator</div>
                </div>
            </div>

            <!-- Button to switch into edit mode (client-side only, no page reload) -->
            <div class="btn-container">
                <button type="button" class="profile-btn profile-btn-primary" onclick="toggleEditMode(true)">
                    <i class="fas fa-edit"></i> Edit Profile Info
                </button>
            </div>
        </div>

        <!-- ── EDIT FORM VIEW ──────────────────────────────────────────────── -->
        <!-- Hidden by default; shown when the admin clicks "Edit Profile Info" -->
        <div class="edit-mode-section" id="edit-section">
            <!-- autocomplete="off" prevents browser autofill conflicting with password fields -->
            <form method="POST" autocomplete="off">

                <!-- Full name input -->
                <div class="profile-form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="profile-form-control"
                        value="<?php echo htmlspecialchars($user_data['fullname']); ?>" required>
                </div>

                <!-- Email address input -->
                <div class="profile-form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="profile-form-control"
                        value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <!-- Password change section divider -->
                <div style="margin: 2rem 0 1rem; border-top: 1px dashed rgba(226, 232, 240, 1); padding-top: 1.5rem;">
                    <h3 style="font-size: 1rem; color: #0f172a; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">Change Password</h3>
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1.2rem;">
                        Leave both password fields blank if you do not wish to change your password.
                    </p>
                </div>

                <!-- Current password — required only if the admin wants to change their password -->
                <div class="profile-form-group">
                    <label for="current_password">Current Password</label>
                    <!-- autocomplete="new-password" prevents autofill of the current password field -->
                    <input type="password" name="current_password" id="current_password"
                        class="profile-form-control" placeholder="••••••••" autocomplete="new-password">
                </div>

                <!-- New password — only saved if both password fields are filled -->
                <div class="profile-form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password"
                        class="profile-form-control" placeholder="••••••••">
                </div>

                <!-- Form action buttons -->
                <div class="btn-container" style="margin-top: 2rem;">
                    <!-- Save button — triggers the POST handler -->
                    <button type="submit" name="save_profile" class="profile-btn profile-btn-primary">
                        <i class="fas fa-check"></i> Save Changes
                    </button>
                    <!-- Cancel button — switches back to the read-only view without saving -->
                    <button type="button" class="profile-btn profile-btn-secondary" onclick="toggleEditMode(false)">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * toggleEditMode — switches between the read-only view and the edit form.
 * Called client-side so no page reload is needed.
 *
 * @param {boolean} showEdit  true  → show edit form, hide read-only view
 *                            false → show read-only view, hide edit form
 */
function toggleEditMode(showEdit) {
    const viewSection = document.getElementById('view-section');
    const editSection = document.getElementById('edit-section');

    if (showEdit) {
        viewSection.style.display = 'none';   // Hide read-only info
        editSection.style.display = 'block';  // Show editable form
    } else {
        viewSection.style.display = 'block';  // Show read-only info
        editSection.style.display = 'none';   // Hide editable form
    }
}
</script>

<?php include 'includes/footer.php'; ?>
