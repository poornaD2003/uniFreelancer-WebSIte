<?php
include_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = (string)$_POST['action'];
    $id = (int)$_POST['id'];

    $ok = false;
    $message = 'Unable to update the club.';

    if ($action === 'approve' || $action === 'restore') {
        $ok = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['approved', $id]);
        $message = 'Club restored successfully.';
    } elseif ($action === 'suspend') {
        $ok = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
        $message = 'Club suspended successfully.';
    } elseif ($action === 'reject') {
        $ok = admin_post_query($conn, 'DELETE FROM clubs WHERE id = ?', 'i', [$id]);
        $message = 'Club removed successfully.';
    }

    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_clubs.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_clubs.php');
}

$all_clubs_result = $conn->query("SELECT id, club_name, club_code, status, contribution_rate, created_at FROM clubs ORDER BY created_at DESC");
$all_clubs = $all_clubs_result ? $all_clubs_result->fetch_all(MYSQLI_ASSOC) : [];

$stats = [
    'total' => count($all_clubs),
    'pending' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'pending'"),
    'approved' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'approved'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'suspended'"),
];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles('clubs'); ?>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Registered Clubs</h1>
    </div>

    <?php echo admin_render_nav('clubs'); ?>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Total Clubs</div><div class="metric-value"><?php echo $stats['total']; ?></div><div class="metric-note">All registered clubs</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Approved</div><div class="metric-value" style="color: #10b981;"><?php echo $stats['approved']; ?></div><div class="metric-note">Currently active clubs</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending</div><div class="metric-value" style="color: #f97316;"><?php echo $stats['pending']; ?></div><div class="metric-note">Awaiting review</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Suspended</div><div class="metric-value" style="color: #ef4444;"><?php echo $stats['suspended']; ?></div><div class="metric-note">Temporarily disabled clubs</div></div>
    </div>

    <div class="section-card admin-panel">
        <div class="section-head">
            <h2>All Clubs</h2>
            <a href="admin_approve.php">Pending queue</a>
        </div>
        <?php if (empty($all_clubs)): ?>
            <div class="muted-empty">No clubs found.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Club</th><th>Username</th><th>Code</th><th>Status</th><th>Share</th><th>Joined</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($all_clubs as $club): ?>
                        <tr>
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($club['club_name']); ?></td>
                            <td><span class="pill"><?php echo htmlspecialchars($club['club_code']); ?></span></td>
                            <td><span class="pill <?php echo admin_status_class('club', $club['status']); ?>"><?php echo htmlspecialchars(admin_status_label('club', $club['status'])); ?></span></td>
                            <td><?php echo number_format((float)$club['contribution_rate'], 2); ?>%</td>
                            <td><?php echo date('M d, Y', strtotime($club['created_at'])); ?></td>
                            <td><div class="action-stack">
                                <?php if ($club['status'] === 'pending'): ?>
                                    <?php echo admin_action_button('club', (int)$club['id'], 'approve', '✓ Approve'); ?>
                                    <?php echo admin_suspend_toggle_button((int)$club['id'], false, 'club'); ?>
                                    <?php echo admin_action_button('club', (int)$club['id'], 'reject', '✕ Reject', 'danger'); ?>
                                <?php else: ?>
                                    <?php echo admin_suspend_toggle_button((int)$club['id'], $club['status'] === 'suspended', 'club'); ?>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>