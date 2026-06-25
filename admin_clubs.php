<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin_clubs.php — Club Management Page
// Lists all registered clubs with search, status stats, and moderation actions.
// ─────────────────────────────────────────────────────────────────────────────
include_once __DIR__ . '/includes/admin_common.php';

// ── HANDLE MODERATION ACTIONS (POST) ────────────────────────────────────────
// Only run when the admin submits one of the action forms in the table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = (string)$_POST['action'];
    $id     = (int)$_POST['id'];

    $ok      = false;
    $message = 'Unable to update the club.';

    if ($action === 'approve' || $action === 'restore') {
        // Set club status to 'approved' so the club can operate on the platform
        $ok      = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['approved', $id]);
        $message = 'Club restored successfully.';
    } elseif ($action === 'suspend') {
        // Suspend the club — hides it but does NOT delete it
        $ok      = admin_post_query($conn, 'UPDATE clubs SET status = ? WHERE id = ?', 'si', ['suspended', $id]);
        $message = 'Club suspended successfully.';
    } elseif ($action === 'reject') {
        // Permanently remove the club registration from the database
        $ok      = admin_post_query($conn, 'DELETE FROM clubs WHERE id = ?', 'i', [$id]);
        $message = 'Club removed successfully.';
    }

    // Flash success or error and reload the page to prevent form re-submission
    if ($ok) {
        admin_flash_and_redirect('success', $message, 'admin_clubs.php');
    }

    admin_flash_and_redirect('error', $message, 'admin_clubs.php');
}

// ── SEARCH / FETCH CLUBS ─────────────────────────────────────────────────────
// If a search term is present in the URL (?search=…), filter clubs by name
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    // Use a prepared statement with LIKE to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, club_name, club_code, status, contribution_rate, created_at FROM clubs WHERE club_name LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';          // Wrap search term with % wildcards
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $all_clubs_result = $stmt->get_result();
    $all_clubs        = $all_clubs_result ? $all_clubs_result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    // No search active — fetch ALL clubs ordered by newest first
    $all_clubs_result = $conn->query("SELECT id, club_name, club_code, status, contribution_rate, created_at FROM clubs ORDER BY created_at DESC");
    $all_clubs        = $all_clubs_result ? $all_clubs_result->fetch_all(MYSQLI_ASSOC) : [];
}

// ── STAT CARD COUNTS ─────────────────────────────────────────────────────────
// These counts are always from the full table (not filtered by search)
$stats = [
    'total'     => count($all_clubs),
    'pending'   => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'pending'"),
    'approved'  => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'approved'"),
    'suspended' => admin_count_query($conn, "SELECT COUNT(*) AS total FROM clubs WHERE status = 'suspended'"),
];

// ── RETRIEVE AND CLEAR FLASH MESSAGE ─────────────────────────────────────────
// Flash messages are stored in the session by admin_flash_and_redirect()
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']); // Clear after reading so it only shows once

include 'includes/header.php';
?>

<?php
// Inject the 'clubs' colour theme CSS (blue palette) and shared admin layout
echo admin_theme_styles('clubs');
?>

