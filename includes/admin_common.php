<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin_common.php — Shared bootstrap for all admin pages
// Included at the top of every admin_*.php file.
// Handles: session start, DB connection, auth guard, helper functions & CSS.
// ─────────────────────────────────────────────────────────────────────────────

// Start a PHP session only if one is not already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the database connection ($conn is made available globally from db.php)
include_once __DIR__ . '/db.php';

// ── AUTHENTICATION GUARD ─────────────────────────────────────────────────────
// Redirect anyone who is not logged in or does not have the 'admin' role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── HELPER FUNCTION: Flash message + redirect ────────────────────────────────
/**
 * Saves a one-time flash notification to the session, then redirects.
 *
 * @param string $type    'success' | 'error'
 * @param string $message The human-readable result text shown to the admin
 * @param string $target  The page to redirect to after storing the flash
 */
function admin_flash_and_redirect(string $type, string $message, string $target = 'admin_dashboard.php'): void
{
    // Store message in session so the next page load can display it once
    $_SESSION['admin_flash'] = [
        'type'    => $type,
        'message' => $message,
    ];

    // Redirect to the target admin page and stop execution
    header('Location: ' . $target);
    exit();
}

// ── HELPER FUNCTION: Per-page colour theme ───────────────────────────────────
/**
 * Returns a <style> block that sets CSS custom properties (variables) for the
 * active page theme colour plus ALL shared admin layout CSS.
 *
 * Each admin page calls: echo admin_theme_styles('users');
 * The colour palette per page:
 *   dashboard → green  (#10b981)
 *   approvals → orange (#f97316)
 *   users     → indigo (#6366f1)
 *   clubs     → blue   (#3b82f6)
 *   gigs      → pink   (#ec4899)
 *
 * @param string $active  Key of the currently active section
 * @return string         Raw HTML <style> block ready to echo into the page
 */
