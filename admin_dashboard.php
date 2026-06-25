<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin_dashboard.php — Admin Overview / Home Page
// Shows platform-wide stats and a recent activity feed.
// ─────────────────────────────────────────────────────────────────────────────
include_once __DIR__ . '/includes/admin_common.php';
include 'includes/header.php';

// ── PLATFORM STATISTICS ───────────────────────────────────────────────────────
// Build an associative array of counts by running individual COUNT queries.
// admin_count_query() returns an integer — 0 if the query fails.
$stats = [
    // User counts broken down by status
    'total_users'     => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users"),
    'active_users'    => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    'pending_users'   => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'"),
    'suspended_users' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'"),

    // Club counts broken down by status
    'total_clubs'     => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs"),
    'pending_clubs'   => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'pending'"),
    'approved_clubs'  => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'approved'"),
    'suspended_clubs' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'suspended'"),

    // Gig counts — note: approved gigs use status 'approve' (not 'approved') in the DB
    'total_gigs'      => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs"),
    'pending_gigs'    => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'pending'"),
    'approved_gigs'   => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'approve'"),
    'suspended_gigs'  => admin_count_query($conn, "SELECT COUNT(*) AS total FROM gigs WHERE status = 'suspended'"),

    // Total orders placed on the platform
    'total_orders'    => admin_count_query($conn, "SELECT COUNT(*) AS total FROM orders"),
];

// ── RECENT ACTIVITY FEED ─────────────────────────────────────────────────────
// UNION of users, clubs, and gigs — gives a unified chronological activity log.
// Each row is normalised to: item_type, item_name, item_meta (status), created_at
$recent_activity = [];
$recent_activity_result = $conn->query(
    "SELECT role AS item_type, fullname AS item_name, status AS item_meta, created_at FROM users
     UNION ALL
     SELECT 'club' AS item_type, club_name AS item_name, status AS item_meta, created_at FROM clubs
     UNION ALL
     SELECT 'gig' AS item_type, title AS item_name, status AS item_meta, created_at FROM gigs
     ORDER BY created_at DESC LIMIT 8"  // Show only the 8 most recent entries
);
if ($recent_activity_result) {
    $recent_activity = $recent_activity_result->fetch_all(MYSQLI_ASSOC);
}
?>

<?php
// Inject dashboard colour theme CSS (green palette) and shared admin layout
echo admin_theme_styles('dashboard');
?>

<div class="admin-shell">
    <!-- Page title -->
    <div class="admin-page-header">
        <h1 class="admin-page-title">Dashboard Overview</h1>
    </div>

    <!-- Navigation pill bar — 'dashboard' tab is highlighted as active -->
    <?php echo admin_render_nav('dashboard'); ?>

    <!-- ── CLICKABLE STAT CARDS ────────────────────────────────────────────── -->
    <!-- Each card is an <a> tag so clicking navigates to the relevant section -->
    <div class="metric-grid">

        <!-- Users card → links to admin_users.php -->
        <a href="admin_users.php" class="admin-panel metric-card metric-link">
            <div class="metric-label">Total Users</div>
            <div class="metric-value"><?php echo $stats['total_users']; ?></div>
            <div class="metric-note">
                <div style="color:#10b981;"><?php echo $stats['active_users']; ?> active</div>    <!-- green = active -->
                <div style="color:#f97316;"><?php echo $stats['pending_users']; ?> pending</div>  <!-- orange = needs review -->
                <div style="color:#ef4444;"><?php echo $stats['suspended_users']; ?> suspended</div> <!-- red = blocked -->
            </div>
        </a>

        <!-- Pending Review card → shows combined pending count, links to approvals -->
        <a href="admin_approve.php" class="admin-panel metric-card metric-link">
            <div class="metric-label">Pending Review</div>
            <!-- Sum of all pending entities across all three types -->
            <div class="metric-value" style="color: #f97316;">
                <?php echo $stats['pending_users'] + $stats['pending_clubs'] + $stats['pending_gigs']; ?>
            </div>
            <div class="metric-note">
                <div style="color:#f97316;"><?php echo $stats['pending_users']; ?> users</div>
                <div style="color:#f97316;"><?php echo $stats['pending_clubs']; ?> clubs</div>
                <div style="color:#f97316;"><?php echo $stats['pending_gigs']; ?> gigs</div>
            </div>
        </a>

        <!-- Clubs card → links to admin_clubs.php -->
        <a href="admin_clubs.php" class="admin-panel metric-card metric-link">
            <div class="metric-label">Clubs</div>
            <div class="metric-value"><?php echo $stats['total_clubs']; ?></div>
            <div class="metric-note">
                <div style="color:#10b981;"><?php echo $stats['approved_clubs']; ?> approved</div>
                <div style="color:#f97316;"><?php echo $stats['pending_clubs']; ?> pending</div>
                <div style="color:#ef4444;"><?php echo $stats['suspended_clubs']; ?> suspended</div>
            </div>
        </a>

        <!-- Gigs card → links to admin_gigs.php -->
        <a href="admin_gigs.php" class="admin-panel metric-card metric-link">
            <div class="metric-label">Gigs</div>
            <div class="metric-value"><?php echo $stats['total_gigs']; ?></div>
            <div class="metric-note">
                <div style="color:#10b981;"><?php echo $stats['approved_gigs']; ?> approved</div>
                <div style="color:#f97316;"><?php echo $stats['pending_gigs']; ?> pending</div>
                <div style="color:#ef4444;"><?php echo $stats['suspended_gigs']; ?> suspended</div>
            </div>
        </a>
    </div>

    <!-- ── RECENT ACTIVITY TABLE ────────────────────────────────────────────── -->
    <div class="admin-panel section-card" style="margin-top: 1.5rem;">
        <div class="section-head">
            <h2>Recent Activity</h2>
        </div>
        <?php if (empty($recent_activity)): ?>
            <!-- Empty state — shown when there are no records yet -->
            <div class="muted-empty">Nothing recent to show yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activity as $item): ?>
                        <tr>
                            <!-- Entity name (user fullname / club name / gig title) -->
                            <td style="font-weight:700;"><?php echo htmlspecialchars($item['item_name']); ?></td>

                            <!-- Role/type badge:
                                 blue   (pill-info)    → user types (admin, student, client)
                                 green  (pill-success) → club
                                 orange (pill-warning) → gig
                            -->
                            <td><span class="pill <?php echo in_array($item['item_type'], ['admin', 'student', 'client']) ? 'pill-info' : ($item['item_type'] === 'club' ? 'pill-success' : 'pill-warning'); ?>">
                                <?php echo ucfirst(htmlspecialchars($item['item_type'])); ?>
                            </span></td>

                            <!-- Status badge:
                                 green  (pill-success) → active / approve / approved
                                 orange (pill-warning) → pending
                                 red    (pill-danger)  → inactive / suspended
                            -->
                            <td><span class="pill <?php
                                $s = $item['item_meta'];
                                // Map status values to colour classes
                                echo $s === 'active' || $s === 'approve' || $s === 'approved'
                                    ? 'pill-success'
                                    : ($s === 'pending' ? 'pill-warning' : 'pill-danger');
                            ?>">
                                <?php
                                // Translate 'inactive' to 'Suspended' for readable display
                                echo ucfirst(htmlspecialchars($s === 'inactive' ? 'Suspended' : $item['item_meta']));
                                ?>
                            </span></td>

                            <!-- Created/registered date -->
                            <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>