<div class="admin-shell">
    <!-- Page title -->
    <div class="admin-page-header">
        <h1 class="admin-page-title">Registered Clubs</h1>
    </div>

    <!-- Navigation pill bar — 'clubs' tab is highlighted as active -->
    <?php echo admin_render_nav('clubs'); ?>

    <!-- Flash notification banner (success = green, error = red) -->
    <?php if (!empty($flash)): ?>
        <div class="admin-panel" style="padding:1rem 1.2rem; margin-bottom:1rem;
            background: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(248,113,113,0.12)'; ?>;
            border-color: <?php echo $flash['type'] === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(248,113,113,0.3)'; ?>;">
            <strong style="color:#0f172a;"><?php echo htmlspecialchars($flash['type'] === 'success' ? 'Success' : 'Attention'); ?></strong>
            <div style="color:#475569; margin-top:0.3rem;"><?php echo htmlspecialchars($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <!-- ── STAT CARDS ──────────────────────────────────────────────────────── -->
    <!-- Four summary cards: Total | Approved (green) | Pending (orange) | Suspended (red) -->
    <div class="metric-grid">
        <div class="admin-panel metric-card">
            <div class="metric-label">Total Clubs</div>
            <div class="metric-value"><?php echo $stats['total']; ?></div>
            <div class="metric-note">All registered clubs</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Approved</div>
            <div class="metric-value" style="color: #10b981;"><?php echo $stats['approved']; ?></div><!-- green = active -->
            <div class="metric-note">Currently active clubs</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Pending</div>
            <div class="metric-value" style="color: #f97316;"><?php echo $stats['pending']; ?></div><!-- orange = needs review -->
            <div class="metric-note">Awaiting review</div>
        </div>
        <div class="admin-panel metric-card">
            <div class="metric-label">Suspended</div>
            <div class="metric-value" style="color: #ef4444;"><?php echo $stats['suspended']; ?></div><!-- red = blocked -->
            <div class="metric-note">Temporarily disabled clubs</div>
        </div>
    </div>

    <!-- ── CLUBS TABLE ─────────────────────────────────────────────────────── -->
    <div class="section-card admin-panel">
        <div class="section-head" style="flex-wrap: wrap;">
            <h2>All Clubs</h2>

            <!-- Search form — submits a GET request with ?search=… -->
            <form method="GET" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" name="search" placeholder="Search club..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    style="padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Search</button>
                <?php if (!empty($_GET['search'])): ?>
                    <!-- Clear button removes the search term and reloads all clubs -->
                    <a href="admin_clubs.php" class="btn"
                        style="padding: 0.4rem 1rem; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Shortcut link to the pending approvals queue -->
            <a href="admin_approve.php">Pending queue</a>
        </div>

        <?php if (empty($all_clubs)): ?>
            <!-- Empty state shown when search returns no results or no clubs exist -->
            <div class="muted-empty">No clubs found.</div>
        <?php else: ?>
            <!-- Horizontally scrollable table wrapper for narrow screens -->
            <div class="table-wrap">
            <table class="data-table">
                <!-- Column headings -->
                <thead><tr>
                    <th>Club</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Share</th>  <!-- contribution_rate = % revenue shared with the platform -->
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($all_clubs as $club): ?>
                        <tr>
                            <!-- Club name in bold dark text -->
                            <td style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($club['club_name']); ?></td>

                            <!-- Unique club code pill (neutral grey) -->
                            <td><span class="pill"><?php echo htmlspecialchars($club['club_code']); ?></span></td>

                            <!-- Coloured status pill — uses admin_status_class() for colour, admin_status_label() for text -->
                            <td><span class="pill <?php echo admin_status_class('club', $club['status']); ?>">
                                <?php echo htmlspecialchars(admin_status_label('club', $club['status'])); ?>
                            </span></td>

                            <!-- Revenue share percentage formatted to 2 decimal places -->
                            <td><?php echo number_format((float)$club['contribution_rate'], 2); ?>%</td>

                            <!-- Registration date, no-wrap to prevent awkward line breaks -->
                            <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($club['created_at'])); ?></td>

                            <!-- Action buttons vary based on the club's current status -->
                            <td><div class="action-stack">
                                <?php if ($club['status'] === 'pending'): ?>
                                    <!-- Pending clubs: show Approve, Suspend, and Reject -->
                                    <?php echo admin_action_button('club', (int)$club['id'], 'approve', '✓ Approve'); ?>
                                    <?php echo admin_suspend_toggle_button((int)$club['id'], false, 'club'); ?>
                                    <?php echo admin_action_button('club', (int)$club['id'], 'reject', '✕ Reject', 'danger'); ?>
                                <?php else: ?>
                                    <!-- Approved/Suspended clubs: show the toggle between Suspend ↔ Unsuspend -->
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