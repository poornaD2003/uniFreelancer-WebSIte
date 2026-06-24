<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'pending_users' => 0,
    'student_users' => 0,
    'client_users' => 0,
    'admin_users' => 0,
    'total_clubs' => 0,
    'pending_clubs' => 0,
    'approved_clubs' => 0,
    'total_gigs' => 0,
    'pending_gigs' => 0,
    'approved_gigs' => 0,
    'total_orders' => 0,
];

$queries = [
    'total_users' => "SELECT COUNT(*) AS total FROM users",
    'active_users' => "SELECT COUNT(*) AS total FROM users WHERE status = 'active'",
    'pending_users' => "SELECT COUNT(*) AS total FROM users WHERE status = 'pending'",
    'student_users' => "SELECT COUNT(*) AS total FROM users WHERE role = 'student'",
    'client_users' => "SELECT COUNT(*) AS total FROM users WHERE role = 'client'",
    'admin_users' => "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'",
    'total_clubs' => "SELECT COUNT(*) AS total FROM clubs",
    'pending_clubs' => "SELECT COUNT(*) AS total FROM clubs WHERE status = 'pending'",
    'approved_clubs' => "SELECT COUNT(*) AS total FROM clubs WHERE status = 'approved'",
    'total_gigs' => "SELECT COUNT(*) AS total FROM gigs",
    'pending_gigs' => "SELECT COUNT(*) AS total FROM gigs WHERE status = 'pending'",
    'approved_gigs' => "SELECT COUNT(*) AS total FROM gigs WHERE status = 'approve'",
    'total_orders' => "SELECT COUNT(*) AS total FROM orders",
];

foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats[$key] = (int)($row['total'] ?? 0);
    }
}

$pending_users = [];
$pending_clubs = [];
$recent_activity = [];

