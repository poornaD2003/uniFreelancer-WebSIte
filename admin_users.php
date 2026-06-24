<?php
include_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = (string)$_POST['action'];
    $id = (int)$_POST['id'];

    $ok = false;
    $message = 'Unable to update the user.';

    if ($action === 'approve' || $action === 'restore') {
        $ok = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['active', $id]);
        $message = 'User restored successfully.';
    } elseif ($action === 'suspend') {
        $ok = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['suspend', $id]);
        $message = 'User suspended successfully.';
    } elseif ($action === 'reject') {
        $ok = admin_post_query($conn, 'DELETE FROM users WHERE id = ?', 'i', [$id]);
        $message = 'User removed successfully.';
    }

    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_users.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_users.php');
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT id, fullname, email, role, status, created_at FROM users WHERE fullname LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $all_users_result = $stmt->get_result();
    $all_users = $all_users_result ? $all_users_result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $all_users_result = $conn->query("SELECT id, fullname, email, role, status, created_at FROM users ORDER BY created_at DESC");
    $all_users = $all_users_result ? $all_users_result->fetch_all(MYSQLI_ASSOC) : [];
}

$stats = [
    'total' => count($all_users),
    'active' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    'pending' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'suspend'"),
];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles('users'); ?>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Registered Users</h1>
    </div>

    <?php echo admin_render_nav('users'); ?>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Total Users</div><div class="metric-value"><?php echo $stats['total']; ?></div><div class="metric-note">All registered users</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Active</div><div class="metric-value" style="color: #10b981;"><?php echo $stats['active']; ?></div><div class="metric-note">Accounts that can log in</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending</div><div class="metric-value" style="color: #f97316;"><?php echo $stats['pending']; ?></div><div class="metric-note">Waiting for approval</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Suspended</div><div class="metric-value" style="color: #ef4444;"><?php echo $stats['suspended']; ?></div><div class="metric-note">Blocked from logging in</div></div>
    </div>

    <div class="section-card admin-panel">
        <div class="section-head" style="flex-wrap: wrap;">
            <h2>All Users</h2>
            <form method="GET" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Search</button>
                <?php if (!empty($_GET['search'])): ?>
                    <a href="admin_users.php" class="btn" style="padding: 0.4rem 1rem; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px;">Clear</a>
                <?php endif; ?>
            </form>
            <a href="admin_approve.php">Pending queue</a>
        </div>
        <?php if (empty($all_users)): ?>
            <div class="muted-empty">No users found.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                    <span class="pill pill-info" style="font-weight: 800; background: rgba(59, 130, 246, 0.12); color: #2563eb;"><i class="fas fa-user-shield"></i> Admin (You)</span>
                                <?php else: ?>
                                    <span class="pill" style="border: 1px solid rgba(226, 232, 240, 1); background: #ffffff; color: #334155;"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="pill <?php echo admin_status_class('user', $user['status']); ?>"><?php echo htmlspecialchars(admin_status_label('user', $user['status'])); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-stack">
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="pill pill-info"><i class="fas fa-shield-halved"></i> Protected</span>
                                    <?php elseif ($user['status'] === 'pending'): ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'approve', '✓ Approve'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'suspend', '⊘ Suspend', 'danger'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'reject', '✕ Reject', 'danger'); ?>
                                    <?php else: ?>
                                        <?php if ($user['status'] === 'suspend'): ?>
                                            <?php echo admin_suspend_toggle_button((int)$user['id'], true); ?>
                                        <?php else: ?>
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