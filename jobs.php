<?php
include 'includes/db.php';
include 'includes/header.php';

// 1. Get Search, Filter, and Sort values from URL parameters
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'recent';

// 2. Build the dynamic SQL query
$sql_conditions = "j.status = 'approve'";

// Search Filter
if (!empty($search_query)) {
    $sql_conditions .= " AND (j.title LIKE '%$search_query%' OR j.description LIKE '%$search_query%')";
}

// Category Filter
if (!empty($category_filter)) {
    $sql_conditions .= " AND j.category = '$category_filter'";
}

// Sorting Logic
$order_by = "j.created_at DESC"; // Default: Recent
if ($sort_option === 'price_asc') {
    $order_by = "j.price ASC"; // Price: Low to High
} elseif ($sort_option === 'price_desc') {
    $order_by = "j.price DESC"; // Price: High to Low
}

// Final Query Execution
$query = "SELECT j.*, u.fullname as student_name FROM gigs j JOIN users u ON j.student_id = u.id WHERE $sql_conditions ORDER BY $order_by";
$result = $conn->query($query);
$jobCount = $result ? $result->num_rows : 0;
?>

<link rel="stylesheet" href="css/style.css">

<form method="GET" action="jobs.php">
    <section class="section search-section" style="padding-top: 130px;">
        <div class="search-bar shadow-bar">
            <i class="fas fa-search search-icon-left"></i>
            <input type="text" name="search" placeholder="Start your search" value="<?php echo htmlspecialchars($search_query); ?>">
            <div class="search-divider"></div>
            <select name="category" class="search-cat">
                <option value="">All Categories</option>
                <option value="design" <?php echo ($category_filter == 'design') ? 'selected' : ''; ?>>Design</option>
                <option value="development" <?php echo ($category_filter == 'development') ? 'selected' : ''; ?>>Development</option>
                <option value="writing" <?php echo ($category_filter == 'writing') ? 'selected' : ''; ?>>Writing</option>
            </select>
            <button type="submit" class="btn btn-primary">Search now</button>
        </div>

        <div class="results-header">
            <h2 class="results-count"><?php echo $jobCount; ?> search result(s) found</h2>
            <div class="sort-box">
                <span class="sort-label">Sort by:</span>
                <select name="sort" class="search-cat sort-select" onchange="this.form.submit()">
                    <option value="recent" <?php echo ($sort_option == 'recent') ? 'selected' : ''; ?>>Recent listings</option>
                    <option value="price_asc" <?php echo ($sort_option == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo ($sort_option == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
        </div>
    </section>
</form>

<section class="section" style="padding-top: 0;">
    <div class="job-grid new-grid">
        <?php if($result && $jobCount > 0): ?>
            <?php while($job = $result->fetch_assoc()): ?>
                <div class="card image-card fade-in">
                    <div class="card-img-wrapper">
                        <?php 
                        // 3. Display actual database image. 
                        // Fallback to placeholder if image column is empty.
                        // NOTE: Ensure 'image' matches your actual column name in the gigs table and 'uploads/' matches your folder path.
                        $image_path = !empty($job['image']) ? "uploads/" . htmlspecialchars($job['image']) : "images/hero_illustration.png"; 
                        ?>
                        <img src="<?php echo $image_path; ?>" alt="Job Cover" class="card-cover">
                        <button class="heart-btn"><i class="far fa-heart"></i></button>
                    </div>
                    
                    <div class="card-content">
                        <div class="card-author">
                            <div class="author-av">
                                <?php echo strtoupper(substr($job['student_name'], 0, 1)); ?>
                            </div>
                            <span class="author-name">
                                <?php echo htmlspecialchars($job['student_name']); ?> 
                                <i class="fas fa-check-circle verified-icon"></i>
                            </span>
                        </div>

                        <h3 class="card-job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        
                        <div class="card-footer">
                            <div class="footer-left">
                                <span class="category-tag"><?php echo htmlspecialchars($job['category']); ?></span>
                                <span class="budget">Rs.<?php echo number_format($job['price'], 2); ?></span>
                            </div>
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline btn-sm">View</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search empty-icon"></i>
                <h2>No gigs found</h2>
                <p class="text-muted">Try adjusting your search or filters to find what you're looking for!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>