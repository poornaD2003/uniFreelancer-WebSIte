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
    }

    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_gigs.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_gigs.php');
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT g.id, g.title, g.category, g.price, g.status, g.created_at, u.fullname AS student_name FROM gigs g JOIN users u ON g.student_id = u.id WHERE g.title LIKE ? ORDER BY g.created_at DESC");
    $like = '%' . $search . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $all_gigs_result = $stmt->get_result();
    $all_gigs = $all_gigs_result ? $all_gigs_result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $all_gigs_result = $conn->query("SELECT g.id, g.title, g.category, g.price, g.status, g.created_at, u.fullname AS student_name FROM gigs g JOIN users u ON g.student_id = u.id ORDER BY g.created_at DESC");
    $all_gigs = $all_gigs_result ? $all_gigs_result->fetch_all(MYSQLI_ASSOC) : [];
}

$stats = [
    'total' => count($all_gigs),
    'pending' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'pending'"),
    'approved' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'approve'"),
];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles('gigs'); ?>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Registered Gigs</h1>
    </div>

    <?php echo admin_render_nav('gigs'); ?>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Total Gigs</div><div class="metric-value"><?php echo $stats['total']; ?></div><div class="metric-note">All posted gigs</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Approved</div><div class="metric-value" style="color: #10b981;"><?php echo $stats['approved']; ?></div><div class="metric-note">Visible on the platform</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending</div><div class="metric-value" style="color: #f97316;"><?php echo $stats['pending']; ?></div><div class="metric-note">Waiting for review</div></div>
    </div>

    <div class="section-card admin-panel">
        <div class="section-head" style="flex-wrap: wrap;">
            <h2>All Gigs</h2>
            <form method="GET" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" name="search" placeholder="Search gig..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Search</button>
                <?php if (!empty($_GET['search'])): ?>
                    <a href="admin_gigs.php" class="btn" style="padding: 0.4rem 1rem; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px;">Clear</a>
                <?php endif; ?>
            </form>
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
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($gig['title']); ?></td>
                            <td><?php echo htmlspecialchars($gig['student_name']); ?></td>
                            <td><span class="pill pill-info"><?php echo htmlspecialchars($gig['category']); ?></span></td>
                            <td><span class="pill <?php echo admin_status_class('gig', $gig['status']); ?>"><?php echo htmlspecialchars(admin_status_label('gig', $gig['status'])); ?></span></td>
                            <td>Rs. <?php echo number_format((float)$gig['price'], 0); ?></td>
                            <td><?php echo date('M d, Y', strtotime($gig['created_at'])); ?></td>
                            <td><div class="action-stack">
                                <?php if ($gig['status'] === 'pending'): ?>
                                    <?php echo admin_action_button('gig', (int)$gig['id'], 'approve', '✓ Approve'); ?>
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