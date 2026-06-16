<?php
include 'includes/db.php';
include 'includes/header.php';

// Fetch jobs from database with filtering
$query = "SELECT j.*, u.fullname as client_name FROM gigs j JOIN users u ON j.student_id = u.id WHERE j.status = 'approve'";
$params = [];

if (!empty($_GET['search'])) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
    $query .= " AND j.category = ?";
    $params[] = $_GET['category'];
}

if (!empty($_GET['min_price'])) {
    $query .= " AND j.price >= ?";
    $params[] = (float)$_GET['min_price'];
}

if (!empty($_GET['max_price'])) {
    $query .= " AND j.price <= ?";
    $params[] = (float)$_GET['max_price'];
}

$sort = $_GET['sort'] ?? 'newest';
if ($sort === 'price_asc') {
    $query .= " ORDER BY j.price ASC";
} elseif ($sort === 'price_desc') {
    $query .= " ORDER BY j.price DESC";
} else {
    $query .= " ORDER BY j.created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<section style="padding: 150px 5% 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Available Gigs</h1>
            <p style="color: var(--text-muted);">Browse the latest freelance opportunities for students.</p>
        </div>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'student'): ?>
            <a href="post-gig.php" class="btn btn-primary">Post a New Gig</a>
        <?php endif; ?>
    </div>

    <!-- Beautiful Glassmorphic Search & Filter Bar -->
    <div class="card" style="margin-bottom: 2.5rem; padding: 1.5rem; border-radius: 16px; background: var(--glass-bg); backdrop-filter: blur(10px);">
        <form method="GET" action="jobs.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; align-items: end;">
            <div class="input-group" style="margin-bottom: 0;">
                <label for="search" style="font-weight: 500; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-search" style="color: var(--primary);"></i> Keyword
                </label>
                <input type="text" id="search" name="search" placeholder="e.g. Logo Design, Website..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="background: rgba(9, 13, 22, 0.6); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 8px; width: 100%;">
            </div>
            
            <div class="input-group" style="margin-bottom: 0;">
                <label for="category" style="font-weight: 500; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-filter" style="color: var(--primary);"></i> Category
                </label>
                <select id="category" name="category" style="background: rgba(9, 13, 22, 0.6); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 8px; width: 100%;">
                    <option value="all">All Categories</option>
                    <option value="Development" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Development') ? 'selected' : ''; ?>>Development</option>
                    <option value="Design" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Design') ? 'selected' : ''; ?>>Design</option>
                    <option value="Writing" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Writing') ? 'selected' : ''; ?>>Writing</option>
                    <option value="Tutoring" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Tutoring') ? 'selected' : ''; ?>>Tutoring</option>
                    <option value="Other" <?php echo (isset($_GET['category']) && $_GET['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="input-group" style="margin-bottom: 0;">
                <label for="min_price" style="font-weight: 500; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-dollar-sign" style="color: var(--primary);"></i> Min Price
                </label>
                <input type="number" id="min_price" name="min_price" placeholder="Min" min="0" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>" style="background: rgba(9, 13, 22, 0.6); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 8px; width: 100%;">
            </div>

            <div class="input-group" style="margin-bottom: 0;">
                <label for="max_price" style="font-weight: 500; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-dollar-sign" style="color: var(--primary);"></i> Max Price
                </label>
                <input type="number" id="max_price" name="max_price" placeholder="Max" min="0" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>" style="background: rgba(9, 13, 22, 0.6); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 8px; width: 100%;">
            </div>

            <div class="input-group" style="margin-bottom: 0;">
                <label for="sort" style="font-weight: 500; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sort-amount-down" style="color: var(--primary);"></i> Sort By
                </label>
                <select id="sort" name="sort" style="background: rgba(9, 13, 22, 0.6); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 8px; width: 100%;">
                    <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 0.75rem; height: 44px; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; height: 44px; display: inline-flex; align-items: center;">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php 
                $has_active_filters = !empty($_GET['search']) || 
                                      (!empty($_GET['category']) && $_GET['category'] !== 'all') || 
                                      !empty($_GET['min_price']) || 
                                      !empty($_GET['max_price']) || 
                                      (isset($_GET['sort']) && $_GET['sort'] !== 'newest');
                if ($has_active_filters): 
                ?>
                    <a href="jobs.php" class="btn btn-outline" style="justify-content: center; height: 44px; padding: 0 1rem; display: inline-flex; align-items: center;" title="Reset Filters">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="job-grid">
        <?php if(count($jobs) > 0): ?>
            <?php foreach($jobs as $job): ?>
                <div class="card job-card fade-in">
                    <span class="category"><?php echo htmlspecialchars($job['category']); ?></span>
                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="budget">$<?php echo number_format($job['price'], 2); ?></span>
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">Apply Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background: var(--glass-bg); border-radius: 24px;">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h2>No gigs found</h2>
                <p style="color: var(--text-muted);">Check back later for new opportunities!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
