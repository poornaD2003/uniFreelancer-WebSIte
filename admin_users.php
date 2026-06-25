<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin_users.php — User Management Page
// Lists all registered users with search, stat cards, and moderation actions.
// Admin accounts are protected — they cannot be suspended or rejected.
// ─────────────────────────────────────────────────────────────────────────────
include_once __DIR__ . '/includes/admin_common.php';

// ── HANDLE MODERATION ACTIONS (POST) ────────────────────────────────────────
// Triggered when the admin submits an action button form in the table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = (string)$_POST['action'];
    $id     = (int)$_POST['id'];

    $ok      = false;
    $message = 'Unable to update the user.';

    if ($action === 'approve' || $action === 'restore') {
        // Set status to 'active' — user can now log in
        $ok      = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['active', $id]);
        $message = 'User restored successfully.';
    } elseif ($action === 'suspend') {
        // Set status to 'suspended' — user is blocked from logging in
        $ok      = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
        $message = 'User suspended successfully.';
    } elseif ($action === 'reject') {
        // Permanently delete the user record from the database
        $ok      = admin_post_query($conn, 'DELETE FROM users WHERE id = ?', 'i', [$id]);
        $message = 'User removed successfully.';
    }

    // Flash result and redirect — prevents duplicate form submission on refresh
    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_users.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_users.php');
}

// ── SEARCH / FETCH USERS ─────────────────────────────────────────────────────
// Search is performed against the 'fullname' column using a LIKE query
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, fullname, email, role, status, created_at FROM users WHERE fullname LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';    // % wildcards allow partial name matching
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $all_users_result = $stmt->get_result();
    $all_users        = $all_users_result ? $all_users_result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    // No search — fetch all users, newest registrations first
    $all_users_result = $conn->query("SELECT id, fullname, email, role, status, created_at FROM users ORDER BY created_at DESC");
    $all_users        = $all_users_result ? $all_users_result->fetch_all(MYSQLI_ASSOC) : [];
}

// ── STAT CARD COUNTS ─────────────────────────────────────────────────────────
// Always queried from the full table so stats are correct even when a search filters the table
$stats = [
    'total'     => count($all_users),
    'active'    => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    'pending'   => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'suspended'"),
];

// ── RETRIEVE AND CLEAR FLASH MESSAGE ─────────────────────────────────────────
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']); // Remove after reading so it only appears once

include 'includes/header.php';
?>

<?php
// Inject the 'users' colour theme CSS (indigo/purple palette) and shared admin layout
echo admin_theme_styles('users');
?>

<div class="admin-shell">
    <!-- Page title -->
    <div class="admin-page-header">
        <h1 class="admin-page-title">Registered Users</h1>
    </div>

    <!-- Navigation pill bar — 'users' tab is highlighted as active -->
    <?php echo admin_render_nav('users'); ?>

    <!-- Flash notification banner (success = green, error = red) -->
    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem;
            background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>;
            border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <!-- ── STAT CARDS ──────────────────────────────────────────────────────── -->
    <!-- Total | Active (green) | Pending (orange) | Suspended (red) -->
    <div class="metric-grid">
        <div class="admin-panel metric-card">
            <div class="metric-label">Total Users</div>
            <div class="metric-value"><?php echo $stats['total']; ?></div>
            <div class="metric-note">All registered users</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Active</div>
            <div class="metric-value" style="color: #10b981;"><?php echo $stats['active']; ?></div><!-- green = can log in -->
            <div class="metric-note">Accounts that can log in</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending</div>
            <div class="metric-value" style="color: #f97316;"><?php echo $stats['pending']; ?></div><!-- orange = needs review -->
            <div class="metric-note">Waiting for approval</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Suspended</div>
            <div class="metric-value" style="color: #ef4444;"><?php echo $stats['suspended']; ?></div><!-- red = blocked -->
            <div class="metric-note">Blocked from logging in</div>
        </div>
    </div>

    <!-- ── USERS TABLE ─────────────────────────────────────────────────────── -->
    <div class="section-card admin-panel">
        <div class="section-head" style="flex-wrap: wrap;">
            <h2>All Users</h2>

            <!-- Search form — submits a GET request with ?search=… -->
            <form method="GET" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" name="search" placeholder="Search by name..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    style="padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Search</button>
                <?php if (!empty($_GET['search'])): ?>
                    <!-- Clear button resets the search and shows all users -->
                    <a href="admin_users.php" class="btn"
                        style="padding: 0.4rem 1rem; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Shortcut link to the pending approvals queue -->
            <a href="admin_approve.php">Pending queue</a>
        </div>

        <?php if (empty($all_users)): ?>
            <!-- Empty state when search returns no results or no users exist -->
            <div class="muted-empty">No users found.</div>
        <?php else: ?>
            <!-- Horizontally scrollable wrapper for narrow screens -->
            <div class="table-wrap">
            <table class="data-table">
                <!-- Column headings -->
                <thead><tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <!-- User full name in bold dark text -->
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($user['fullname']); ?></td>

                            <!-- Email address -->
                            <td><?php echo htmlspecialchars($user['email']); ?></td>

                            <!-- Role column: highlight the currently logged-in admin differently -->
                            <td>
                                <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                    <!-- Special badge for the admin who is currently logged in -->
                                    <span class="pill pill-info" style="font-weight: 800; background: rgba(59, 130, 246, 0.12); color: #2563eb;">
                                        <i class="fas fa-user-shield"></i> Admin (You)
                                    </span>
                                <?php else: ?>
                                    <!-- Standard role pill for all other users -->
                                    <span class="pill" style="border: 1px solid rgba(226, 232, 240, 1); background: #ffffff; color: #334155;">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Status pill — colour determined by admin_status_class() -->
                            <td><span class="pill <?php echo admin_status_class('user', $user['status']); ?>">
                                <?php echo htmlspecialchars(admin_status_label('user', $user['status'])); ?>
                            </span></td>

                            <!-- Registration date, no-wrap to prevent awkward line breaks -->
                            <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>

                            <!-- Action buttons — logic varies based on user role and status -->
                            <td>
                                <div class="action-stack">
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <!-- Admin accounts are protected — no moderation actions allowed -->
                                        <span class="pill pill-info"><i class="fas fa-shield-halved"></i> Protected</span>

                                    <?php elseif ($user['status'] === 'pending'): ?>
                                        <!-- Pending users: show Approve, Suspend, and Reject -->
                                        <?php echo admin_action_button('user', (int)$user['id'], 'approve', '✓ Approve'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'suspend', '⊘ Suspend', 'danger'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'reject', '✕ Reject', 'danger'); ?>

                                    <?php else: ?>
                                        <?php if ($user['status'] === 'suspended'): ?>
                                            <!-- Currently suspended: show green Unsuspend button -->
                                            <?php echo admin_suspend_toggle_button((int)$user['id'], true); ?>
                                        <?php else: ?>
                                            <!-- Currently active: show red Suspend button -->
                                            <?php echo admin_suspend_toggle_button((int)$user['id'], false); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>