<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';
include 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$faculty_filter = isset($_GET['faculty']) ? trim($_GET['faculty']) : '';

// Base query for active student freelancers
$query = "
    SELECT u.id, u.fullname, u.profile_pic, sp.university_name, sp.faculty, sp.department, sp.club_affiliations,
           (SELECT COUNT(*) FROM orders WHERE student_id = u.id AND status = 'completed') as jobs_completed,
           (SELECT MIN(price) FROM gigs WHERE student_id = u.id) as min_rate
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'student' AND u.status = 'active'
";

// Apply search filter
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $query .= " AND (u.fullname LIKE '%$search_escaped%' OR sp.university_name LIKE '%$search_escaped%' OR sp.department LIKE '%$search_escaped%' OR EXISTS (SELECT 1 FROM student_skills WHERE user_id = u.id AND skill_name LIKE '%$search_escaped%'))";
}

// Apply faculty filter
if (!empty($faculty_filter)) {
    $faculty_escaped = $conn->real_escape_string($faculty_filter);
    $query .= " AND sp.faculty = '$faculty_escaped'";
}

$query .= " ORDER BY u.fullname ASC";
$result = $conn->query($query);

$faculties = [
    "Faculty of Science", "Faculty of Arts", "Faculty of Computing", 
    "Faculty of Engineering", "Faculty of Management and Finance"
];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    .directory-hero {
        padding: 160px 5% 40px;
        text-align: center;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .directory-hero h1 {
        font-size: 2.8rem;
        margin-bottom: 0.8rem;
        letter-spacing: -0.02em;
        color: #fff;
    }
    
    .directory-hero p {
        color: var(--text-muted);
        font-size: 1.05rem;
    }
    
    .search-filter-wrap {
        max-width: 800px;
        margin: 2rem auto;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .search-input-group {
        flex: 1;
        min-width: 280px;
        display: flex;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        align-items: center;
        padding-left: 1rem;
    }
    
    .search-input-group i {
        color: var(--text-muted);
    }
    
    .search-input-group input {
        border: none;
        background: transparent;
        color: #fff;
        padding: 0.8rem 1rem;
        width: 100%;
        outline: none;
        font-family: inherit;
    }
    
    .filter-dropdown {
        width: 220px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-main);
        padding: 0 1rem;
        outline: none;
        font-family: inherit;
        cursor: pointer;
    }
    
    .directory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto 80px;
        padding: 0 5%;
    }
    
    .freelancer-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: var(--transition);
        position: relative;
    }
    
    .freelancer-card:hover {
        border-color: #374151;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    }
    
    .card-top {
        display: flex;
        gap: 1.2rem;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    
    .card-avatar-wrap {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid var(--primary);
        background: #1f2937;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .card-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .card-meta h3 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 2px;
    }
    
    .card-meta .uni {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .card-meta .dept {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }
    
    .card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1.5rem;
        flex: 1;
    }
    
    .card-tag {
        font-size: 0.72rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border-color);
        padding-top: 1.2rem;
        margin-top: auto;
    }
    
    .card-rate-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.04em;
        margin-bottom: 2px;
    }
    
    .card-rate {
        font-size: 1.15rem;
        font-weight: 700;
        color: #fff;
    }
    
    .card-jobs {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 600;
    }
    
    .card-btn {
        background: transparent;
        border: 1px solid var(--primary);
        color: var(--primary);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        text-align: center;
    }
    
    .card-btn:hover {
        background: var(--primary);
        color: #fff;
    }
</style>

<div class="directory-hero fade-in">
    <h1>Connect with Verified Student Talent</h1>
    <p>Discover skilled student developers, designers, and tutors from leading campuses.</p>
    
    <form method="GET" action="freelancers.php">
        <div class="search-filter-wrap">
            <div class="search-input-group">
                <i class="ti ti-search"></i>
                <input type="text" name="search" placeholder="Search by name, university, skills..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <select name="faculty" class="filter-dropdown" onchange="this.form.submit()">
                <option value="">All Faculties</option>
                <?php foreach ($faculties as $fac): ?>
                    <option value="<?php echo htmlspecialchars($fac); ?>" <?php echo ($faculty_filter === $fac) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fac); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary" style="height: 48px;">Search</button>
        </div>
    </form>
</div>

<div class="directory-grid fade-in">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($f = $result->fetch_assoc()): 
            $initial = mb_substr($f['fullname'], 0, 1);
            $profile_pic = (!empty($f['profile_pic']) && $f['profile_pic'] !== 'default.png')
                ? "/unilance/uploads/" . basename($f['profile_pic'])
                : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                
            $rate_display = ($f['min_rate'] > 0) ? "Rs. " . number_format($f['min_rate']) : "Flexible";
            $jobs_count = $f['jobs_completed'] ? $f['jobs_completed'] : 0;
            
            // Build tags list
            $tags = [];
            if (!empty($f['department'])) $tags[] = $f['department'];
            if (!empty($f['faculty'])) $tags[] = $f['faculty'];
            if (!empty($f['club_affiliations'])) {
                $clubs = explode(',', $f['club_affiliations']);
                foreach ($clubs as $c) {
                    $tags[] = trim($c);
                }
            }
            $tags = array_slice(array_unique($tags), 0, 3); // Max 3 tags
        ?>
            <div class="freelancer-card">
                <div>
                    <div class="card-top">
                        <div class="card-avatar-wrap">
                            <img src="<?php echo $profile_pic; ?>" alt="<?php echo htmlspecialchars($f['fullname']); ?>" class="card-avatar" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
                        </div>
                        <div class="card-meta">
                            <h3><?php echo htmlspecialchars($f['fullname']); ?></h3>
                            <div class="uni">🎓 <?php echo htmlspecialchars($f['university_name']); ?></div>
                            <div class="dept"><?php echo htmlspecialchars($f['department']); ?></div>
                        </div>
                    </div>
                    
                    <div class="card-tags">
                        <?php foreach ($tags as $tag): ?>
                            <span class="card-tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div>
                        <div class="card-rate-label">Starting Rate</div>
                        <div class="card-rate"><?php echo $rate_display; ?></div>
                        <div class="card-jobs"><?php echo $jobs_count; ?> jobs completed</div>
                    </div>
                    <a href="profile.php?id=<?php echo $f['id']; ?>" class="card-btn">View Profile</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 4rem; text-align: center; color: var(--text-muted);">
            <i class="ti ti-users" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
            <h2>No student freelancers found</h2>
            <p>Try adjusting your search query or faculty filter.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
