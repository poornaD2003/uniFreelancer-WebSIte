<?php
include_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = (string)$_POST['action'];
    $id = (int)$_POST['id'];

    $ok = false;
    $message = 'Unable to update the gig.';

    if ($action === 'approve' || $action === 'restore') {
        $ok = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['approve', $id]);
        $message = 'Gig restored successfully.';
    } elseif ($action === 'suspend') {
        $ok = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
        $message = 'Gig suspended successfully.';
    }

    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_gigs.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_gigs.php');
}

$all_gigs_result = $conn->query("SELECT g.id, g.title, g.category, g.price, g.status, g.created_at, u.fullname AS student_name FROM gigs g JOIN users u ON g.student_id = u.id ORDER BY g.created_at DESC");
$all_gigs = $all_gigs_result ? $all_gigs_result->fetch_all(MYSQLI_ASSOC) : [];

$stats = [
    'total' => count($all_gigs),
    'pending' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'pending'"),
    'approved' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'approve'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'suspended'"),
];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles(); ?>

<div class="admin-shell">
    <div class="admin-hero">
        <div>
            <div class="pill pill-info" style="margin-bottom:0.8rem;">Gigs</div>
            <h1>Registered Gigs</h1>
            <p>Review all gigs, approve pending work, and suspend or restore gigs when needed.</p>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#fff;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#e2e8f0; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Total Gigs</div><div class="metric-value"><?php echo $stats['total']; ?></div><div class="metric-note">All posted gigs</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Approved</div><div class="metric-value"><?php echo $stats['approved']; ?></div><div class="metric-note">Visible on the platform</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending</div><div class="metric-value"><?php echo $stats['pending']; ?></div><div class="metric-note">Waiting for review</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Suspended</div><div class="metric-value"><?php echo $stats['suspended']; ?></div><div class="metric-note">Hidden from clients</div></div>
    </div>

    <div class="section-card admin-panel">
        <div class="section-head">
            <h2>All Gigs</h2>
            <a href="admin_approve.php">Pending queue</a>
        </div>
        <?php if (empty($all_gigs)): ?>
            <div class="muted-empty">No gigs found.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Gig</th><th>Freelancer</th><th>Category</th><th>Status</th><th>Price</th><th>Joined</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($all_gigs as $gig): ?>
                        <tr>
                            <td style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($gig['title']); ?></td>
                            <td><?php echo htmlspecialchars($gig['student_name']); ?></td>
                            <td><span class="pill pill-info"><?php echo htmlspecialchars($gig['category']); ?></span></td>
                            <td><span class="pill <?php echo admin_status_class('gig', $gig['status']); ?>"><?php echo htmlspecialchars(admin_status_label('gig', $gig['status'])); ?></span></td>
                            <td>Rs. <?php echo number_format((float)$gig['price'], 0); ?></td>
                            <td><?php echo date('M d, Y', strtotime($gig['created_at'])); ?></td>
                            <td><div class="action-stack">
                                <?php if ($gig['status'] === 'pending'): ?>
                                    <?php echo admin_action_button('gig', (int)$gig['id'], 'approve', 'Approve'); ?>
                                    <?php echo admin_action_button('gig', (int)$gig['id'], 'suspend', 'Suspend', 'danger'); ?>
                                <?php elseif ($gig['status'] === 'suspended'): ?>
                                    <?php echo admin_action_button('gig', (int)$gig['id'], 'restore', 'Restore'); ?>
                                <?php else: ?>
                                    <?php echo admin_action_button('gig', (int)$gig['id'], 'suspend', 'Suspend', 'danger'); ?>
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