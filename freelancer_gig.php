<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'includes/db.php';
include 'includes/header.php';

$msg = ""; 
$error_msg = "";

// 1. URL එකෙන් එන Gig ID එක ගැනීම
$gig_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($gig_id === 0) {
    echo "<div class='container' style='padding-top:140px; text-align:center;'><h2>Gig not found!</h2></div>";
    include 'includes/footer.php';
    exit();
}

// 2. Order Submit → DB Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $client_id  = (int)$_SESSION['user_id'];
    $amount     = (float)$_POST['gig_price'];
    $seller_id  = (int)$_POST['seller_id'];

    if ($client_id === $seller_id) {
        $error_msg = "You cannot place an order on your own gig!";
    } else {
        $s = $conn->prepare("INSERT INTO orders (client_id,student_id,gig_id,status) VALUES (?, ?, ?, 'pending')");
        if ($s) {
            $s->bind_param("iii", $client_id, $seller_id, $gig_id);
            $msg = $s->execute()
                ? "Order placed successfully! The freelancer will contact you soon."
                : "Failed to place order. Please try again.";
            $s->close();
        }
    }
}

// 3. Gig + Seller details
$query = "SELECT g.*, u.fullname as student_name 
          FROM gigs g 
          JOIN users u ON g.student_id = u.id 
          WHERE g.id = ? AND g.status = 'approve' LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $gig_id);
$stmt->execute();
$gig = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gig) {
    echo "<div class='container' style='padding-top:140px; text-align:center;'><h2>Sorry, this gig is no longer available or pending approval.</h2></div>";
    include 'includes/footer.php';
    exit();
}

// 4. Other gigs by same seller
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
/* ─── Design tokens ───────────────────────────────────────────── */
:root {
    --hero-start:   #0f0c29;
    --hero-mid:     #302b63;
    --hero-end:     #24243e;
    --accent:       #7c3aed;
    --accent-light: rgba(124,58,237,.12);
    --cta:          #10b981;
    --cta-hover:    #059669;
    --white:        #ffffff;
    --ink:          #1a1a2e;
    --muted:        #64748b;
    --border:       #e8eaf0;
    --card-bg:      #ffffff;
    --section-bg:   #f5f6fa;
    --radius-lg:    20px;
    --radius-md:    14px;
    --radius-sm:    8px;
    --shadow-card:  0 4px 24px rgba(15,12,41,.06);
    --shadow-lift:  0 12px 40px rgba(15,12,41,.10);
}

*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: var(--section-bg);
    color: var(--ink);
    margin: 0;
}

/* ─── HERO BAND ───────────────────────────────────────────────── */
.gig-hero {
    background: linear-gradient(135deg, var(--hero-start) 0%, var(--hero-mid) 55%, var(--hero-end) 100%);
    padding-top: 120px; /* header offset */
    padding-bottom: 60px;
    position: relative;
    overflow: hidden;
}

/* subtle noise texture */
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

/* Left column – image + meta */
.hero-left {
    flex: 1.7;
    min-width: 0;
}

/* Right column – order card */
.hero-right {
    flex: 1;
    min-width: 320px;
    max-width: 380px;
}

/* ─── GIG IMAGE ───────────────────────────────────────────────── */
.gig-img-wrap {
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    margin-bottom: 28px;
    position: relative;
}

.gig-img-wrap::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: var(--radius-lg);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
    pointer-events: none;
}

.gig-img {
    width: 100%;
    max-height: 420px;
    object-fit: cover;
    display: block;
}

/* ─── HERO META (title, category, seller) ─────────────────────── */
.hero-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    font-size: 13px;
    color: rgba(255,255,255,.5);
}
.hero-breadcrumb a { color: rgba(255,255,255,.5); text-decoration: none; }
.hero-breadcrumb a:hover { color: rgba(255,255,255,.8); }
.hero-breadcrumb span { color: rgba(255,255,255,.25); }

.gig-category-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--accent-light);
    border: 1px solid rgba(124,58,237,.3);
    color: #a78bfa;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-bottom: 14px;
}

