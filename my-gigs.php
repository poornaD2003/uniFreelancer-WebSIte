<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';
include 'includes/header.php';

// Determine current page for sidebar active state
$current_page = basename($_SERVER['PHP_SELF']);

$user_id = (int)$_SESSION['user_id'];

// ── Fetch all reviews on current student's gigs ──────────────────
$query = "SELECT 
            gr.id as review_id,
            gr.gig_id,
            gr.user_id,
            gr.rating,
            gr.review_text,
            gr.created_at,
            g.title as gig_title,
            g.category,
            u.fullname as reviewer_name
          FROM gig_reviews gr
          JOIN gigs g ON gr.gig_id = g.id
          JOIN users u ON gr.user_id = u.id
          WHERE g.student_id = ?
          ORDER BY gr.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$stmt->close();

$all_reviews = [];
while ($r = $reviews_result->fetch_assoc()) {
    $all_reviews[] = $r;
}

// Show all reviews in "Jobs"
$current_reviews = $all_reviews;

// Calculate stats
$total_reviews = count($all_reviews);
$avg_rating = 0;
if ($total_reviews > 0) {
    $sum = array_sum(array_map(fn($r) => $r['rating'], $all_reviews));
    $avg_rating = round($sum / $total_reviews, 1);
}

function render_stars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= round($rating) ? 'fas' : 'far';
        $html .= "<i class='$cls fa-star'></i>";
    }
    return $html;
}

function time_ago($datetime) {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    
    if ($diff->y > 0) return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
    if ($diff->m > 0) return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
    if ($diff->d > 0) return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    if ($diff->h > 0) return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    if ($diff->i > 0) return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
    return "Just now";
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/student.css">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --accent:       #10b981;
    --accent-soft:  rgba(124,58,237,.08);
    --green:        #10b981;
    --star:         #f59e0b;
    --ink:          #1a1a2e;
    --muted:        #6b7280;
    --border:       #e5e7eb;
    --bg:           #f9fafb;
    --white:        #ffffff;
    --r-lg:         16px;
    --r-md:         12px;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--ink);
    margin-top: 0;
}

/* ── STUDENT SIDEBAR ───────────────────────────────────────────── */
.student-layout {
    display: flex;
    min-height: 100vh;
    background: var(--bg);
}

.student-sidebar {
    width: 256px;
    background: #1f2937;
    color: #fff;
    padding: 24px 0;
    position: fixed;
    left: 0;
    top: 70px;
    height: calc(100vh - 70px);
    overflow-y: auto;
    z-index: 100;
    box-shadow: 2px 0 8px rgba(0,0,0,.1);
}

.student-sidebar::-webkit-scrollbar { width: 6px; }
.student-sidebar::-webkit-scrollbar-track { background: rgba(0,0,0,.1); }
.student-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 3px; }
.student-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.3); }

.sidebar-header {
    padding: 16px 24px;
    margin-bottom: 24px;
    border-bottom: 1px solid rgba(255,255,255,.1);
}

.sidebar-title {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--green);
    margin: 0;
}


.student-content {
    margin-left: 256px;
    flex: 1;
}

/* ── PAGE HEADER ────────────────────────────────────────────────── */
.page-header {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 32px 24px;
}

.header-inner {
    max-width: 1200px;
    margin: 0 auto;
}

.header-title {
    font-family: 'Syne', sans-serif;
    font-size: 32px;
    font-weight: 800;
    color: var(--ink);
    margin-bottom: 6px;
}

.header-sub {
    font-size: 14px;
    color: var(--muted);
}

/* ── MAIN CONTAINER ────────────────────────────────────────────── */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 24px 60px;
}

/* ── STATS CARD ────────────────────────────────────────────────── */
.stats-card {
    background: var(--white);
    border-radius: var(--r-lg);
    border: 1px solid var(--border);
    padding: 28px;
    margin-bottom: 28px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 28px;
}

.stat-item {
    text-align: center;
}

.stat-num {
    font-family: 'Syne', sans-serif;
    font-size: 42px;
    font-weight: 800;
    color: var(--accent);
    display: block;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .05em;
}

/* ── REVIEW SECTION ────────────────────────────────────────────── */
.reviews-section {
    background: var(--white);
    border-radius: var(--r-lg);
    border: 1px solid var(--border);
    overflow: hidden;
}

/* Tab navigation */
.review-tabs {
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border);
    background: #fafafa;
}

.review-tab {
    flex: 0 0 auto;
    padding: 14px 20px;
    font-size: 14px;
    font-weight: 500;
    color: var(--muted);
    cursor: default;
    border-bottom: 3px solid transparent;
    transition: color .2s, border-color .2s;
}

