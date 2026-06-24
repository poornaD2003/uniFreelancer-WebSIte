<?php
include 'includes/db.php';
include 'includes/header.php';

// 1. Get Search, Filter, and Sort values from URL
$search_query    = isset($_GET['search'])   ? $conn->real_escape_string($_GET['search'])   : '';
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$sort_option     = isset($_GET['sort'])     ? $_GET['sort']     : 'recent';
$min_price       = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price       = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';

// NEW: Grab selected categories from the sidebar checkboxes
$selected_categories = isset($_GET['cat_cb']) ? (array)$_GET['cat_cb'] : [];

// If a top-bar navigation category is clicked, sync it with the checkboxes array
if (!empty($category_filter) && empty($selected_categories)) {
    $selected_categories[] = $category_filter;
}

// 2. Build dynamic SQL
$sql_conditions = "j.status = 'approve'";

if (!empty($search_query)) {
    $sql_conditions .= " AND (j.title LIKE '%$search_query%' OR j.description LIKE '%$search_query%')";
}

// FIXED: Handle multiple checkbox selections or single link categories seamlessly
if (!empty($selected_categories)) {
    $escaped_cats = array_map(function($cat) use ($conn) {
        return "'" . $conn->real_escape_string($cat) . "'";
    }, $selected_categories);
    
    $sql_conditions .= " AND j.category IN (" . implode(',', $escaped_cats) . ")";
    
    // Set active header title preview based on first chosen category if single category isn't set
    if (empty($category_filter)) {
        $category_filter = $selected_categories[0];
    }
}

if ($min_price !== '') {
    $sql_conditions .= " AND j.price >= $min_price";
}
if ($max_price !== '') {
    $sql_conditions .= " AND j.price <= $max_price";
}

$order_by = "j.created_at DESC";
if ($sort_option === 'price_asc')  $order_by = "j.price ASC";
if ($sort_option === 'price_desc') $order_by = "j.price DESC";

$query    = "SELECT j.*, u.fullname as student_name FROM gigs j JOIN users u ON j.student_id = u.id WHERE $sql_conditions ORDER BY $order_by";
$result   = $conn->query($query);
$jobCount = $result ? $result->num_rows : 0;

// Category display name
$category_labels = [
    ''            => 'All Services',
    'design'      => 'Design & Creative',
    'development' => 'Programming & Tech',
    'writing'     => 'Writing & Translation',
];
$hero_title = $category_labels[$category_filter] ?? 'Browse Gigs';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/jobs.css">



<?php
// Category nav links
$categories = [
    ''            => 'All Categories',
    'design'      => 'Design & Creative',
    'development' => 'Programming & Tech',
    'writing'     => 'Writing & Translation',
    'video'       => 'Video & Animation',
    'music'       => 'Music & Audio',
    'business'    => 'Business',
];
?>

<!-- ── CATEGORY HERO BANNER ──────────────────────────────── -->
<div class="cat-hero">
    <div class="hero-blob-yellow"></div>
    <div class="hero-blob-coral"></div>
    <div class="hero-text">
        <div class="hero-breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="jobs.php">Services</a>
            <?php if (!empty($category_filter)): ?>
                <span>/</span>
                <span><?php echo $category_labels[$category_filter] ?? $category_filter; ?></span>
            <?php endif; ?>
        </div>
        <h1 class="hero-title"><?php echo $hero_title; ?></h1>
        <p class="hero-sub">Find talented student freelancers for every project.</p>
        <a href="student_freelancer_site.php#how" class="hero-play-btn">
            <span class="play-circle"><i class="fas fa-play"></i></span>
            How UniLance Works
        </a>
    </div>
</div>

<!-- ── CATEGORY NAV STRIP ────────────────────────────────── -->
<div class="cat-nav-strip">
    <div class="cat-nav-inner">
        <?php foreach ($categories as $val => $label): ?>
            <a href="jobs.php?category=<?php echo urlencode($val); ?>&search=<?php echo urlencode($search_query); ?>"
               class="cat-nav-link <?php echo ($category_filter === $val) ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
        <a href="jobs.php?sort=recent&category=<?php echo urlencode($category_filter); ?>" class="cat-nav-link <?php echo ($sort_option == 'recent' && empty($search_query)) ? 'active' : ''; ?>">Trending</a>
    </div>
</div>

<!-- ── SEARCH BAR ────────────────────────────────────────── -->
<div class="search-form-wrap">
    <form method="GET" action="jobs.php">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <div class="search-inner">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search for any service…"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <span class="search-divider-v"></span>
                <select name="category_inline" class="search-cat-select" onchange="window.location='jobs.php?category='+this.value">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($category_filter === $val) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
        </div>
    </form>
</div>

