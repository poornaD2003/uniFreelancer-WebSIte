<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'includes/db.php';
include 'includes/header.php';

$msg = "";
$error_msg = "";

// ── 1. Gig ID from URL ──────────────────────────────────────────
$gig_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($gig_id === 0) {
    echo "<div style='padding-top:140px;text-align:center'><h2>Gig not found!</h2></div>";
    include 'includes/footer.php'; exit();
}

// ── 2. Create reviews table if it doesn't exist ─────────────────
$conn->query("CREATE TABLE IF NOT EXISTS gig_reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    gig_id      INT NOT NULL,
    user_id     INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (gig_id, user_id),
    FOREIGN KEY (gig_id)  REFERENCES gigs(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// ── 3. Handle Review Submission ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); exit();
    }
    $reviewer_id  = (int)$_SESSION['user_id'];
    $rating_val   = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text  = trim($conn->real_escape_string($_POST['review_text'] ?? ''));

    if ($rating_val < 1 || $rating_val > 5) {
        $error_msg = "Please select a star rating (1–5).";
    } elseif (empty($review_text)) {
        $error_msg = "Please write a review before submitting.";
    } else {
        // Check: only users who placed an order on this gig can review
        $order_check = $conn->prepare("SELECT orderID FROM orders WHERE client_id = ? AND gig_id = ? LIMIT 1");
        $order_check->bind_param("ii", $reviewer_id, $gig_id);
        $order_check->execute();
        $order_check->store_result();
        $has_ordered = $order_check->num_rows > 0;
        $order_check->close();

        if (!$has_ordered) {
            $error_msg = "You can only review a gig after placing an order.";
        } else {
            // Check for duplicate review
            $dup = $conn->prepare("SELECT id FROM gig_reviews WHERE gig_id = ? AND user_id = ? LIMIT 1");
            $dup->bind_param("ii", $gig_id, $reviewer_id);
            $dup->execute(); $dup->store_result();
            if ($dup->num_rows > 0) {
                $error_msg = "You have already reviewed this gig.";
            } else {
                $ins = $conn->prepare("INSERT INTO gig_reviews (gig_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
                $ins->bind_param("iiis", $gig_id, $reviewer_id, $rating_val, $review_text);
                if ($ins->execute()) {
                    $msg = "Your review has been posted!";
                } else {
                    $error_msg = "Could not save your review. Please try again.";
                }
                $ins->close();
            }
            $dup->close();
        }
    }
}

// ── 4. Handle Order Submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); exit();
    }
    $client_id = (int)$_SESSION['user_id'];
    $seller_id_post = (int)$_POST['seller_id'];
    if ($client_id === $seller_id_post) {
        $error_msg = "You cannot place an order on your own gig!";
    } else {
        $s = $conn->prepare("INSERT INTO orders (client_id,student_id,gig_id,status) VALUES (?, ?, ?, 'pending')");
        if ($s) {
            $s->bind_param("iii", $client_id, $seller_id_post, $gig_id);
            $msg = $s->execute()
                ? "Order placed successfully! The freelancer will contact you soon."
                : "Failed to place order. Please try again.";
            $s->close();
        }
    }
}

// ── 5. Fetch Gig + Seller ───────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT g.*, u.fullname as student_name
     FROM gigs g JOIN users u ON g.student_id = u.id
     WHERE g.id = ? AND g.status = 'approve' LIMIT 1"
);
$stmt->bind_param("i", $gig_id);
$stmt->execute();
$gig = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gig) {
    echo "<div style='padding-top:140px;text-align:center'><h2>This gig is no longer available.</h2></div>";
    include 'includes/footer.php'; exit();
}

// ── 6. Fetch Reviews + Stats ────────────────────────────────────
$rev_stmt = $conn->prepare(
    "SELECT r.*, u.fullname as reviewer_name
     FROM gig_reviews r JOIN users u ON r.user_id = u.id
     WHERE r.gig_id = ?
     ORDER BY r.created_at DESC"
);
$rev_stmt->bind_param("i", $gig_id);
$rev_stmt->execute();
$reviews_result = $rev_stmt->get_result();
$rev_stmt->close();