$pending_users_result = $conn->query("SELECT id, fullname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
if ($pending_users_result) {
    $pending_users = $pending_users_result->fetch_all(MYSQLI_ASSOC);
}

$pending_clubs_result = $conn->query("SELECT id, club_name, club_code, contribution_rate, created_at FROM clubs WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
if ($pending_clubs_result) {
    $pending_clubs = $pending_clubs_result->fetch_all(MYSQLI_ASSOC);
}

$recent_activity_result = $conn->query(
    "SELECT 'user' AS item_type, fullname AS item_name, role AS item_meta, created_at
     FROM users
     UNION ALL
     SELECT 'club' AS item_type, club_name AS item_name, status AS item_meta, created_at
     FROM clubs
     ORDER BY created_at DESC
     LIMIT 8"
);
if ($recent_activity_result) {
    $recent_activity = $recent_activity_result->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
    .admin-shell {
        padding: 110px 5% 4rem;
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.18), transparent 35%),
            radial-gradient(circle at top right, rgba(99, 102, 241, 0.14), transparent 28%),
            linear-gradient(180deg, #07111b 0%, #0f172a 100%);
        color: #e2e8f0;
        min-height: 100vh;
    }

    .admin-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .admin-hero h1 {
        font-size: clamp(2rem, 4vw, 3.2rem);
        letter-spacing: -0.04em;
        margin-bottom: 0.35rem;
        color: #ffffff;
    }

    .admin-hero p {
        color: #94a3b8;
        max-width: 760px;
        line-height: 1.6;
    }

    .admin-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-panel {
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 24px;
        backdrop-filter: blur(18px);
        box-shadow: 0 24px 70px rgba(2, 6, 23, 0.35);
    }

    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .metric-card {
        padding: 1.25rem;
        min-height: 136px;
    }

    .metric-label {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 0.6rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -0.04em;
    }

    .metric-note {
        margin-top: 0.55rem;
        color: #cbd5e1;
        font-size: 0.9rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }

    .section-card {
        padding: 1.35rem;
    }

    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .section-head h2 {
        color: #fff;
        font-size: 1.15rem;
        letter-spacing: -0.02em;
    }

    .section-head a {
        color: #7dd3fc;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th,
    .data-table td {
        padding: 0.95rem 0.8rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        text-align: left;
        vertical-align: top;
    }

    .data-table th {
        color: #94a3b8;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .data-table td {
        color: #e2e8f0;
        font-size: 0.92rem;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        background: rgba(148, 163, 184, 0.12);
        color: #e2e8f0;
    }

    .pill-success {
        background: rgba(16, 185, 129, 0.14);
        color: #6ee7b7;
    }

    .pill-warning {
        background: rgba(245, 158, 11, 0.14);
        color: #fbbf24;
    }

    .pill-info {
        background: rgba(96, 165, 250, 0.14);
        color: #93c5fd;
    }

    .quick-links {
        display: grid;
        gap: 0.8rem;
    }

    .quick-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.05rem;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(15, 23, 42, 0.45);
        color: #e2e8f0;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        border-color: rgba(125, 211, 252, 0.4);
    }

    .muted-empty {
        color: #94a3b8;
        padding: 1rem 0;
    }

    @media (max-width: 980px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-shell">
    <div class="admin-hero">
        <div>
            <div class="pill pill-info" style="margin-bottom: 0.8rem;">Admin Workspace</div>
            <h1>Dashboard Overview</h1>
            <p>Monitor registrations, approvals, gigs, clubs, and overall platform activity from one place. Use the quick actions to jump straight into moderation.</p>
        </div>
        <div class="admin-actions">
            <a class="btn btn-primary" href="admin_approve.php">Review Approvals</a>
            <a class="btn btn-outline" href="logout.php">Logout</a>
        </div>
    </div>

    <div class="metric-grid">
        <div class="admin-panel metric-card">
            <div class="metric-label">Total Users</div>
            <div class="metric-value"><?php echo $stats['total_users']; ?></div>
            <div class="metric-note"><?php echo $stats['active_users']; ?> active, <?php echo $stats['pending_users']; ?> pending</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending Approvals</div>
            <div class="metric-value"><?php echo $stats['pending_users'] + $stats['pending_clubs'] + $stats['pending_gigs']; ?></div>
            <div class="metric-note">Users, clubs, and gigs waiting for review</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Clubs</div>
            <div class="metric-value"><?php echo $stats['total_clubs']; ?></div>
            <div class="metric-note"><?php echo $stats['approved_clubs']; ?> approved, <?php echo $stats['pending_clubs']; ?> pending</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Orders & Gigs</div>
            <div class="metric-value"><?php echo $stats['total_orders']; ?> / <?php echo $stats['total_gigs']; ?></div>
            <div class="metric-note"><?php echo $stats['approved_gigs']; ?> gigs approved, <?php echo $stats['pending_gigs']; ?> pending</div>
        </div>
    </div>

    <div class="content-grid">
        <div class="admin-panel section-card">
            <div class="section-head">
                <h2>Pending Users</h2>
                <a href="admin_approve.php">Open approvals</a>
            </div>
            <?php if (empty($pending_users)): ?>
                <div class="muted-empty">No user registrations are waiting for review.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                    <div style="color: #94a3b8; font-size: 0.84rem;"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td>
                                    <span class="pill <?php echo $user['role'] === 'student' ? 'pill-success' : 'pill-info'; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="admin-panel section-card">
            <div class="section-head">
                <h2>Quick Actions</h2>
                <a href="admin_approve.php">Manage now</a>
            </div>
            <div class="quick-links">
                <a class="quick-link" href="admin_approve.php">
                    <span>
                        <strong style="display:block; color:#fff; margin-bottom:0.2rem;">Approve registrations</strong>
                        <span style="color:#94a3b8; font-size:0.9rem;">Review pending users and clubs.</span>
                    </span>
                    <span class="pill pill-warning"><?php echo $stats['pending_users'] + $stats['pending_clubs']; ?></span>
                </a>
                <a class="quick-link" href="student-dashboard.php">
                    <span>
                        <strong style="display:block; color:#fff; margin-bottom:0.2rem;">Inspect student area</strong>
                        <span style="color:#94a3b8; font-size:0.9rem;">Open the student dashboard in a new context.</span>
                    </span>
                    <span class="pill">View</span>
                </a>
                <a class="quick-link" href="client-dashboard.php">
                    <span>
                        <strong style="display:block; color:#fff; margin-bottom:0.2rem;">Inspect client area</strong>
                        <span style="color:#94a3b8; font-size:0.9rem;">Check the client dashboard layout and flow.</span>
                    </span>
                    <span class="pill">View</span>
                </a>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="admin-panel section-card">
            <div class="section-head">
                <h2>Pending Clubs</h2>
                <a href="admin_approve.php">See all clubs</a>
            </div>
            <?php if (empty($pending_clubs)): ?>
                <div class="muted-empty">No club requests are waiting for approval.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Code</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_clubs as $club): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($club['club_name']); ?></div>
                                    <div style="color: #94a3b8; font-size: 0.84rem;">Submitted <?php echo date('M d, Y', strtotime($club['created_at'])); ?></div>
                                </td>
                                <td><span class="pill"><?php echo htmlspecialchars($club['club_code']); ?></span></td>
                                <td><?php echo number_format((float)$club['contribution_rate'], 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
                                <td style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><span class="pill <?php echo $item['item_type'] === 'user' ? 'pill-success' : 'pill-info'; ?>"><?php echo htmlspecialchars($item['item_type']); ?></span></td>
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