.gig-title-hero {
    font-family: 'Syne', sans-serif;
    font-size: 34px;
    font-weight: 800;
    color: var(--white);
    line-height: 1.2;
    margin: 0 0 24px;
    letter-spacing: -.3px;
}

.hero-seller {
    display: flex;
    align-items: center;
    gap: 14px;
}

.seller-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 3px rgba(255,255,255,.12);
    flex-shrink: 0;
}

.seller-name {
    font-weight: 600;
    font-size: 15px;
    color: var(--white);
    display: flex;
    align-items: center;
    gap: 6px;
}

.seller-badge {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,.5);
    margin-top: 2px;
}

.verified-dot {
    width: 16px;
    height: 16px;
    background: #3b82f6;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.verified-dot i { font-size: 9px; color: #fff; }

/* ─── ORDER CARD ──────────────────────────────────────────────── */
.order-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: 0 24px 60px rgba(0,0,0,.22);
    position: sticky;
    top: 110px;
}

.order-card-top {
    padding-bottom: 20px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.price-eyebrow {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 4px;
}

.price-display {
    font-family: 'Syne', sans-serif;
    font-size: 38px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
    margin-bottom: 4px;
}

.price-display sup {
    font-size: 18px;
    font-weight: 700;
    vertical-align: top;
    margin-top: 8px;
    display: inline-block;
}

.price-note {
    font-size: 12px;
    color: var(--muted);
}

/* delivery badges */
.delivery-strip {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.d-badge {
    flex: 1;
    background: #f1f5f9;
    border-radius: var(--radius-sm);
    padding: 10px 8px;
    text-align: center;
}

.d-badge-icon {
    font-size: 16px;
    margin-bottom: 4px;
}

.d-badge-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.d-badge-val {
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    margin-top: 1px;
}

/* trust list */
.trust-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
}

.trust-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #374151;
    font-weight: 500;
    padding: 7px 0;
}

.trust-list li:not(:last-child) {
    border-bottom: 1px solid var(--border);
}

.trust-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.trust-icon.green  { background: rgba(16,185,129,.1);  color: var(--cta);    }
.trust-icon.blue   { background: rgba(59,130,246,.1);  color: #3b82f6;       }
.trust-icon.violet { background: var(--accent-light);  color: var(--accent); }

/* CTA button */
.btn-order {
    width: 100%;
    padding: 16px;
    background: var(--cta);
    color: #fff;
    border: none;
    border-radius: var(--radius-md);
    font-size: 16px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    transition: background .2s, transform .2s, box-shadow .2s;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(16,185,129,.3);
}

.btn-order:hover {
    background: var(--cta-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16,185,129,.4);
}

.btn-order.disabled-own {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.btn-login {
    width: 100%;
    padding: 16px;
    background: var(--accent);
    color: #fff;
    border-radius: var(--radius-md);
    font-size: 15px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    transition: background .2s, box-shadow .2s, transform .2s;
    box-shadow: 0 4px 14px rgba(124,58,237,.25);
}
.btn-login:hover {
    background: #6d28d9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(124,58,237,.35);
}

.secure-note {
    text-align: center;
    font-size: 12px;
    color: var(--muted);
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* ─── ALERTS ──────────────────────────────────────────────────── */
.alert {
    padding: 14px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}
.alert-success { background: rgba(16,185,129,.08); color: #065f46; border: 1px solid rgba(16,185,129,.2); }
.alert-error   { background: rgba(239,68,68,.07);  color: #991b1b; border: 1px solid rgba(239,68,68,.15); }
.alert i { flex-shrink: 0; margin-top: 1px; }

/* ─── CONTENT SECTION ─────────────────────────────────────────── */
.gig-content-wrap {
    max-width: 1160px;
    margin: 0 auto;
    padding: 48px 24px 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 32px;
    align-items: start;
}

.about-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow-card);
    border: 1px solid var(--border);
    margin-bottom: 32px;
}

.section-eyebrow {
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: .1em;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.section-heading {
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 18px;
}

.gig-desc {
    font-size: 15.5px;
    line-height: 1.85;
    color: #4b5563;
    white-space: pre-wrap;
    margin: 0;
}

/* what you get list */
.include-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.include-list li {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}

.include-list i {
    color: var(--cta);
    font-size: 13px;
    flex-shrink: 0;
}

/* content sidebar spacer */
.content-sidebar-gap {
    height: 1px;
}

/* ─── MORE GIGS SECTION ───────────────────────────────────────── */
.more-gigs-section {
    max-width: 1160px;
    margin: 48px auto 80px;
    padding: 0 24px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
}

.section-title {
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--ink);
    margin: 0;
}

.gigs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 22px;
}

.gig-card {
    background: var(--card-bg);
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-card);
    transition: transform .25s, box-shadow .25s;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    position: relative;
}

.gig-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), #6366f1);
    opacity: 0;
    transition: opacity .25s;
}

