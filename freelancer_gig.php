<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'includes/db.php';
include 'includes/header.php';

$msg = "";
$error_msg = "";

$gig_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($gig_id === 0) {
    echo "<div style='padding-top:140px;text-align:center'><h2>Gig not found!</h2></div>";
    include 'includes/footer.php'; exit();
}

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
        $order_check = $conn->prepare("SELECT orderID FROM orders WHERE client_id = ? AND gig_id = ? LIMIT 1");
        $order_check->bind_param("ii", $reviewer_id, $gig_id);
        $order_check->execute();
        $order_check->store_result();
        $has_ordered = $order_check->num_rows > 0;
        $order_check->close();

        if (!$has_ordered) {
            $error_msg = "You can only review a gig after placing an order.";
        } else {
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

$user_reviewed = false;
$user_ordered  = false;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $oc = $conn->prepare("SELECT orderId FROM orders WHERE client_id = ? AND gig_id = ? LIMIT 1");
    $oc->bind_param("ii", $uid, $gig_id);
    $oc->execute(); $oc->store_result();
    $user_ordered = $oc->num_rows > 0;
    $oc->close();
    $rc = $conn->prepare("SELECT id FROM gig_reviews WHERE gig_id = ? AND user_id = ? LIMIT 1");
    $rc->bind_param("ii", $gig_id, $uid);
    $rc->execute(); $rc->store_result();
    $user_reviewed = $rc->num_rows > 0;
    $rc->close();
}

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
<link rel="stylesheet" href="css/freelancer_gig.css">



<?php
function render_stars(float $rating, string $size_class = ''): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= round($rating) ? '' : ' empty';
        $html .= "<i class='fas fa-star{$cls}'></i>";
    }
    return $html;
}
?>

<div class="gig-hero">
    <div class="hero-inner">

        <div class="hero-left">
            <div class="hero-breadcrumb">
                <a href="jobs.php"><i class="fas fa-th-large" style="font-size:11px"></i> Browse Gigs</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($gig['category']); ?></span>
            </div>

            <?php
            // Parse multiple images
            $raw_imgs = (!empty($gig['image']) && $gig['image'] !== 'default.png')
                ? array_values(array_filter(array_map('trim', explode(',', $gig['image']))))
                : [];
            $gig_images = !empty($raw_imgs)
                ? $raw_imgs
                : ['images/hero_illustration.png'];
            $is_default_img = empty($raw_imgs);
            ?>
            <div class="gig-img-wrap">
                <!-- Main viewer -->
                <div class="gig-gallery">
                    <?php foreach ($gig_images as $idx => $gimg): ?>
                        <div class="gig-slide<?php echo $idx === 0 ? ' active' : ''; ?>" data-idx="<?php echo $idx; ?>">
                            <img src="<?php echo $is_default_img ? $gimg : 'uploads/' . htmlspecialchars($gimg); ?>"
                                 alt="<?php echo htmlspecialchars($gig['title']); ?> – image <?php echo $idx + 1; ?>"
                                 class="gig-img">
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($gig_images) > 1): ?>
                        <!-- Arrow buttons -->
                        <button class="gig-arrow gig-arrow-prev" id="gigPrev" aria-label="Previous image">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="gig-arrow gig-arrow-next" id="gigNext" aria-label="Next image">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Dot indicators -->
                        <div class="gig-dots">
                            <?php foreach ($gig_images as $idx => $gimg): ?>
                                <span class="gig-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-goto="<?php echo $idx; ?>"></span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Image counter badge -->
                        <div class="gig-counter">
                            <span id="gigCur">1</span> / <?php echo count($gig_images); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($gig_images) > 1): ?>
                <!-- Thumbnail strip -->
                <div class="gig-thumbs">
                    <?php foreach ($gig_images as $idx => $gimg): ?>
                        <div class="gig-thumb<?php echo $idx === 0 ? ' active' : ''; ?>" data-goto="<?php echo $idx; ?>">
                            <img src="<?php echo $is_default_img ? $gimg : 'uploads/' . htmlspecialchars($gimg); ?>"
                                 alt="Thumbnail <?php echo $idx + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="cat-pill"><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($gig['category']); ?></div>
            <h1 class="gig-title-hero"><?php echo htmlspecialchars($gig['title']); ?></h1>

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