<!-- ── MAIN PAGE BODY ─────────────────────────────────────── -->
<div class="page-body">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
        <form method="GET" action="jobs.php" id="filter-form">
            <input type="hidden" name="search"   value="<?php echo htmlspecialchars($search_query); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
            <input type="hidden" name="sort"     value="<?php echo htmlspecialchars($sort_option); ?>">

            

            <!-- Budget -->
            <div class="filter-block">
                <div class="filter-header">
                    <span class="filter-title">Budget</span>
                    <i class="fas fa-chevron-up filter-chevron open"></i>
                </div>
                <div class="filter-body">
                    <div class="budget-inputs">
                        <div class="budget-input-wrap">
                            <label>Min Price</label>
                            <input type="number" name="min_price" id="min_price_input"
                                   value="<?php echo $min_price !== '' ? $min_price : '0'; ?>"
                                   placeholder="0" min="0">
                        </div>
                        <div class="budget-input-wrap">
                            <label>Max Price</label>
                            <input type="number" name="max_price" id="max_price_input"
                                   value="<?php echo $max_price !== '' ? $max_price : '10000'; ?>"
                                   placeholder="10000" min="0">
                        </div>
                    </div>
                    <div class="range-slider-wrap">
                        <input type="range" class="range-slider" id="price_range"
                               min="0" max="10000" step="500"
                               value="<?php echo $max_price !== '' ? $max_price : '10000'; ?>">
                        <div class="range-labels">
                            <span>Rs. <?php echo number_format($min_price !== '' ? $min_price : 0); ?></span>
                            <span>Rs. <?php echo number_format($max_price !== '' ? $max_price : 10000); ?></span>
                        </div>
                    </div>
                    <button type="submit" style="width:100%;padding:8px;background:var(--green-accent);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;margin-top:6px;">
                        Apply Budget
                    </button>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="filter-block">
                <div class="filter-header">
                    <span class="filter-title">Category</span>
                    <i class="fas fa-chevron-up filter-chevron open"></i>
                </div>
                <div class="filter-body">
                    <?php
                    $cat_opts = [
                        'design'      => ['label' => 'Design & Creative'],
                        'development' => ['label' => 'Programming & Tech'],
                        'writing'     => ['label' => 'Writing & Translation'],
                        'video'       => ['label' => 'Video & Animation'],
                        'music'       => ['label' => 'Music & Audio'],
                        'business'    => ['label' => 'Business'],
                    ];
                    foreach ($cat_opts as $val => $opt):
                    ?>
                    <div class="filter-row">
                        <label>
                            <input type="checkbox" name="cat_cb[]" value="<?php echo $val; ?>"
                            <?php echo in_array($val, $selected_categories) ? 'checked' : ''; ?>
                            onchange="document.getElementById('filter-form').submit()">
                            <?php echo $opt['label']; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Results bar -->
        <div class="results-bar">
            <p class="results-count">
                <strong><?php echo number_format($jobCount); ?></strong> service<?php echo $jobCount !== 1 ? 's' : ''; ?> available
            </p>
            <form method="GET" action="jobs.php" style="display:inline;">
                <input type="hidden" name="search"   value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                <div class="sort-wrap">
                    <span>Sort by</span>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="recent"     <?php echo ($sort_option == 'recent')     ? 'selected' : ''; ?>>Best Selling</option>
                        <option value="price_asc"  <?php echo ($sort_option == 'price_asc')  ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sort_option == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- GIG CARDS GRID -->
        <div class="gig-grid">
            <?php if ($result && $jobCount > 0): ?>
                <?php while ($job = $result->fetch_assoc()):
                    // Take only the first image from the comma-separated list
                    $raw_images = (!empty($job['image']) && $job['image'] !== 'default.png')
                        ? array_values(array_filter(array_map('trim', explode(',', $job['image']))))
                        : [];
                    $img_path = !empty($raw_images)
                        ? 'uploads/' . htmlspecialchars($raw_images[0])
                        : 'images/hero_illustration.png';
                ?>
                <div class="gig-card">
                    <!-- Cover image -->
                    <div class="card-img-wrap">
                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($job['title']); ?>" class="card-cover">
                        <button class="heart-btn" aria-label="Save to favourites">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="card-body">
                        <div class="card-category"><?php echo htmlspecialchars($job['category']); ?></div>
                        <a href="freelancer_gig.php?id=<?php echo $job['id']; ?>" style="text-decoration:none;">
                            <h3 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        </a>

                        <!-- Rating (static placeholder — replace with DB data when available) -->
                        <div class="card-rating">
                            <span class="stars"><i class="fas fa-star"></i></span>
                            <span class="rating-num">4.82</span>
                            <span class="rating-count">94 reviews</span>
                        </div>

                        <!-- Footer: seller + price -->
                        <div class="card-footer">
                            <div class="seller-info">
                                <div class="seller-av"><?php echo strtoupper(substr($job['student_name'], 0, 1)); ?></div>
                                <span class="seller-name">
                                    <?php echo htmlspecialchars($job['student_name']); ?>
                                    <i class="fas fa-check-circle verified"></i>
                                </span>
                            </div>
                            <div class="card-price">
                                <span class="price-label">Starting at</span>
                                <span class="price-value">Rs. <?php echo number_format($job['price'], 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h2>No gigs found</h2>
                    <p>Try adjusting your search or filters to find what you're looking for.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Range slider syncs with max_price input
const rangeSlider   = document.getElementById('price_range');
const maxPriceInput = document.getElementById('max_price_input');
if (rangeSlider && maxPriceInput) {
    rangeSlider.addEventListener('input', () => {
        maxPriceInput.value = rangeSlider.value;
    });
    maxPriceInput.addEventListener('input', () => {
        rangeSlider.value = maxPriceInput.value;
    });
}
</script>

<?php include 'includes/footer.php'; ?>