.review-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    font-weight: 600;
}

/* Review list */
.review-list {
    padding: 28px;
}

.review-card {
    display: flex;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid var(--border);
    transition: background .2s;
    position: relative;
}

.review-card:last-child {
    border-bottom: none;
}

.review-card:hover {
    background: #fafafa;
}

.review-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2d5a3d, #1a4a3a);
    color: #fff;
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.review-content {
    flex: 1;
    min-width: 0;
}

.review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    gap: 12px;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.review-name {
    font-weight: 600;
    font-size: 15px;
    color: var(--ink);
}

.review-stars {
    display: flex;
    gap: 2px;
    font-size: 13px;
}

.review-stars i {
    color: var(--star);
}

.review-stars i.far {
    color: #d1d5db;
}

.review-time {
    font-size: 12px;
    color: var(--muted);
    white-space: nowrap;
}

.review-reply-btn {
    background: #f3f4f6;
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--muted);
    font-size: 14px;
    transition: background .2s, color .2s;
}

.review-reply-btn:hover {
    background: var(--accent-soft);
    color: var(--accent);
}

.review-text {
    font-size: 14px;
    line-height: 1.75;
    color: #4b5563;
    margin-bottom: 12px;
}

.review-action {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.review-action-link {
    color: var(--green);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: color .2s;
}

.review-action-link:hover {
    color: #059669;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
    display: block;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: var(--muted);
}

/* ── RESPONSIVE ────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .student-sidebar {
        transform: translateX(-100%);
        transition: transform .3s ease;
        width: 220px;
        box-shadow: 8px 0 16px rgba(0,0,0,.15);
    }

    .student-sidebar.open {
        transform: translateX(0);
    }

    .student-content {
        margin-left: 0;
    }

    .header-title {
        font-size: 24px;
    }

    .stats-card {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .review-card {
        gap: 16px;
    }

    .review-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .review-avatar {
        width: 48px;
        height: 48px;
    }

    .review-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .review-tabs::-webkit-scrollbar {
        display: none;
    }
}
</style>

<!-- ══════════════════════════ PAGE HEADER ══════════════════════════ -->
<div class="student-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar"><h2>Student Hub</h2><nav>
        <a href="student-dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="student-post-job.php"><i class="fas fa-briefcase"></i> Post Gig</a>
        <a href="student-orders.php" class="active"><i class="fas fa-shopping-basket"></i> Orders</a>
        <a href="my-gigs.php"><i class="fas fa-tasks"></i> My Reviews</a>

    </nav></aside>

    <!-- CONTENT -->
    <div class="student-content">
        <div class="page-header">
            <div class="header-inner">
                <br><br>
                <h1 class="header-title">Reviews</h1>
                <p class="header-sub">Manage and respond to client reviews on your gigs</p>
            </div>
        </div>

<!-- ══════════════════════════ CONTAINER ══════════════════════════ -->
<div class="container">

    
    

    <!-- REVIEWS SECTION -->
    <div class="reviews-section">
        <!-- Tab Navigation -->
        <div class="review-tabs">
            <div class="review-tab active">
                Gigs (<?php echo count($all_reviews); ?>)
            </div>
        </div>

        <!-- Review List -->
        <div class="review-list">
            <?php if (count($current_reviews) > 0): ?>
                <?php foreach ($current_reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-avatar">
                            <?php echo strtoupper(substr($review['reviewer_name'], 0, 1)); ?>
                        </div>

                        <div class="review-content">
                            <div class="review-header">
                                <div class="review-meta">
                                    <span class="review-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                    <div class="review-stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            $cls = $i <= $review['rating'] ? 'fas' : 'far';
                                            echo "<i class='$cls fa-star'></i>";
                                        }
                                        ?>
                                    </div>
                                    <span class="review-time">Published <?php echo time_ago($review['created_at']); ?></span>
                                </div>
                            </div>

                            <p class="review-text">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </p>

                            <div class="review-action">
                                <span style="color: var(--muted);">On:</span>
                                <a href="freelancer_gig.php?id=<?php echo $review['gig_id']; ?>" style="text-decoration:none;color:var(--accent);font-weight:500">
                                    <?php echo htmlspecialchars(substr($review['gig_title'], 0, 50)); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash empty-icon"></i>
                    <h3 class="empty-title">No reviews yet</h3>
                    <p class="empty-text">
                        <?php
                        if ($total_reviews === 0) {
                            echo "Your gigs haven't received any reviews yet. Keep delivering great work!";
                        } else {
                            echo "No reviews in this category. Check other tabs!";
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>    </div><!-- /student-content -->
</div><!-- /student-layout -->

<?php include 'includes/footer.php'; ?>