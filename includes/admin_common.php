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

function admin_theme_styles(): string
{
    return <<<'CSS'
<style>
    .admin-shell {
        padding: 110px 5% 4rem;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.10), transparent 34%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.10), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        color: #1f2937;
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
        color: #0f172a;
    }

    .admin-hero p {
        color: #475569;
        max-width: 760px;
        line-height: 1.6;
    }

    .admin-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-panel {
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 24px;
        backdrop-filter: blur(18px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .admin-nav {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin: 1rem 0 1.4rem;
    }

    .admin-nav a {
        padding: 0.8rem 1rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        color: #475569;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.78);
    }

    .admin-nav a:hover,
    .admin-nav a.active {
        border-color: rgba(59, 130, 246, 0.4);
        color: #0f172a;
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
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 0.6rem;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.04em;
    }

    .metric-note {
        margin-top: 0.55rem;
        color: #475569;
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
        color: #0f172a;
        font-size: 1.15rem;
        letter-spacing: -0.02em;
    }

    .section-head a {
        color: #2563eb;
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
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .data-table td {
        color: #334155;
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
        background: rgba(148, 163, 184, 0.14);
        color: #334155;
    }

    .pill-success { background: rgba(16, 185, 129, 0.12); color: #047857; }
    .pill-warning { background: rgba(245, 158, 11, 0.12); color: #b45309; }
    .pill-info { background: rgba(96, 165, 250, 0.12); color: #2563eb; }
    .pill-danger { background: rgba(248, 113, 113, 0.12); color: #b91c1c; }

    .table-wrap { overflow-x: auto; }
    .action-stack { display: flex; gap: 0.45rem; flex-wrap: wrap; justify-content: flex-end; }
    .anchor-spacer { scroll-margin-top: 96px; }

    .quick-links { display: grid; gap: 0.8rem; }

    .quick-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.05rem;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.82);
        color: #0f172a;
        text-decoration: none;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        border-color: rgba(59, 130, 246, 0.4);
    }

    .muted-empty { color: #64748b; padding: 1rem 0; }

    @media (max-width: 980px) {
        .content-grid { grid-template-columns: 1fr; }
    }
</style>
CSS;
}

function admin_render_nav(string $active = 'dashboard'): string
{
    $items = [
        'dashboard' => ['label' => 'Overview', 'href' => 'admin_dashboard.php'],
        'approvals' => ['label' => 'Approvals', 'href' => 'admin_approve.php'],
        'users' => ['label' => 'Users', 'href' => 'admin_users.php'],
        'clubs' => ['label' => 'Clubs', 'href' => 'admin_clubs.php'],
        'gigs' => ['label' => 'Gigs', 'href' => 'admin_gigs.php'],
    ];

    $html = '<div class="admin-nav">';
    foreach ($items as $key => $item) {
        $class = $key === $active ? ' active' : '';
        $html .= '<a class="' . $class . '" href="' . $item['href'] . '">' . htmlspecialchars($item['label']) . '</a>';
    }
    $html .= '</div>';

    return $html;
}

function admin_status_label(string $entity, string $status): string
{
    if ($entity === 'gig' && $status === 'approve') {
        return 'Approved';
    }

    if ($entity === 'user' && $status === 'inactive') {
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

    if ($entity === 'user' && $status === 'inactive') {
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