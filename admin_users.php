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
        $ok = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['inactive', $id]);
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

$all_users_result = $conn->query("SELECT id, fullname, email, role, status, created_at FROM users ORDER BY created_at DESC");
$all_users = $all_users_result ? $all_users_result->fetch_all(MYSQLI_ASSOC) : [];

$stats = [
    'total' => count($all_users),
    'active' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    'pending' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'"),
];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles(); ?>

<div class="admin-shell">
    <div class="admin-hero">
        <div>
            <div class="pill pill-info" style="margin-bottom:0.8rem;">Users</div>
            <h1>Registered Users</h1>
            <p>Review every user account, approve pending users, and suspend or restore accounts when needed.</p>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#fff;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#e2e8f0; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Total Users</div><div class="metric-value"><?php echo $stats['total']; ?></div><div class="metric-note">All registered users</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Active</div><div class="metric-value"><?php echo $stats['active']; ?></div><div class="metric-note">Accounts that can log in</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending</div><div class="metric-value"><?php echo $stats['pending']; ?></div><div class="metric-note">Waiting for approval</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Suspended</div><div class="metric-value"><?php echo $stats['suspended']; ?></div><div class="metric-note">Blocked from logging in</div></div>
    </div>

    <div class="section-card admin-panel">
        <div class="section-head">
            <h2>All Users</h2>
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
                            <td style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="pill <?php echo $user['role'] === 'student' ? 'pill-success' : ($user['role'] === 'client' ? 'pill-info' : 'pill-warning'); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="pill <?php echo admin_status_class('user', $user['status']); ?>"><?php echo htmlspecialchars(admin_status_label('user', $user['status'])); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-stack">
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="pill pill-info">Protected</span>
                                    <?php elseif ($user['status'] === 'pending'): ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'approve', 'Approve'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'suspend', 'Suspend', 'danger'); ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'reject', 'Reject', 'danger'); ?>
                                    <?php elseif ($user['status'] === 'inactive'): ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'restore', 'Restore'); ?>
                                    <?php else: ?>
                                        <?php echo admin_action_button('user', (int)$user['id'], 'suspend', 'Suspend', 'danger'); ?>
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