.gig-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lift);
}

.gig-card:hover::before { opacity: 1; }

.gig-card-img {
    width: 100%;
    height: 158px;
    object-fit: cover;
    display: block;
    border-bottom: 1px solid var(--border);
}

.gig-card-body {
    padding: 16px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.gig-card-title {
    font-size: 14.5px;
    font-weight: 600;
    color: var(--ink);
    margin: 0 0 16px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
    height: 42px;
}

.gig-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.gig-card-cat {
    font-size: 10px;
    font-weight: 700;
    color: var(--accent);
    background: var(--accent-light);
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.gig-card-price {
    font-weight: 700;
    font-size: 14px;
    color: var(--ink);
}

.empty-gigs {
    color: var(--muted);
    font-size: 15px;
    grid-column: 1 / -1;
    padding: 24px 0;
}

/* ─── DIVIDER ─────────────────────────────────────────────────── */
.section-divider {
    max-width: 1160px;
    margin: 0 auto;
    padding: 0 24px;
}
.section-divider hr {
    border: none;
    border-top: 1px solid var(--border);
    margin: 0;
}

/* ─── RESPONSIVE ──────────────────────────────────────────────── */
@media (max-width: 900px) {
    .hero-inner,
    .content-grid {
        flex-direction: column;
        grid-template-columns: 1fr;
    }
    .hero-right { min-width: 0; max-width: 100%; }
    .gig-title-hero { font-size: 26px; }
    .order-card { position: static; }
    .include-list { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .gig-hero { padding-top: 90px; }
    .price-display { font-size: 30px; }
    .gigs-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
}
</style>

<!-- ══════════════════════════════════════════════════════════════
     HERO BAND
══════════════════════════════════════════════════════════════════ -->
<div class="gig-hero">
    <div class="hero-inner">
        <!-- Left: image + title + seller -->
        <div class="hero-left">
            <!-- Breadcrumb -->
            <div class="hero-breadcrumb">
                <a href="jobs.php"><i class="fas fa-th-large" style="font-size:11px;"></i> Browse Gigs</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($gig['category']); ?></span>
            </div>

            <!-- Image -->
            <?php
            $img_path = (!empty($gig['image']) && $gig['image'] !== 'default.png')
                ? "uploads/" . htmlspecialchars($gig['image'])
                : "images/hero_illustration.png";
            ?>
            <div class="gig-img-wrap">
                <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($gig['title']); ?>" class="gig-img">
            </div>

            <!-- Category pill -->
            <div class="gig-category-pill">
                <i class="fas fa-layer-group"></i>
                <?php echo htmlspecialchars($gig['category']); ?>
            </div>

            <!-- Title -->
            <h1 class="gig-title-hero"><?php echo htmlspecialchars($gig['title']); ?></h1>

            <!-- Seller -->
            <div class="hero-seller">
                <div class="seller-avatar"><?php echo strtoupper(substr($gig['student_name'], 0, 1)); ?></div>
                <div>
                    <div class="seller-name">
                        <?php echo htmlspecialchars($gig['student_name']); ?>
                        <span class="verified-dot"><i class="fas fa-check"></i></span>
                    </div>
                    <div class="seller-badge">Verified Student Freelancer</div>
                </div>
            </div>
        </div>

        <!-- Right: order card -->
        <div class="hero-right">
            <div class="order-card">
                <!-- Alerts -->
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="order-card-top">
                    <div class="price-eyebrow">Service Budget</div>
                    <div class="price-display">
                        <sup>Rs.</sup><?php echo number_format($gig['price'], 0); ?>
                    </div>
                    <div class="price-note">Fixed price · No hidden fees</div>
                </div>

                <!-- Delivery badges -->
                <div class="delivery-strip">
                    <div class="d-badge">
                        <div class="d-badge-icon">🚀</div>
                        <div class="d-badge-label">Delivery</div>
                        <div class="d-badge-val">On-Time</div>
                    </div>
                    <div class="d-badge">
                        <div class="d-badge-icon">🔄</div>
                        <div class="d-badge-label">Revisions</div>
                        <div class="d-badge-val">Included</div>
                    </div>
                    <div class="d-badge">
                        <div class="d-badge-icon">💬</div>
                        <div class="d-badge-label">Support</div>
                        <div class="d-badge-val">Direct</div>
                    </div>
                </div>

                <!-- Trust list -->
                <ul class="trust-list">
                    <li>
                        <span class="trust-icon green"><i class="fas fa-shield-alt"></i></span>
                        Safe &amp; secure checkout
                    </li>
                    <li>
                        <span class="trust-icon blue"><i class="fas fa-history"></i></span>
                        On-time delivery guarantee
                    </li>
                    <li>
                        <span class="trust-icon violet"><i class="fas fa-headset"></i></span>
                        Dedicated support team
                    </li>
                </ul>

                <!-- CTA -->
                <form method="POST" action="">
                    <input type="hidden" name="gig_price"  value="<?php echo $gig['price']; ?>">
                    <input type="hidden" name="seller_id"  value="<?php echo $gig['student_id']; ?>">

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] == $gig['student_id']): ?>
                            <button type="button" class="btn-order disabled-own" disabled>
                                <i class="fas fa-ban"></i> This is your own gig
                            </button>
                        <?php else: ?>
                            <button type="submit" name="place_order" class="btn-order">
                                <i class="fas fa-shopping-cart"></i> Order Service Now
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login to Place Order
                        </a>
                    <?php endif; ?>
                </form>

                <div class="secure-note">
                    <i class="fas fa-lock" style="font-size:10px;"></i>
                    Secured by UniLance Payment Protection
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     CONTENT SECTION
══════════════════════════════════════════════════════════════════ -->
<div class="gig-content-wrap">
    <!-- About card -->
    <div class="about-card">
        <div class="section-eyebrow">Service details</div>
        <h2 class="section-heading">About This Gig</h2>
        <p class="gig-desc"><?php echo htmlspecialchars($gig['description']); ?></p>

        <?php if (!empty($gig['description'])): ?>
        <hr style="border:none; border-top:1px solid var(--border); margin: 24px 0;">
        <p style="font-size:13px; font-weight:600; color:var(--ink); margin:0 0 12px; text-transform:uppercase; letter-spacing:.06em;">What's included</p>
        <ul class="include-list">
            <li><i class="fas fa-check-circle"></i> Source files delivered</li>
            <li><i class="fas fa-check-circle"></i> High-quality output</li>
            <li><i class="fas fa-check-circle"></i> Direct communication</li>
            <li><i class="fas fa-check-circle"></i> Revision on request</li>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MORE GIGS
══════════════════════════════════════════════════════════════════ -->
<div class="section-divider"><hr></div>

<div class="more-gigs-section">
    <div class="section-header">
        <h2 class="section-title">
            More by <span style="color:var(--accent);"><?php echo htmlspecialchars($gig['student_name']); ?></span>
        </h2>
    </div>

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
            <p class="empty-gigs">No other gigs available from this freelancer yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>