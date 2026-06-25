<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin_approve.php — Pending Approvals Page
// Handles moderation of pending users, clubs, and gigs.
// Admins can approve, suspend, or reject each entity from this page.
// ─────────────────────────────────────────────────────────────────────────────
include_once __DIR__ . '/includes/admin_common.php';

// ── HANDLE MODERATION ACTIONS (POST) ────────────────────────────────────────
// Runs only when an action button form is submitted (approve / suspend / reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entity'], $_POST['action'], $_POST['id'])) {
    $entity = (string)$_POST['entity'];  // Which table to target: 'user', 'club', or 'gig'
    $action = (string)$_POST['action'];  // What to do: 'approve', 'restore', 'suspend', 'reject'
    $id     = (int)$_POST['id'];         // DB primary key of the entity being moderated

    $ok      = false;                                    // Tracks whether the DB operation succeeded
    $message = 'Unable to complete the moderation action.'; // Default error message

    // ── USER MODERATION ──────────────────────────────────────────────────────
    if ($entity === 'user') {
        if ($action === 'approve' || $action === 'restore') {
            // Set status to 'active' — user can now log in to the platform
            $ok      = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['active', $id]);
            $message = 'User approved successfully.';
        } elseif ($action === 'suspend') {
            // Set status to 'inactive' — blocks the user from logging in (reversible)
            $ok      = admin_post_query($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', ['inactive', $id]);
            $message = 'User suspended successfully.';
        } elseif ($action === 'reject') {
            // Permanently DELETE the user row — used to decline a bad registration
            $ok      = admin_post_query($conn, 'DELETE FROM users WHERE id = ?', 'i', [$id]);
            $message = 'User registration rejected and removed.';
        }

    // ── CLUB MODERATION ──────────────────────────────────────────────────────
    } elseif ($entity === 'club') {
        if ($action === 'approve' || $action === 'restore') {
            // Mark club as 'approved' — club becomes active and visible on the platform
            $ok      = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['approved', $id]);
            $message = 'Club approved successfully.';
        } elseif ($action === 'suspend') {
            // Mark club as 'suspended' — hides it but keeps the record (reversible)
            $ok      = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
            $message = 'Club suspended successfully.';
        } elseif ($action === 'reject') {
            // Permanently DELETE the club row — declines the registration entirely
            $ok      = admin_post_query($conn, 'DELETE FROM clubs WHERE id = ?', 'i', [$id]);
            $message = 'Club registration rejected and removed.';
        }

    // ── GIG MODERATION ───────────────────────────────────────────────────────
    } elseif ($entity === 'gig') {
        if ($action === 'approve' || $action === 'restore') {
            // Note: gigs use status 'approve' (not 'approved') in the database
            // This makes the gig publicly visible in the freelancer listings
            $ok      = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['approve', $id]);
            $message = 'Gig approved successfully.';
        } elseif ($action === 'suspend') {
            // Suspend the gig — removes it from public listings but keeps the data
            $ok      = admin_post_query($conn, 'UPDATE gigs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
            $message = 'Gig suspended successfully.';
        }
    }

    // Store a one-time flash message and redirect to prevent form re-submission on refresh
    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_approve.php');
    }

    // If $ok is still false, redirect with an error flash message
    admin_flash_and_redirect('error', $message, 'admin_approve.php');
}

// ── FETCH PENDING ENTITIES ───────────────────────────────────────────────────
// These queries power the three approval tables below.
// All results are ordered newest-first so the most recent registrations appear at the top.