function admin_theme_styles(string $active = 'dashboard'): string
{
    // Colour palette map — each section gets its own primary/secondary/gradient
    $colors = [
        'dashboard' => [
            'primary'     => '#10b981',         // green — calm overview tone
            'primary_rgb' => '16, 185, 129',
            'secondary'   => '#06b6d4',
            'gradient'    => 'linear-gradient(135deg, #10b981, #06b6d4)',
        ],
        'approvals' => [
            'primary'     => '#f97316',         // orange — signals action needed
            'primary_rgb' => '249, 115, 22',
            'secondary'   => '#ef4444',
            'gradient'    => 'linear-gradient(135deg, #f97316, #ef4444)',
        ],
        'users' => [
            'primary'     => '#6366f1',         // indigo — user management
            'primary_rgb' => '99, 102, 241',
            'secondary'   => '#8b5cf6',
            'gradient'    => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
        ],
        'clubs' => [
            'primary'     => '#3b82f6',         // blue — club/organisation theme
            'primary_rgb' => '59, 130, 246',
            'secondary'   => '#06b6d4',
            'gradient'    => 'linear-gradient(135deg, #3b82f6, #06b6d4)',
        ],
        'gigs' => [
            'primary'     => '#ec4899',         // pink — freelance/creative theme
            'primary_rgb' => '236, 72, 153',
            'secondary'   => '#f43f5e',
            'gradient'    => 'linear-gradient(135deg, #ec4899, #f43f5e)',
        ],
    ];

    // Fall back to dashboard theme if an unknown key is passed
    $theme = $colors[$active] ?? $colors['dashboard'];

    // Build and return the full CSS block injected into the page <head> area
    $style = '
<style>
    /* ── CSS custom properties (variables) set per active page ── */
    :root {
        --admin-primary:     ' . $theme['primary'] . ';
        --admin-primary-rgb: ' . $theme['primary_rgb'] . ';
        --admin-secondary:   ' . $theme['secondary'] . ';
        --admin-gradient:    ' . $theme['gradient'] . ';
    }

    /* ── Full-page admin shell with soft gradient background ── */
    .admin-shell {
        padding: 110px 5% 4rem;
        background:
            radial-gradient(circle at 10% 20%, rgba(var(--admin-primary-rgb), 0.08), transparent 45%),
            radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.06), transparent 45%),
            linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #1e293b;
        min-height: 100vh;
        font-family: \'Plus Jakarta Sans\', \'Outfit\', sans-serif;
    }

    /* ── Page header (title row) ── */
    .admin-page-header { margin-bottom: 1.5rem; }

    .admin-page-title {
        font-size: 1.85rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    /* ── Flex row used for grouping action buttons ── */
    .admin-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    /* ── Glassmorphism card / panel ── */
    .admin-panel {
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 20px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    /* Subtle lift on panel hover */
    .admin-panel:hover { box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06); }

    /* ── Top navigation pill bar ── */
    .admin-nav {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin: 1.5rem 0 2rem;
        background: rgba(241, 245, 249, 0.6);
        padding: 6px;
        border-radius: 999px;    /* fully rounded pill container */
        width: fit-content;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    /* Individual nav links */
    .admin-nav a {
        padding: 0.6rem 1.2rem;
        border-radius: 999px;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.88rem;
        transition: all 0.25s ease;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .admin-nav a i { font-size: 0.95rem; }

    /* Hover state: slight white background */
    .admin-nav a:hover { color: #0f172a; background: rgba(255, 255, 255, 0.5); }

    /* Active/current page link uses the page gradient + glow */
    .admin-nav a.active {
        background: var(--admin-gradient);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(var(--admin-primary-rgb), 0.25);
    }

    /* ── Responsive stats grid ── */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Individual stat card */
    .metric-card {
        padding: 1.5rem;
        min-height: 128px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    /* Decorative circle in the top-right corner of each card */
    .metric-card::after {
        content: \'\';
        position: absolute;
        top: -15px;
        right: -15px;
        width: 75px;
        height: 75px;
        border-radius: 50%;
        background: rgba(var(--admin-primary-rgb), 0.04);
        pointer-events: none;
    }

    /* Card lifts and highlights on hover */
    .metric-card:hover {
        transform: translateY(-4px);
        border-color: rgba(var(--admin-primary-rgb), 0.25);
        box-shadow: 0 12px 30px rgba(var(--admin-primary-rgb), 0.08);
    }

    /* ── Metric card typography ── */
    .metric-label {
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .metric-value {
        font-size: 2.2rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.04em;
        line-height: 1;
    }

    .metric-note { margin-top: 0.55rem; color: #64748b; font-size: 0.88rem; }

    /* ── Two-column layout for dashboard content areas ── */
    .content-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    /* Section card padding */
    .section-card { padding: 1.75rem; }

    /* Section heading row (title + optional "See all" link) */
    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        padding-bottom: 0.75rem;
    }

    .section-head h2 { color: #0f172a; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; }

    /* Section header links use the page primary colour */
    .section-head a {
        color: var(--admin-primary);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s;
    }

    .section-head a:hover { color: var(--admin-secondary); }

    /* ── Data table ── */
    .data-table { width: 100%; border-collapse: collapse; }

    .data-table th,
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        text-align: left;
        vertical-align: middle;
    }

    /* Table header — muted uppercase labels */
    .data-table th {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: rgba(248, 250, 252, 0.5);
    }

    .data-table td { color: #334155; font-size: 0.92rem; }

    /* Subtle row highlight on hover */
    .data-table tbody tr:hover td { background: rgba(248, 250, 252, 0.8); }

    /* ── Status/role pill badges ──
       pill          → neutral grey
       pill-success  → green  (active / approved)
       pill-warning  → orange (pending)
       pill-info     → blue   (info / role labels)
       pill-danger   → red    (suspended / rejected)
    */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        background: rgba(148, 163, 184, 0.1);
        color: #475569;
        text-transform: uppercase;
    }

    .pill-success { background: rgba(16, 185, 129, 0.12); color: #059669; }  /* green  */
    .pill-warning { background: rgba(249, 115, 22, 0.12);  color: #ea580c; }  /* orange */
    .pill-info    { background: rgba(59, 130, 246, 0.12);  color: #2563eb; }  /* blue   */
    .pill-danger  { background: rgba(239, 68, 68, 0.12);   color: #dc2626; }  /* red    */

    /* ── Metric cards that act as navigation links ── */
    a.metric-link {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-decoration: none;
        cursor: pointer;
    }
    a.metric-link .metric-value,
    a.metric-link .metric-label { color: inherit; }
    a.metric-link:hover {
        transform: translateY(-5px);
        border-color: rgba(var(--admin-primary-rgb), 0.3);
        box-shadow: 0 16px 36px rgba(var(--admin-primary-rgb), 0.1);
    }

    /* ── Utility classes ── */
    .table-wrap    { overflow-x: auto; }                                        /* horizontal scroll on small screens */
    .action-stack  { display: flex; gap: 0.45rem; flex-wrap: wrap; justify-content: flex-end; }  /* button group in table */
    .anchor-spacer { scroll-margin-top: 96px; }                                 /* offset for sticky header when using anchor links */

    /* ── Quick-link list (sidebar / profile back button) ── */
    .quick-links { display: grid; gap: 0.8rem; }

    .quick-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.1rem 1.2rem;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: rgba(255, 255, 255, 0.8);
        color: #0f172a;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    /* Icon inside quick link inherits primary colour */
    .quick-link i { color: var(--admin-primary); transition: transform 0.2s; }

    .quick-link:hover {
        transform: translateY(-2px);
        border-color: rgba(var(--admin-primary-rgb), 0.3);
        box-shadow: 0 6px 15px rgba(var(--admin-primary-rgb), 0.06);
    }

    /* Slide icon right on hover for a directional cue */
    .quick-link:hover i { transform: translateX(4px); }

    /* Empty state placeholder text */
    .muted-empty { color: #64748b; padding: 1.5rem 0; text-align: center; font-style: italic; }

    /* ── Action buttons rendered inside tables (forms) ── */
    .admin-panel form button {
        border: none;
        cursor: pointer;
        font-weight: 700;
        transition: all 0.2s;
    }
    .admin-panel form button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    /* ── Responsive: stack content grid on small screens ── */
    @media (max-width: 980px) {
        .content-grid { grid-template-columns: 1fr; }
    }
</style>';

    return $style;
}

// ── HELPER FUNCTION: Render navigation pill bar ──────────────────────────────
/**
 * Builds the horizontal nav bar shared by all admin pages.
 * The currently active section receives the 'active' CSS class
 * which applies the gradient highlight.
 *
 * @param string $active  Key matching one of the $items array keys
 * @return string         HTML string for the nav bar
 */
function admin_render_nav(string $active = 'dashboard'): string
{
    // Nav items: key => [label, href, Font Awesome icon class]
    $items = [
        'dashboard' => ['label' => 'Overview',  'href' => 'admin_dashboard.php', 'icon' => 'fas fa-chart-pie'],
        'approvals' => ['label' => 'Approvals', 'href' => 'admin_approve.php',   'icon' => 'fas fa-check-circle'],
        'users'     => ['label' => 'Users',     'href' => 'admin_users.php',     'icon' => 'fas fa-users'],
        'clubs'     => ['label' => 'Clubs',     'href' => 'admin_clubs.php',     'icon' => 'fas fa-shield-halved'],
        'gigs'      => ['label' => 'Gigs',      'href' => 'admin_gigs.php',      'icon' => 'fas fa-briefcase'],
    ];

    $html = '<div class="admin-nav">';
    foreach ($items as $key => $item) {
        // Add 'active' class to highlight the current page's nav item
        $class = $key === $active ? ' active' : '';
        $html .= '<a class="' . $class . '" href="' . $item['href'] . '"><i class="' . $item['icon'] . '"></i> ' . htmlspecialchars($item['label']) . '</a>';
    }
    $html .= '</div>';

    return $html;
}

// ── HELPER FUNCTION: Human-readable status label ─────────────────────────────
/**
 * Normalises DB status values into display-friendly text.
 * Gigs use 'approve' (not 'approved') in the DB — this corrects that.
 *
 * @param string $entity  'user' | 'club' | 'gig'
 * @param string $status  Raw DB status string
 * @return string         Readable label (e.g. 'Approved', 'Suspended')
 */
function admin_status_label(string $entity, string $status): string
{
    // Gigs store status as 'approve' — normalise to 'Approved' for display
    if ($entity === 'gig' && $status === 'approve') {
        return 'Approved';
    }

    // Explicitly handle 'suspended' for users and clubs/gigs
    if ($entity === 'user' && $status === 'suspended') {
        return 'Suspended';
    }

    if (($entity === 'club' || $entity === 'gig') && $status === 'suspended') {
        return 'Suspended';
    }

    // Default: capitalise the first letter of the raw status
    return ucfirst($status);
}

// ── HELPER FUNCTION: CSS pill class for status ───────────────────────────────
/**
 * Returns the correct pill colour class based on entity type and status.
 * Colour coding used consistently across all admin tables:
 *   orange (pill-warning) → pending
 *   red    (pill-danger)  → suspended
 *   green  (pill-success) → active / approved
 *   blue   (pill-info)    → everything else
 *
 * @param string $entity  'user' | 'club' | 'gig'
 * @param string $status  Raw DB status string
 * @return string         CSS class name
 */
function admin_status_class(string $entity, string $status): string
{
    if ($status === 'pending') {
        return 'pill-warning';   // orange — needs admin attention
    }

    if ($entity === 'user' && $status === 'suspended') {
        return 'pill-danger';    // red — user blocked
    }

    if (($entity === 'club' || $entity === 'gig') && $status === 'suspended') {
        return 'pill-danger';    // red — club/gig hidden
    }

    if ($status === 'approved' || $status === 'approve' || $status === 'active') {
        return 'pill-success';   // green — all clear
    }

    return 'pill-info';          // blue fallback
}

// ── HELPER FUNCTION: Suspend / Unsuspend toggle button ──────────────────────
/**
 * Renders a single POST form button that toggles between
 * 'suspend' and 'restore' depending on the entity's current state.
 *
 * Green button → currently suspended, click to Unsuspend
 * Red button   → currently active,    click to Suspend
 *
 * @param int    $id            DB row ID of the entity
 * @param bool   $is_suspended  Whether the entity is currently suspended
 * @param string $entity        'user' | 'club' — used in the confirm dialog
 * @return string               HTML form string
 */
function admin_suspend_toggle_button(int $id, bool $is_suspended, string $entity = 'user'): string
{
    if ($is_suspended) {
        // Green restore button — entity is currently blocked
        $action  = 'restore';
        $label   = '↺ Unsuspend';
        $style   = 'background:rgba(16,185,129,0.15);color:#059669;border:1px solid rgba(16,185,129,0.35);';
        $confirm = 'Unsuspend this ' . $entity . ' and restore access?';
    } else {
        // Red suspend button — entity is currently active
        $action  = 'suspend';
        $label   = '⊘ Suspend';
        $style   = 'background:rgba(239,68,68,0.12);color:#dc2626;border:1px solid rgba(239,68,68,0.28);';
        $confirm = 'Suspend this ' . $entity . '? It will be blocked immediately.';
    }

    // Inline POST form so the button submits without a separate <form> on the page
    return '<form method="POST" style="display:inline-block;">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<button type="submit" style="padding:0.45rem 0.9rem;font-size:0.82rem;border-radius:10px;font-weight:700;cursor:pointer;transition:all 0.2s;' . $style . '"'
        . ' onclick="return confirm(\'' . addslashes($confirm) . '\');">'
        . $label
        . '</button></form>';
}

// ── HELPER FUNCTION: Generic moderation action button ───────────────────────
/**
 * Renders a POST form button for moderation actions (Approve, Reject, etc.).
 * The 'entity' hidden field tells admin_approve.php which table to update.
 *
 * Button colours:
 *   primary → solid blue  (Approve)
 *   danger  → light red   (Suspend, Reject)
 *
 * @param string $entity   'user' | 'club' | 'gig'
 * @param int    $id       DB row ID of the entity
 * @param string $action   'approve' | 'suspend' | 'reject' | 'restore'
 * @param string $label    Button text visible to the admin
 * @param string $variant  'primary' (default blue) | 'danger' (red)
 * @return string          HTML form string
 */
function admin_action_button(string $entity, int $id, string $action, string $label, string $variant = 'primary'): string
{
    // Choose button colour based on the danger/primary variant
    $style = $variant === 'danger'
        ? 'background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.24);'  // red
        : 'background:#2563eb;color:#fff;border:1px solid #2563eb;';                                    // blue

    return '<form method="POST" style="display:inline-block;">'
        . '<input type="hidden" name="entity" value="' . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . '">'
        . '<input type="hidden" name="id" value="' . (int)$id . '">'
        . '<input type="hidden" name="action" value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">'
        . '<button type="submit" class="btn" style="padding:0.45rem 0.8rem;font-size:0.82rem;border-radius:10px;' . $style . '" onclick="return confirm(\'Apply this moderation action?\');">'
        . htmlspecialchars($label)
        . '</button></form>';
}

// ── HELPER FUNCTION: Safe parameterised write query ─────────────────────────
/**
 * Prepares, binds params, executes, and closes a single SQL statement.
 * Handles UPDATE and DELETE operations used by moderation actions.
 *
 * Uses call_user_func_array to dynamically bind a variable number of params
 * by reference (required by mysqli::bind_param).
 *
 * @param mysqli $conn    Active database connection
 * @param string $sql     Parameterised SQL string (? placeholders)
 * @param string $types   Bind type string e.g. 'si' = string, integer
 * @param array  $params  Ordered array of values matching the type string
 * @return bool           TRUE on success, FALSE on prepare or execute failure
 */
function admin_post_query(mysqli $conn, string $sql, string $types, array $params): bool
{
    // Prepare the SQL statement; return false if DB syntax is wrong
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    // Bind parameters only when both types and params are provided
    if ($types !== '' && $params !== []) {
        $bindParams   = [];
        $bindParams[] = $types; // First element must be the type string

        // mysqli::bind_param needs variables passed by reference
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// ── HELPER FUNCTION: Fetch a single integer COUNT from a SELECT query ────────
/**
 * Runs a simple "SELECT COUNT(*) AS total …" query and returns the integer.
 * Used on dashboard/listing pages to populate stat cards.
 *
 * @param mysqli $conn  Active database connection
 * @param string $sql   A SELECT query that returns a column named 'total'
 * @return int          The count value, or 0 if the query fails
 */
function admin_count_query(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0; // Query failed — return safe zero
    }

    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}