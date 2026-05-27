<?php
include 'includes/db.php';
include 'includes/header.php';

// Fetch jobs from database
$stmt = $pdo->query("SELECT j.*, u.fullname as client_name FROM gigs j JOIN users u ON j.client_id = u.id WHERE j.status = 'approve' ORDER BY j.created_at DESC");
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