$reviews       = [];
$total_rating  = 0;
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
while ($r = $reviews_result->fetch_assoc()) {
    $reviews[] = $r;
    $total_rating += $r['rating'];
    $rating_counts[$r['rating']]++;
}
$review_count  = count($reviews);
$avg_rating    = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

// ── 7. Check if current user already reviewed / ordered ─────────
$user_reviewed = false;
$user_ordered  = false;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    // check ordered
    $oc = $conn->prepare("SELECT orderId FROM orders WHERE client_id = ? AND gig_id = ? LIMIT 1");
    $oc->bind_param("ii", $uid, $gig_id);
    $oc->execute(); $oc->store_result();
    $user_ordered = $oc->num_rows > 0;
    $oc->close();
    // check reviewed
    $rc = $conn->prepare("SELECT id FROM gig_reviews WHERE gig_id = ? AND user_id = ? LIMIT 1");
    $rc->bind_param("ii", $gig_id, $uid);
    $rc->execute(); $rc->store_result();
    $user_reviewed = $rc->num_rows > 0;
    $rc->close();
}

// ── 8. Other gigs by seller ─────────────────────────────────────
$seller_id = $gig['student_id'];
$other_stmt = $conn->prepare("SELECT * FROM gigs WHERE student_id = ? AND id != ? AND status = 'approve' LIMIT 4");
$other_stmt->bind_param("ii", $seller_id, $gig_id);
$other_stmt->execute();
$other_gigs_result = $other_stmt->get_result();
$other_stmt->close();
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">

<style>
/* ── Tokens ─────────────────────────────────────────────────────── */
:root {
    --hero-start:  #0f0c29;
    --hero-mid:    #302b63;
    --hero-end:    #24243e;
    --accent:      #7c3aed;
    --accent-soft: rgba(124,58,237,.12);
    --cta:         #10b981;
    --cta-hover:   #059669;
    --star:        #f59e0b;
    --white:       #ffffff;
    --ink:         #1a1a2e;
    --muted:       #64748b;
    --border:      #e8eaf0;
    --bg:          #f5f6fa;
    --r-lg:        20px;
    --r-md:        14px;
    --r-sm:        8px;
    --sh-card:     0 4px 24px rgba(15,12,41,.06);
    --sh-lift:     0 12px 40px rgba(15,12,41,.10);
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--ink); margin: 0; }

/* ── HERO BAND ───────────────────────────────────────────────────── */
.gig-hero {
    background: linear-gradient(135deg, var(--hero-start) 0%, var(--hero-mid) 55%, var(--hero-end) 100%);
    padding-top: 120px;
    padding-bottom: 60px;
    position: relative;
    overflow: hidden;
}
.gig-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 50%, rgba(124,58,237,.18) 0%, transparent 55%),
        radial-gradient(circle at 80% 20%, rgba(99,102,241,.12) 0%, transparent 40%);
    pointer-events: none;
}
.hero-inner {
    max-width: 1160px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    gap: 40px;
    align-items: flex-start;
    position: relative;
    z-index: 1;
}
.hero-left { flex: 1.7; min-width: 0; }
.hero-right { flex: 1; min-width: 320px; max-width: 380px; }

.hero-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 14px; font-size: 13px; color: rgba(255,255,255,.5);
}
.hero-breadcrumb a { color: rgba(255,255,255,.5); text-decoration: none; }
.hero-breadcrumb a:hover { color: rgba(255,255,255,.8); }
.hero-breadcrumb span { color: rgba(255,255,255,.25); }

.gig-img-wrap {
    border-radius: var(--r-lg); overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.35); margin-bottom: 24px; position: relative;
}
.gig-img-wrap::after {
    content: ''; position: absolute; inset: 0; border-radius: var(--r-lg);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08); pointer-events: none;
}
.gig-img { width: 100%; max-height: 420px; object-fit: cover; display: block; }