<div class="content-wrap">

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

    <div class="white-card">
        <div class="sec-eyebrow">Client feedback</div>
        <h2 class="sec-heading">Reviews &amp; Ratings</h2>

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
                    <label class="form-label">Your Rating</label>
                    <div class="star-picker" id="starPicker">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>"
                               <?php echo (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">★</label>
                        <?php endfor; ?>
                    </div>

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

</div>

<div class="section-divider" style="margin-top:48px"><hr></div>

<div class="more-gigs-section">
    <h2 class="section-title">More by <span style="color:var(--accent)"><?php echo htmlspecialchars($gig['student_name']); ?></span></h2>
    <div class="gigs-grid">
        <?php if ($other_gigs_result && $other_gigs_result->num_rows > 0): ?>
            <?php while ($ogig = $other_gigs_result->fetch_assoc()):
                // Take only the first image from the comma-separated list
                $ogig_imgs = (!empty($ogig['image']) && $ogig['image'] !== 'default.png')
                    ? array_values(array_filter(array_map('trim', explode(',', $ogig['image']))))
                    : [];
                $ogig_img = !empty($ogig_imgs)
                    ? 'uploads/' . htmlspecialchars($ogig_imgs[0])
                    : 'images/hero_illustration.png';
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
const ta = document.getElementById('review_text');
const cc = document.getElementById('charCount');
if (ta && cc) {
    cc.textContent = ta.value.length;
    ta.addEventListener('input', () => cc.textContent = ta.value.length);
}

// ── Gig image gallery ─────────────────────────────────────────
(function () {
    const slides = Array.from(document.querySelectorAll('.gig-slide'));
    const dots   = Array.from(document.querySelectorAll('.gig-dot'));
    const thumbs = Array.from(document.querySelectorAll('.gig-thumb'));
    const curEl  = document.getElementById('gigCur');
    const prevBtn = document.getElementById('gigPrev');
    const nextBtn = document.getElementById('gigNext');

    if (slides.length < 2) return; // single image, nothing to do

    let current  = 0;
    let autoPlay = null;

    function goTo(idx) {
        slides[current].classList.remove('active');
        dots[current]   && dots[current].classList.remove('active');
        thumbs[current] && thumbs[current].classList.remove('active');

        current = (idx + slides.length) % slides.length;

        slides[current].classList.add('active');
        dots[current]   && dots[current].classList.add('active');
        thumbs[current] && thumbs[current].classList.add('active');
        if (curEl) curEl.textContent = current + 1;
    }

    function startAuto() {
        stopAuto();
        autoPlay = setInterval(() => goTo(current + 1), 3500);
    }

    function stopAuto() {
        clearInterval(autoPlay);
    }

    if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); startAuto(); });

    // Dot clicks
    dots.forEach(dot => {
        dot.addEventListener('click', () => { goTo(parseInt(dot.dataset.goto)); startAuto(); });
    });

    // Thumbnail clicks
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => { goTo(parseInt(thumb.dataset.goto)); startAuto(); });
    });

    // Pause on hover over the gallery
    const gallery = document.querySelector('.gig-gallery');
    if (gallery) {
        gallery.addEventListener('mouseenter', stopAuto);
        gallery.addEventListener('mouseleave', startAuto);
    }

    // Swipe support on touch devices
    let touchStartX = 0;
    if (gallery) {
        gallery.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
        gallery.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) {
                goTo(diff > 0 ? current + 1 : current - 1);
                startAuto();
            }
        }, { passive: true });
    }

    startAuto();
}());
</script>

<?php include 'includes/footer.php'; ?>