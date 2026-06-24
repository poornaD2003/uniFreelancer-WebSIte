<?php
include_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entity'], $_POST['action'], $_POST['id'])) {
    $entity = (string)$_POST['entity'];
    $action = (string)$_POST['action'];
    $id = (int)$_POST['id'];

    $ok = false;
    $message = 'Unable to complete the moderation action.';

    if ($entity === 'user') {
        if ($action === 'approve' || $action === 'restore') {
            $ok = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['active', $id]);
            $message = 'User approved successfully.';
        } elseif ($action === 'suspend') {
            $ok = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['inactive', $id]);
            $message = 'User suspended successfully.';
        } elseif ($action === 'reject') {
            $ok = admin_post_query($conn, 'DELETE FROM users WHERE id = ?', 'i', [$id]);
            $message = 'User registration rejected and removed.';
        }
    } elseif ($entity === 'club') {
        if ($action === 'approve' || $action === 'restore') {
            $ok = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['approved', $id]);
            $message = 'Club approved successfully.';
        } elseif ($action === 'suspend') {
            $ok = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
            $message = 'Club suspended successfully.';
        } elseif ($action === 'reject') {
            $ok = admin_post_query($conn, 'DELETE FROM clubs WHERE id = ?', 'i', [$id]);
            $message = 'Club registration rejected and removed.';
        }
    } elseif ($entity === 'gig') {
        if ($action === 'approve' || $action === 'restore') {
            $ok = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['approve', $id]);
            $message = 'Gig approved successfully.';
        } elseif ($action === 'suspend') {
            $ok = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
            $message = 'Gig suspended successfully.';
        }
    }

    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_approve.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_approve.php');
}

$pending_users_result = $conn->query("SELECT id, fullname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$pending_users = $pending_users_result ? $pending_users_result->fetch_all(MYSQLI_ASSOC) : [];
$pending_clubs_result = $conn->query("SELECT id, club_name, club_code, contribution_rate, created_at FROM clubs WHERE status = 'pending' ORDER BY created_at DESC");
$pending_clubs = $pending_clubs_result ? $pending_clubs_result->fetch_all(MYSQLI_ASSOC) : [];
$pending_gigs_result = $conn->query("SELECT g.id, g.title, g.category, g.price, g.created_at, u.fullname AS student_name FROM gigs g JOIN users u ON g.student_id = u.id WHERE g.status = 'pending' ORDER BY g.created_at DESC");
$pending_gigs = $pending_gigs_result ? $pending_gigs_result->fetch_all(MYSQLI_ASSOC) : [];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include 'includes/header.php';
?>

<?php echo admin_theme_styles('approvals'); ?>

<div class="admin-shell">
    <div class="admin-page-header">
        <h1 class="admin-page-title">Pending Approvals</h1>
    </div>

    <?php echo admin_render_nav('approvals'); ?>

    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem; background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>; border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <div class="metric-grid">
        <div class="admin-panel metric-card"><div class="metric-label">Pending Users</div><div class="metric-value"><?php echo count($pending_users); ?></div><div class="metric-note">Users waiting for approval</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending Clubs</div><div class="metric-value"><?php echo count($pending_clubs); ?></div><div class="metric-note">Club registrations waiting for approval</div></div>
        <div class="admin-panel metric-card"><div class="metric-label">Pending Gigs</div><div class="metric-value"><?php echo count($pending_gigs); ?></div><div class="metric-note">Gigs waiting for approval</div></div>
    </div>

    <div class="section-card admin-panel" id="approvals">
        <div class="section-head">
            <h2>Pending Users</h2>
            <a href="admin_users.php">See all users</a>
        </div>
        <?php if (empty($pending_users)): ?>
            <div class="muted-empty">No user registrations are waiting for review.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                        <tr>
                            <td><div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($user['fullname']); ?></div><div style="color:#64748b; font-size:0.84rem;"><?php echo htmlspecialchars($user['email']); ?></div></td>
                            <td><span class="pill <?php echo $user['role'] === 'student' ? 'pill-success' : 'pill-info'; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><div class="action-stack"><?php echo admin_action_button('user', (int)$user['id'], 'approve', 'Approve'); ?><?php echo admin_action_button('user', (int)$user['id'], 'suspend', 'Suspend', 'danger'); ?><?php echo admin_action_button('user', (int)$user['id'], 'reject', 'Reject', 'danger'); ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-card admin-panel" style="margin-top:1rem;" id="clubs">
        <div class="section-head">
            <h2>Pending Clubs</h2>
            <a href="admin_clubs.php">See all clubs</a>
        </div>
        <?php if (empty($pending_clubs)): ?>
            <div class="muted-empty">No club requests are waiting for approval.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Club</th><th>Code</th><th>Share</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pending_clubs as $club): ?>
                        <tr>
                            <td><div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($club['club_name']); ?></div><div style="color:#64748b; font-size:0.84rem;">Submitted <?php echo date('M d, Y', strtotime($club['created_at'])); ?></div></td>
                            <td><span class="pill"><?php echo htmlspecialchars($club['club_code']); ?></span></td>
                            <td><?php echo number_format((float)$club['contribution_rate'], 2); ?>%</td>
                            <td><div class="action-stack"><?php echo admin_action_button('club', (int)$club['id'], 'approve', 'Approve'); ?><?php echo admin_action_button('club', (int)$club['id'], 'suspend', 'Suspend', 'danger'); ?><?php echo admin_action_button('club', (int)$club['id'], 'reject', 'Reject', 'danger'); ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-card admin-panel" style="margin-top:1rem;" id="gigs">
        <div class="section-head">
            <h2>Pending Gigs</h2>
            <a href="admin_gigs.php">See all gigs</a>
        </div>
        <?php if (empty($pending_gigs)): ?>
            <div class="muted-empty">No gig submissions are waiting for review.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Freelancer</th><th>Price</th><th>Category</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pending_gigs as $gig): ?>
                        <tr>
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($gig['title']); ?></td>
                            <td><?php echo htmlspecialchars($gig['student_name']); ?></td>
                            <td>Rs. <?php echo number_format((float)$gig['price'], 0); ?></td>
                            <td><span class="pill pill-info"><?php echo htmlspecialchars($gig['category']); ?></span></td>
                            <td><div class="action-stack"><?php echo admin_action_button('gig', (int)$gig['id'], 'approve', 'Approve'); ?><?php echo admin_action_button('gig', (int)$gig['id'], 'suspend', 'Suspend', 'danger'); ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>