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
<link rel="stylesheet" href="css/my-gigs.css">


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
                <h1>Reviews</h1>
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