// Pending users — people who registered but haven't been approved yet
$pending_users_result = $conn->query("SELECT id, fullname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$pending_users        = $pending_users_result ? $pending_users_result->fetch_all(MYSQLI_ASSOC) : [];

// Pending clubs — club registrations awaiting admin review
$pending_clubs_result = $conn->query("SELECT id, club_name, club_code, contribution_rate, created_at FROM clubs WHERE status = 'pending' ORDER BY created_at DESC");
$pending_clubs        = $pending_clubs_result ? $pending_clubs_result->fetch_all(MYSQLI_ASSOC) : [];

// Pending gigs — gig submissions awaiting approval before going live
// JOINs with users to show the freelancer's name alongside each gig
$pending_gigs_result = $conn->query("SELECT g.id, g.title, g.category, g.price, g.created_at, u.fullname AS student_name FROM gigs g JOIN users u ON g.student_id = u.id WHERE g.status = 'pending' ORDER BY g.created_at DESC");
$pending_gigs        = $pending_gigs_result ? $pending_gigs_result->fetch_all(MYSQLI_ASSOC) : [];

// Read and clear the one-time flash message stored by the previous redirect
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']); // Remove after reading so it only displays once

include 'includes/header.php';
?>

<?php
// Inject the 'approvals' colour theme CSS (orange/red palette) and shared admin layout
echo admin_theme_styles('approvals');
?>

<div class="admin-shell">
    <!-- Page heading -->
    <div class="admin-page-header">
        <h1 class="admin-page-title">Pending Approvals</h1>
    </div>

    <!-- Navigation pill bar — 'approvals' tab is highlighted as active -->
    <?php echo admin_render_nav('approvals'); ?>

    <!-- Flash notification banner
         success → green background (rgba(16,185,129,0.12))
         error   → red background  (rgba(248,113,113,0.12))
    -->
    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem;
            background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>;
            border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <!-- ── SUMMARY STAT CARDS ───────────────────────────────────────────────── -->
    <!-- Three cards show a live count of how many items are pending in each category -->
    <div class="metric-grid">
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending Users</div>
            <div class="metric-value"><?php echo count($pending_users); ?></div>
            <div class="metric-note">Users waiting for approval</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending Clubs</div>
            <div class="metric-value"><?php echo count($pending_clubs); ?></div>
            <div class="metric-note">Club registrations waiting for approval</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending Gigs</div>
            <div class="metric-value"><?php echo count($pending_gigs); ?></div>
            <div class="metric-note">Gigs waiting for approval</div>
        </div>
    </div>

    <!-- ── PENDING USERS TABLE ──────────────────────────────────────────────── -->
    <div class="section-card admin-panel" id="approvals">
        <div class="section-head">
            <h2>Pending Users</h2>
            <a href="admin_users.php">See all users</a><!-- link to the full users list -->
        </div>
        <?php if (empty($pending_users)): ?>
            <!-- Empty state — nothing to moderate right now -->
            <div class="muted-empty">No user registrations are waiting for review.</div>
        <?php else: ?>
            <!-- table-wrap enables horizontal scroll on narrow screens -->
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr>
                    <th>Name</th>
                    <th>Role</th>     <!-- student or client -->
                    <th>Joined</th>   <!-- registration date -->
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                        <tr>
                            <!-- Name (bold) + email (muted subtitle) stacked in one cell -->
                            <td>
                                <div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                <div style="color:#64748b; font-size:0.84rem;"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>

                            <!-- Role badge: green (pill-success) for students, blue (pill-info) for clients -->
                            <td><span class="pill <?php echo $user['role'] === 'student' ? 'pill-success' : 'pill-info'; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span></td>

                            <!-- Registration date — nowrap prevents ugly line breaks -->
                            <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>

                            <!-- Action buttons: blue Approve | red Suspend | red Reject -->
                            <td><div class="action-stack">
                                <?php echo admin_action_button('user', (int)$user['id'], 'approve', 'Approve'); ?>
                                <?php echo admin_action_button('user', (int)$user['id'], 'suspend', 'Suspend', 'danger'); ?>
                                <?php echo admin_action_button('user', (int)$user['id'], 'reject',  'Reject',  'danger'); ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── PENDING CLUBS TABLE ──────────────────────────────────────────────── -->
    <div class="section-card admin-panel" style="margin-top:1rem;" id="clubs">
        <div class="section-head">
            <h2>Pending Clubs</h2>
            <a href="admin_clubs.php">See all clubs</a><!-- link to the full clubs list -->
        </div>
        <?php if (empty($pending_clubs)): ?>
            <div class="muted-empty">No club requests are waiting for approval.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr>
                    <th>Club</th>
                    <th>Code</th>   <!-- unique club identifier code -->
                    <th>Share</th>  <!-- contribution_rate: % of revenue shared with the platform -->
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($pending_clubs as $club): ?>
                        <tr>
                            <!-- Club name in bold -->
                            <td><div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($club['club_name']); ?></div></td>

                            <!-- Club code displayed in a neutral pill badge -->
                            <td><span class="pill"><?php echo htmlspecialchars($club['club_code']); ?></span></td>

                            <!-- Contribution rate formatted to 2 decimal places -->
                            <td><?php echo number_format((float)$club['contribution_rate'], 2); ?>%</td>

                            <!-- Registration date -->
                            <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($club['created_at'])); ?></td>

                            <!-- Action buttons: blue Approve | red Suspend | red Reject -->
                            <td><div class="action-stack">
                                <?php echo admin_action_button('club', (int)$club['id'], 'approve', 'Approve'); ?>
                                <?php echo admin_action_button('club', (int)$club['id'], 'suspend', 'Suspend', 'danger'); ?>
                                <?php echo admin_action_button('club', (int)$club['id'], 'reject',  'Reject',  'danger'); ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── PENDING GIGS TABLE ───────────────────────────────────────────────── -->
    <div class="section-card admin-panel" style="margin-top:1rem;" id="gigs">
        <div class="section-head">
            <h2>Pending Gigs</h2>
            <a href="admin_gigs.php">See all gigs</a><!-- link to the full gigs list -->
        </div>
        <?php if (empty($pending_gigs)): ?>
            <div class="muted-empty">No gig submissions are waiting for review.</div>
        <?php else: ?>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr>
                    <th>Title</th>
                    <th>Freelancer</th>   <!-- student who posted the gig -->
                    <th>Price</th>
                    <th>Category</th>
                    <th>Submitted</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($pending_gigs as $gig): ?>
                        <tr>
                            <!-- Gig title in bold dark text -->
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($gig['title']); ?></td>

                            <!-- Freelancer name (joined from users table via student_id) -->
                            <td><?php echo htmlspecialchars($gig['student_name']); ?></td>

                            <!-- Price in Rs. formatted as a whole number -->
                            <td>Rs. <?php echo number_format((float)$gig['price'], 0); ?></td>

                            <!-- Category badge — always blue (pill-info) -->
                            <td><span class="pill pill-info"><?php echo htmlspecialchars($gig['category']); ?></span></td>

                            <!-- Submission date -->
                            <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($gig['created_at'])); ?></td>

                            <!-- Action buttons: blue Approve | red Suspend (no Reject for gigs) -->
                            <td><div class="action-stack">
                                <?php echo admin_action_button('gig', (int)$gig['id'], 'approve', 'Approve'); ?>
                                <?php echo admin_action_button('gig', (int)$gig['id'], 'suspend', 'Suspend', 'danger'); ?>
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