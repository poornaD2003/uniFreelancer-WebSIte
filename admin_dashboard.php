<?php
include_once __DIR__ . '/includes/admin_common.php';
include 'includes/header.php';

$stats = [
    'total_users' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users"),
    'active_users' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    'pending_users' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'"),
    'suspended_users' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'"),
    'total_clubs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs"),
    'pending_clubs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'pending'"),
    'approved_clubs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'approved'"),
    'suspended_clubs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'suspended'"),
    'total_gigs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs"),
    'pending_gigs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'pending'"),
    'approved_gigs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'approve'"),
    'suspended_gigs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'suspended'"),
    'total_orders' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM orders"),
];

$recent_activity = [];
$recent_activity_result = $conn->query(
    "SELECT 'user' AS item_type, fullname AS item_name, status AS item_meta, created_at FROM users
     UNION ALL
     SELECT 'club' AS item_type, club_name AS item_name, status AS item_meta, created_at FROM clubs
     UNION ALL
     SELECT 'gig' AS item_type, title AS item_name, status AS item_meta, created_at FROM gigs
     ORDER BY created_at DESC LIMIT 8"
);
if ($recent_activity_result) {
    $recent_activity = $recent_activity_result->fetch_all(MYSQLI_ASSOC);
}
?>

<?php echo admin_theme_styles('dashboard'); ?>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Dashboard Overview</h1>
    </div>

    <?php echo admin_render_nav('dashboard'); ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card">
            <div class="metric-label">Total Users</div>
            <div class="metric-value"><?php echo $stats['total_users']; ?></div>
            <div class="metric-note"><?php echo $stats['active_users']; ?> active, <?php echo $stats['pending_users']; ?> pending, <?php echo $stats['suspended_users']; ?> suspended</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending Work</div>
            <div class="metric-value" style="color: #f97316;"><?php echo $stats['pending_users'] + $stats['pending_clubs'] + $stats['pending_gigs']; ?></div>
            <div class="metric-note">Registrations and gigs waiting for review</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Clubs</div>
            <div class="metric-value"><?php echo $stats['total_clubs']; ?></div>
            <div class="metric-note"><?php echo $stats['approved_clubs']; ?> approved, <?php echo $stats['pending_clubs']; ?> pending, <?php echo $stats['suspended_clubs']; ?> suspended</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Orders & Gigs</div>
            <div class="metric-value"><?php echo $stats['total_orders']; ?> / <?php echo $stats['total_gigs']; ?></div>
            <div class="metric-note"><?php echo $stats['approved_gigs']; ?> gigs approved, <?php echo $stats['pending_gigs']; ?> pending, <?php echo $stats['suspended_gigs']; ?> suspended</div>
        </div>
    </div>

    <div class="content-grid">
        <div class="admin-panel section-card">
            <div class="section-head">
                <h2>Quick Actions</h2>
                <a href="admin_approve.php">Open approvals</a>
            </div>
            <div class="quick-links">
                <a href="admin_approve.php" class="quick-link">
                    <span><i class="fas fa-check-circle" style="margin-right: 8px;"></i> Review Pending Registrations & Gigs</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="admin_users.php" class="quick-link">
                    <span><i class="fas fa-users" style="margin-right: 8px;"></i> Manage User Accounts</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="admin_clubs.php" class="quick-link">
                    <span><i class="fas fa-shield-halved" style="margin-right: 8px;"></i> Review Registered Clubs</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="admin_gigs.php" class="quick-link">
                    <span><i class="fas fa-briefcase" style="margin-right: 8px;"></i> Moderate Active Gigs</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="admin-panel section-card">
            <div class="section-head">
                <h2>Recent Activity</h2>
                <a href="admin_approve.php">Moderation queue</a>
            </div>
            <?php if (empty($recent_activity)): ?>
                <div class="muted-empty">Nothing recent to show yet.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $item): ?>
                            <tr>
                                <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><span class="pill <?php echo $item['item_type'] === 'user' ? 'pill-success' : ($item['item_type'] === 'club' ? 'pill-info' : 'pill-warning'); ?>"><?php echo htmlspecialchars($item['item_type']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>