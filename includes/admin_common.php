<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

function admin_flash_and_redirect(string $type, string $message, string $target = 'admin_dashboard.php'): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    header('Location: ' . $target);
    exit();
}

function admin_theme_styles(string $active = 'dashboard'): string
{
    $colors = [
        'dashboard' => [
            'primary' => '#10b981',
            'primary_rgb' => '16, 185, 129',
            'secondary' => '#06b6d4',
            'gradient' => 'linear-gradient(135deg, #10b981, #06b6d4)',
        ],
        'approvals' => [
            'primary' => '#f97316',
            'primary_rgb' => '249, 115, 22',
            'secondary' => '#ef4444',
            'gradient' => 'linear-gradient(135deg, #f97316, #ef4444)',
        ],
        'users' => [
            'primary' => '#6366f1',
            'primary_rgb' => '99, 102, 241',
            'secondary' => '#8b5cf6',
            'gradient' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
        ],
        'clubs' => [
            'primary' => '#3b82f6',
            'primary_rgb' => '59, 130, 246',
            'secondary' => '#06b6d4',
            'gradient' => 'linear-gradient(135deg, #3b82f6, #06b6d4)',
        ],
        'gigs' => [
            'primary' => '#ec4899',
            'primary_rgb' => '236, 72, 153',
            'secondary' => '#f43f5e',
            'gradient' => 'linear-gradient(135deg, #ec4899, #f43f5e)',
        ],
    ];

    $theme = $colors[$active] ?? $colors['dashboard'];

    $style = '
<style>
    :root {
        --admin-primary: ' . $theme['primary'] . ';
        --admin-primary-rgb: ' . $theme['primary_rgb'] . ';
        --admin-secondary: ' . $theme['secondary'] . ';
        --admin-gradient: ' . $theme['gradient'] . ';
    }

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

    .admin-page-header {
        margin-bottom: 1.5rem;
    }

    .admin-page-title {
        font-size: 1.85rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .admin-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-panel {
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 20px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .admin-panel:hover {
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
    }

    .admin-nav {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin: 1.5rem 0 2rem;
        background: rgba(241, 245, 249, 0.6);
        padding: 6px;
        border-radius: 999px;
        width: fit-content;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

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

    .admin-nav a i {
        font-size: 0.95rem;
    }

    .admin-nav a:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.5);
    }

    .admin-nav a.active {
        background: var(--admin-gradient);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(var(--admin-primary-rgb), 0.25);
    }

    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        padding: 1.5rem;
        min-height: 128px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

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

    .metric-card:hover {
        transform: translateY(-4px);
        border-color: rgba(var(--admin-primary-rgb), 0.25);
        box-shadow: 0 12px 30px rgba(var(--admin-primary-rgb), 0.08);
    }

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

    .metric-note {
        margin-top: 0.55rem;
        color: #64748b;
        font-size: 0.88rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1.25fr 1fr;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .section-card {
        padding: 1.75rem;
    }

    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        padding-bottom: 0.75rem;
    }

    .section-head h2 {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .section-head a {
        color: var(--admin-primary);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s;
    }

    .section-head a:hover {
        color: var(--admin-secondary);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th,
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        text-align: left;
        vertical-align: middle;
    }

    .data-table th {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: rgba(248, 250, 252, 0.5);
    }

    .data-table td {
        color: #334155;
        font-size: 0.92rem;
    }

    .data-table tbody tr:hover td {
        background: rgba(248, 250, 252, 0.8);
    }

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

    .pill-success { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .pill-warning { background: rgba(249, 115, 22, 0.12); color: #ea580c; }
    .pill-info { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
    .pill-danger { background: rgba(239, 68, 68, 0.12); color: #dc2626; }

    /* Metric cards as clickable links */
    a.metric-link {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-decoration: none;
        cursor: pointer;
    }
    a.metric-link .metric-value,
    a.metric-link .metric-label {
        color: inherit;
    }
    a.metric-link:hover {
        transform: translateY(-5px);
        border-color: rgba(var(--admin-primary-rgb), 0.3);
        box-shadow: 0 16px 36px rgba(var(--admin-primary-rgb), 0.1);
    }

    .table-wrap { overflow-x: auto; }
    .action-stack { display: flex; gap: 0.45rem; flex-wrap: wrap; justify-content: flex-end; }
    .anchor-spacer { scroll-margin-top: 96px; }

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

    .quick-link i {
        color: var(--admin-primary);
        transition: transform 0.2s;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        border-color: rgba(var(--admin-primary-rgb), 0.3);
        box-shadow: 0 6px 15px rgba(var(--admin-primary-rgb), 0.06);
    }

    .quick-link:hover i {
        transform: translateX(4px);
    }

    .muted-empty { color: #64748b; padding: 1.5rem 0; text-align: center; font-style: italic; }

    /* Custom form elements within admin tables */
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

    @media (max-width: 980px) {
        .content-grid { grid-template-columns: 1fr; }
    }
</style>';

    return $style;
}

function admin_render_nav(string $active = 'dashboard'): string
{
    $items = [
        'dashboard' => ['label' => 'Overview', 'href' => 'admin_dashboard.php', 'icon' => 'fas fa-chart-pie'],
        'approvals' => ['label' => 'Approvals', 'href' => 'admin_approve.php', 'icon' => 'fas fa-check-circle'],
        'users' => ['label' => 'Users', 'href' => 'admin_users.php', 'icon' => 'fas fa-users'],
        'clubs' => ['label' => 'Clubs', 'href' => 'admin_clubs.php', 'icon' => 'fas fa-shield-halved'],
        'gigs' => ['label' => 'Gigs', 'href' => 'admin_gigs.php', 'icon' => 'fas fa-briefcase'],
    ];

    $html = '<div class="admin-nav">';
    foreach ($items as $key => $item) {
        $class = $key === $active ? ' active' : '';
        $html .= '<a class="' . $class . '" href="' . $item['href'] . '"><i class="' . $item['icon'] . '"></i> ' . htmlspecialchars($item['label']) . '</a>';
    }
    $html .= '</div>';

    return $html;
}

function admin_status_label(string $entity, string $status): string
{
    if ($entity === 'gig' && $status === 'approve') {
        return 'Approved';
    }

    if ($entity === 'user' && $status === 'suspend') {
        return 'Suspended';
    }

    if (($entity === 'club' || $entity === 'gig') && $status === 'suspended') {
        return 'Suspended';
    }

    return ucfirst($status);
}

function admin_status_class(string $entity, string $status): string
{
    if ($status === 'pending') {
        return 'pill-warning';
    }

    if ($entity === 'user' && $status === 'suspend') {
        return 'pill-danger';
    }

    if (($entity === 'club' || $entity === 'gig') && $status === 'suspended') {
        return 'pill-danger';
    }

    if ($status === 'approved' || $status === 'approve' || $status === 'active') {
        return 'pill-success';
    }

    return 'pill-info';
}

function admin_suspend_toggle_button(int $id, bool $is_suspended, string $entity = 'user'): string
{
    if ($is_suspended) {
        $action  = 'restore';
        $label   = '↺ Unsuspend';
        $style   = 'background:rgba(16,185,129,0.15);color:#059669;border:1px solid rgba(16,185,129,0.35);';
        $confirm = 'Unsuspend this ' . $entity . ' and restore access?';
    } else {
        $action  = 'suspend';
        $label   = '⊘ Suspend';
        $style   = 'background:rgba(239,68,68,0.12);color:#dc2626;border:1px solid rgba(239,68,68,0.28);';
        $confirm = 'Suspend this ' . $entity . '? It will be blocked immediately.';
    }

    return '<form method="POST" style="display:inline-block;">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<button type="submit" style="padding:0.45rem 0.9rem;font-size:0.82rem;border-radius:10px;font-weight:700;cursor:pointer;transition:all 0.2s;' . $style . '"'
        . ' onclick="return confirm(\'' . addslashes($confirm) . '\');">'
        . $label
        . '</button></form>';
}

function admin_action_button(string $entity, int $id, string $action, string $label, string $variant = 'primary'): string
{
    $style = $variant === 'danger'
        ? 'background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.24);'
        : 'background:#2563eb;color:#fff;border:1px solid #2563eb;';

    return '<form method="POST" style="display:inline-block;">'
        . '<input type="hidden" name="entity" value="' . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . '">'
        . '<input type="hidden" name="id" value="' . (int)$id . '">'
        . '<input type="hidden" name="action" value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">'
        . '<button type="submit" class="btn" style="padding:0.45rem 0.8rem;font-size:0.82rem;border-radius:10px;' . $style . '" onclick="return confirm(\'Apply this moderation action?\');">'
        . htmlspecialchars($label)
        . '</button></form>';
}

function admin_post_query(mysqli $conn, string $sql, string $types, array $params): bool
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '' && $params !== []) {
        $bindParams = [];
        $bindParams[] = $types;
        foreach ($params as $index => $value) {
            $bindParams[] = &$params[$index];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function admin_count_query(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}