.cat-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--accent-soft); border: 1px solid rgba(124,58,237,.3);
    color: #a78bfa; padding: 5px 14px; border-radius: 30px;
    font-size: 11px; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; margin-bottom: 12px;
}
.gig-title-hero {
    font-family: 'Syne', sans-serif; font-size: 34px; font-weight: 800;
    color: var(--white); line-height: 1.2; margin: 0 0 16px; letter-spacing: -.3px;
}

/* hero rating summary */
.hero-rating-row {
    display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
}
.hero-stars { display: flex; gap: 2px; }
.hero-stars i { color: var(--star); font-size: 16px; }
.hero-stars i.empty { color: rgba(255,255,255,.25); }
.hero-avg { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: #fff; }
.hero-rev-count { font-size: 13px; color: rgba(255,255,255,.55); }

.hero-seller { display: flex; align-items: center; gap: 14px; }
.seller-av {
    width: 46px; height: 46px; border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff; font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 0 3px rgba(255,255,255,.12); flex-shrink: 0;
}
.seller-name { font-weight: 600; font-size: 15px; color: #fff; display: flex; align-items: center; gap: 6px; }
.vdot {
    width: 16px; height: 16px; background: #3b82f6; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
}
.vdot i { font-size: 9px; color: #fff; }
.seller-sub { color: rgba(255,255,255,.45); font-size: 13px; font-weight: 500; margin-top: 2px; }

/* ── ORDER CARD ──────────────────────────────────────────────────── */
.order-card {
    background: var(--white); border-radius: var(--r-lg); padding: 28px;
    box-shadow: 0 24px 60px rgba(0,0,0,.22); position: sticky; top: 110px;
}
.order-card-top { padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
.price-eyebrow { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.price-display { font-family: 'Syne', sans-serif; font-size: 38px; font-weight: 800; color: var(--ink); line-height: 1; margin-bottom: 4px; }
.price-display sup { font-size: 18px; font-weight: 700; vertical-align: top; margin-top: 8px; display: inline-block; }
.price-note { font-size: 12px; color: var(--muted); }

/* order card rating summary */
.card-rating-row {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 0; margin-bottom: 4px;
}
.card-stars { display: flex; gap: 2px; }
.card-stars i { color: var(--star); font-size: 13px; }
.card-stars i.empty { color: #d1d5db; }
.card-rating-text { font-size: 13px; color: var(--muted); }
.card-rating-text strong { color: var(--ink); }

.delivery-strip { display: flex; gap: 8px; margin-bottom: 20px; }
.d-badge { flex: 1; background: #f1f5f9; border-radius: var(--r-sm); padding: 10px 8px; text-align: center; }
.d-badge-icon { font-size: 16px; margin-bottom: 4px; }
.d-badge-label { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.d-badge-val { font-size: 13px; font-weight: 700; color: var(--ink); margin-top: 1px; }

.trust-list { list-style: none; padding: 0; margin: 0 0 24px; }
.trust-list li { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; font-weight: 500; padding: 7px 0; }
.trust-list li:not(:last-child) { border-bottom: 1px solid var(--border); }
.trust-icon { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.trust-icon.green  { background: rgba(16,185,129,.1); color: var(--cta); }
.trust-icon.blue   { background: rgba(59,130,246,.1); color: #3b82f6; }
.trust-icon.violet { background: var(--accent-soft); color: var(--accent); }

.btn-order {
    width: 100%; padding: 16px; background: var(--cta); color: #fff;
    border: none; border-radius: var(--r-md); font-size: 16px; font-weight: 600;
    font-family: 'Inter', sans-serif; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 9px;
    transition: background .2s, transform .2s, box-shadow .2s; text-decoration: none;
    box-shadow: 0 4px 14px rgba(16,185,129,.3);
}
.btn-order:hover { background: var(--cta-hover); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,.4); }
.btn-order.disabled-own { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; box-shadow: none; transform: none; }
.btn-login {
    width: 100%; padding: 16px; background: var(--accent); color: #fff;
    border-radius: var(--r-md); font-size: 15px; font-weight: 600;
    font-family: 'Inter', sans-serif; text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 9px;
    transition: background .2s, transform .2s; box-shadow: 0 4px 14px rgba(124,58,237,.25);
}
.btn-login:hover { background: #6d28d9; transform: translateY(-2px); }
.secure-note {
    text-align: center; font-size: 12px; color: var(--muted); margin-top: 12px;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}

.alert { padding: 14px 16px; border-radius: var(--r-sm); margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: flex-start; gap: 10px; line-height: 1.5; }
.alert i { flex-shrink: 0; margin-top: 1px; }
.alert-success { background: rgba(16,185,129,.08); color: #065f46; border: 1px solid rgba(16,185,129,.2); }
.alert-error   { background: rgba(239,68,68,.07);  color: #991b1b; border: 1px solid rgba(239,68,68,.15); }

/* ── CONTENT AREA ────────────────────────────────────────────────── */
.content-wrap { max-width: 1160px; margin: 0 auto; padding: 48px 24px 0; }

.white-card {
    background: var(--white); border-radius: var(--r-lg); padding: 32px;
    box-shadow: var(--sh-card); border: 1px solid var(--border); margin-bottom: 28px;
}
.sec-eyebrow { font-size: 11px; font-weight: 700; color: var(--accent); letter-spacing: .1em; text-transform: uppercase; margin-bottom: 8px; }
.sec-heading { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 700; color: var(--ink); margin: 0 0 18px; }
.gig-desc { font-size: 15.5px; line-height: 1.85; color: #4b5563; white-space: pre-wrap; margin: 0; }

.include-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.include-list li { display: flex; align-items: center; gap: 9px; font-size: 14px; color: #374151; font-weight: 500; }
.include-list i { color: var(--cta); font-size: 13px; flex-shrink: 0; }

/* ── RATING OVERVIEW ─────────────────────────────────────────────── */
.rating-overview {
    display: flex;
    align-items: center;
    gap: 40px;
    padding-bottom: 28px;
    margin-bottom: 28px;
    border-bottom: 1px solid var(--border);
}
.rating-big-num {
    text-align: center;
    flex-shrink: 0;
}
.rating-big-num .num {
    font-family: 'Syne', sans-serif;
    font-size: 64px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
    display: block;
}
.rating-big-num .stars-lg { display: flex; gap: 4px; justify-content: center; margin: 8px 0 6px; }
.rating-big-num .stars-lg i { color: var(--star); font-size: 22px; }
.rating-big-num .stars-lg i.empty { color: #d1d5db; }
.rating-big-num .rev-label { font-size: 13px; color: var(--muted); font-weight: 500; }

.rating-bars { flex: 1; }
.bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.bar-row:last-child { margin-bottom: 0; }
.bar-label { font-size: 13px; font-weight: 600; color: var(--ink); width: 14px; text-align: right; flex-shrink: 0; }
.bar-track { flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, var(--star), #fbbf24); border-radius: 4px; transition: width .6s ease; }
.bar-count { font-size: 12px; color: var(--muted); width: 20px; text-align: left; flex-shrink: 0; }

/* ── REVIEW CARDS ────────────────────────────────────────────────── */
.review-list { display: flex; flex-direction: column; gap: 20px; }
.review-card {
    border: 1px solid var(--border); border-radius: var(--r-md); padding: 20px;
    background: #fafafa; transition: box-shadow .2s;
}
.review-card:hover { box-shadow: var(--sh-card); background: var(--white); }
.review-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.review-author { display: flex; align-items: center; gap: 12px; }
.review-av {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff; font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.review-name { font-weight: 600; font-size: 14px; color: var(--ink); display: block; margin-bottom: 2px; }
.review-date { font-size: 12px; color: var(--muted); }
.review-stars { display: flex; gap: 3px; }
.review-stars i { color: var(--star); font-size: 14px; }
.review-stars i.empty { color: #d1d5db; }
.review-text { font-size: 14px; line-height: 1.75; color: #4b5563; }

.no-reviews { text-align: center; padding: 40px 20px; color: var(--muted); }
.no-reviews i { font-size: 40px; color: #d1d5db; margin-bottom: 12px; display: block; }
.no-reviews p { font-size: 14px; margin: 0; }

/* ── REVIEW FORM ─────────────────────────────────────────────────── */
.review-form-card {
    background: var(--white); border-radius: var(--r-lg); padding: 32px;
    box-shadow: var(--sh-card); border: 1px solid var(--border); margin-bottom: 28px;
}
.form-label { font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 8px; display: block; }

/* interactive star picker */
.star-picker { display: flex; gap: 8px; margin-bottom: 20px; }
.star-picker input[type="radio"] { display: none; }
.star-picker label {
    font-size: 32px;
    color: #d1d5db;
    cursor: pointer;
    transition: color .15s, transform .15s;
    line-height: 1;
}
.star-picker label:hover,
.star-picker label:hover ~ label { color: #d1d5db; }
.star-picker:hover label { color: var(--star); }
.star-picker label:hover ~ label { color: #d1d5db; }
.star-picker input[type="radio"]:checked ~ label { color: #d1d5db; }
.star-picker input[type="radio"]:checked + label,
.star-picker input[type="radio"]:checked + label ~ label { color: #d1d5db; }

/* CSS-only star trick — stars fill left-to-right */
.star-picker { flex-direction: row-reverse; justify-content: flex-end; }
.star-picker label:hover,
.star-picker label:hover ~ label { color: var(--star) !important; }
.star-picker input[type="radio"]:checked ~ label { color: var(--star) !important; }
.star-picker input[type="radio"]:checked + label { color: var(--star) !important; }

.review-textarea {
    width: 100%; min-height: 110px; padding: 14px 16px;
    border: 1.5px solid var(--border); border-radius: var(--r-md);
    font-size: 14px; font-family: 'Inter', sans-serif; color: var(--ink);
    resize: vertical; outline: none; transition: border-color .2s; line-height: 1.6;
    background: #fafafa;
}
.review-textarea:focus { border-color: var(--accent); background: var(--white); }
.review-textarea::placeholder { color: #9ca3af; }

.btn-review {
    padding: 13px 28px; background: var(--accent); color: #fff; border: none;
    border-radius: var(--r-md); font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: background .2s, transform .2s;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(124,58,237,.25);
}
.btn-review:hover { background: #6d28d9; transform: translateY(-2px); }

.review-notice {
    background: rgba(124,58,237,.06); border: 1px solid rgba(124,58,237,.15);
    border-radius: var(--r-sm); padding: 14px 16px; font-size: 13px; color: #5b21b6;
    display: flex; align-items: center; gap: 10px;
}
.review-notice i { flex-shrink: 0; }

/* ── MORE GIGS ───────────────────────────────────────────────────── */
.section-divider { max-width: 1160px; margin: 0 auto; padding: 0 24px; }
.section-divider hr { border: none; border-top: 1px solid var(--border); margin: 0; }

.more-gigs-section { max-width: 1160px; margin: 48px auto 80px; padding: 0 24px; }
.section-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 700; color: var(--ink); margin: 0 0 24px; }

.gigs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 22px; }
.gig-card {
    background: var(--white); border-radius: var(--r-md); overflow: hidden;
    border: 1px solid var(--border); box-shadow: var(--sh-card);
    transition: transform .25s, box-shadow .25s; text-decoration: none; color: inherit;
    display: flex; flex-direction: column; position: relative;
}
.gig-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--accent), #6366f1); opacity: 0; transition: opacity .25s;
}
.gig-card:hover { transform: translateY(-6px); box-shadow: var(--sh-lift); }
.gig-card:hover::before { opacity: 1; }
.gig-card-img { width: 100%; height: 158px; object-fit: cover; display: block; border-bottom: 1px solid var(--border); }
.gig-card-body { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
.gig-card-title { font-size: 14.5px; font-weight: 600; color: var(--ink); margin: 0 0 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.45; height: 42px; }
.gig-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border); }
.gig-card-cat { font-size: 10px; font-weight: 700; color: var(--accent); background: var(--accent-soft); padding: 4px 10px; border-radius: 20px; letter-spacing: .04em; text-transform: uppercase; }
.gig-card-price { font-weight: 700; font-size: 14px; color: var(--ink); }

/* ── RESPONSIVE ──────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .hero-inner { flex-direction: column; }
    .hero-right { min-width: 0; max-width: 100%; }
    .gig-title-hero { font-size: 26px; }
    .order-card { position: static; }
    .include-list { grid-template-columns: 1fr; }
    .rating-overview { flex-direction: column; gap: 24px; align-items: flex-start; }
}
@media (max-width: 600px) {
    .gig-hero { padding-top: 90px; }
    .price-display { font-size: 30px; }
    .gigs-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
    .rating-big-num .num { font-size: 48px; }
}
</style>

<?php
// Helper: render star icons given a numeric rating
function render_stars(float $rating, string $size_class = ''): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= round($rating) ? '' : ' empty';
        $html .= "<i class='fas fa-star{$cls}'></i>";
    }
    return $html;
}
?>

<!-- ═══════════════════════════ HERO ════════════════════════════ -->
<div class="gig-hero">
    <div class="hero-inner">

        <div class="hero-left">
            <div class="hero-breadcrumb">
                <a href="jobs.php"><i class="fas fa-th-large" style="font-size:11px"></i> Browse Gigs</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($gig['category']); ?></span>
            </div>

            <?php
            $img_path = (!empty($gig['image']) && $gig['image'] !== 'default.png')
                ? "uploads/" . htmlspecialchars($gig['image'])
                : "images/hero_illustration.png";
            ?>
            <div class="gig-img-wrap">
                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($gig['title']); ?>" class="gig-img">
            </div>

            <div class="cat-pill"><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($gig['category']); ?></div>
            <h1 class="gig-title-hero"><?php echo htmlspecialchars($gig['title']); ?></h1>

            <!-- Rating in hero -->
            <div class="hero-rating-row">
                <div class="hero-stars"><?php echo render_stars($avg_rating); ?></div>
                <span class="hero-avg"><?php echo $avg_rating > 0 ? number_format($avg_rating, 1) : '—'; ?></span>
                <span class="hero-rev-count"><?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?></span>
            </div>

            <div class="hero-seller">
                <div class="seller-av"><?php echo strtoupper(substr($gig['student_name'], 0, 1)); ?></div>
                <div>
                    <div class="seller-name">
                        <?php echo htmlspecialchars($gig['student_name']); ?>
                        <span class="vdot"><i class="fas fa-check"></i></span>
                    </div>
                    <div class="seller-sub">Verified Student Freelancer</div>
                </div>
            </div>
        </div>

        <div class="hero-right">
            <div class="order-card">
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo $msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="order-card-top">
                    <div class="price-eyebrow">Service Budget</div>
                    <div class="price-display"><sup>Rs.</sup><?php echo number_format($gig['price'], 0); ?></div>
                    <div class="price-note">Fixed price · No hidden fees</div>
                </div>

                <!-- Compact rating in order card -->
                <?php if ($review_count > 0): ?>
                <div class="card-rating-row">
                    <div class="card-stars"><?php echo render_stars($avg_rating); ?></div>
                    <span class="card-rating-text">
                        <strong><?php echo number_format($avg_rating, 1); ?></strong>
                        (<?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?>)
                    </span>
                </div>
                <?php endif; ?>

                <div class="delivery-strip">
                    <div class="d-badge"><div class="d-badge-icon">🚀</div><div class="d-badge-label">Delivery</div><div class="d-badge-val">On-Time</div></div>
                    <div class="d-badge"><div class="d-badge-icon">🔄</div><div class="d-badge-label">Revisions</div><div class="d-badge-val">Included</div></div>
                    <div class="d-badge"><div class="d-badge-icon">💬</div><div class="d-badge-label">Support</div><div class="d-badge-val">Direct</div></div>
                </div>

                <ul class="trust-list">
                    <li><span class="trust-icon green"><i class="fas fa-shield-alt"></i></span> Safe &amp; secure checkout</li>
                    <li><span class="trust-icon blue"><i class="fas fa-history"></i></span> On-time delivery guarantee</li>
                    <li><span class="trust-icon violet"><i class="fas fa-headset"></i></span> Dedicated support team</li>
                </ul>

                <form method="POST" action="">
                    <input type="hidden" name="gig_price" value="<?php echo $gig['price']; ?>">
                    <input type="hidden" name="seller_id" value="<?php echo $gig['student_id']; ?>">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] == $gig['student_id']): ?>
                            <button type="button" class="btn-order disabled-own" disabled><i class="fas fa-ban"></i> This is your own gig</button>
                        <?php else: ?>
                            <button type="submit" name="place_order" class="btn-order"><i class="fas fa-shopping-cart"></i> Order Service Now</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login to Place Order</a>
                    <?php endif; ?>
                </form>
                <div class="secure-note"><i class="fas fa-lock" style="font-size:10px"></i> Secured by UniLance Payment Protection</div>
            </div>
        </div>

    </div>
</div>

<!-- ═══════════════════════ CONTENT ════════════════════════════ -->
<div class="content-wrap">

    <!-- About This Gig -->
    <div class="white-card">
        <div class="sec-eyebrow">Service details</div>
        <h2 class="sec-heading">About This Gig</h2>
        <p class="gig-desc"><?php echo htmlspecialchars($gig['description']); ?></p>
        <?php if (!empty($gig['description'])): ?>
        <hr style="border:none;border-top:1px solid var(--border);margin:24px 0">
        <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0 0 12px;text-transform:uppercase;letter-spacing:.06em">What's included</p>
        <ul class="include-list">
            <li><i class="fas fa-check-circle"></i> Source files delivered</li>
            <li><i class="fas fa-check-circle"></i> High-quality output</li>
            <li><i class="fas fa-check-circle"></i> Direct communication</li>
            <li><i class="fas fa-check-circle"></i> Revision on request</li>
        </ul>
        <?php endif; ?>
    </div>

    <!-- ══ REVIEWS SECTION ══ -->
    <div class="white-card">
        <div class="sec-eyebrow">Client feedback</div>
        <h2 class="sec-heading">Reviews &amp; Ratings</h2>

        <!-- Rating overview bar chart -->
        <?php if ($review_count > 0): ?>
        <div class="rating-overview">
            <div class="rating-big-num">
                <span class="num"><?php echo number_format($avg_rating, 1); ?></span>
                <div class="stars-lg"><?php echo render_stars($avg_rating); ?></div>
                <span class="rev-label"><?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="rating-bars">
                <?php foreach ([5, 4, 3, 2, 1] as $star): 
                    $count  = $rating_counts[$star];
                    $pct    = $review_count > 0 ? round(($count / $review_count) * 100) : 0;
                ?>
                <div class="bar-row">
                    <span class="bar-label"><?php echo $star; ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                    <span class="bar-count"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Review cards -->
        <?php if ($review_count > 0): ?>
        <div class="review-list">
            <?php foreach ($reviews as $rev): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-author">
                        <div class="review-av"><?php echo strtoupper(substr($rev['reviewer_name'], 0, 1)); ?></div>
                        <div>
                            <span class="review-name"><?php echo htmlspecialchars($rev['reviewer_name']); ?></span>
                            <span class="review-date"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="review-stars"><?php echo render_stars($rev['rating']); ?></div>
                </div>
                <p class="review-text"><?php echo nl2br(htmlspecialchars($rev['review_text'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-reviews">
            <i class="far fa-comment-dots"></i>
            <p>No reviews yet. Be the first to share your experience!</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ LEAVE A REVIEW ══ -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $gig['student_id']): ?>
        <?php if ($user_reviewed): ?>
            <div class="review-notice">
                <i class="fas fa-check-circle"></i>
                You have already reviewed this gig. Thank you for your feedback!
            </div>
        <?php elseif ($user_ordered): ?>
            <div class="review-form-card">
                <div class="sec-eyebrow">Share your experience</div>
                <h2 class="sec-heading" style="margin-bottom:24px">Leave a Review</h2>

                <form method="POST" action="">
                    <!-- Star picker -->
                    <label class="form-label">Your Rating</label>
                    <div class="star-picker" id="starPicker">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>"
                               <?php echo (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">★</label>
                        <?php endfor; ?>
                    </div>

                    <!-- Text -->
                    <label class="form-label" for="review_text" style="margin-top:4px">Your Review</label>
                    <textarea name="review_text" id="review_text" class="review-textarea"
                              placeholder="Describe your experience with this service — quality, communication, delivery…"
                              maxlength="1000"><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
                    <div style="text-align:right;font-size:12px;color:var(--muted);margin-top:4px;margin-bottom:20px">
                        <span id="charCount">0</span> / 1000
                    </div>

                    <button type="submit" name="submit_review" class="btn-review">
                        <i class="fas fa-paper-plane"></i> Post Review
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-notice">
                <i class="fas fa-info-circle"></i>
                You need to place an order on this gig before you can leave a review.
            </div>
        <?php endif; ?>
    <?php elseif (!isset($_SESSION['user_id'])): ?>
        <div class="review-notice">
            <i class="fas fa-sign-in-alt"></i>
            <a href="login.php" style="color:var(--accent);font-weight:600;text-decoration:none">Log in</a>&nbsp;to leave a review.
        </div>
    <?php endif; ?>

</div><!-- /content-wrap -->

<!-- ═════════════════════ MORE GIGS ════════════════════════════ -->
<div class="section-divider" style="margin-top:48px"><hr></div>

<div class="more-gigs-section">
    <h2 class="section-title">More by <span style="color:var(--accent)"><?php echo htmlspecialchars($gig['student_name']); ?></span></h2>
    <div class="gigs-grid">
        <?php if ($other_gigs_result && $other_gigs_result->num_rows > 0): ?>
            <?php while ($ogig = $other_gigs_result->fetch_assoc()):
                $ogig_img = (!empty($ogig['image']) && $ogig['image'] !== 'default.png')
                    ? "uploads/" . htmlspecialchars($ogig['image'])
                    : "images/hero_illustration.png";
            ?>
            <a href="freelancer_gig.php?id=<?php echo $ogig['id']; ?>" class="gig-card">
                <img src="<?php echo $ogig_img; ?>" alt="<?php echo htmlspecialchars($ogig['title']); ?>" class="gig-card-img">
                <div class="gig-card-body">
                    <h4 class="gig-card-title"><?php echo htmlspecialchars($ogig['title']); ?></h4>
                    <div class="gig-card-footer">
                        <span class="gig-card-cat"><?php echo htmlspecialchars($ogig['category']); ?></span>
                        <span class="gig-card-price">Rs. <?php echo number_format($ogig['price'], 0); ?></span>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:var(--muted);font-size:15px;grid-column:1/-1">No other gigs from this freelancer yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Character counter for review textarea
const ta = document.getElementById('review_text');
const cc = document.getElementById('charCount');
if (ta && cc) {
    cc.textContent = ta.value.length;
    ta.addEventListener('input', () => cc.textContent = ta.value.length);
}
</script>

<?php include 'includes/footer.php'; ?>