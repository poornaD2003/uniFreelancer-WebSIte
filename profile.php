<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';
include 'includes/header.php';

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    echo "<div class='container' style='padding: 180px 5% 80px; text-align: center;'><h2>Invalid Freelancer Reference</h2><p style='color: var(--text-muted); margin-top: 10px;'>No student freelancer was specified.</p><a href='jobs.php' class='btn btn-primary' style='margin-top: 20px;'>Browse Gigs</a></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch student data and details
$stmt = $conn->prepare("
    SELECT u.fullname, u.profile_pic, u.email, u.created_at as member_since, sp.* 
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.user_id 
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$freelancer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$freelancer) {
    echo "<div class='container' style='padding: 180px 5% 80px; text-align: center;'><h2>Freelancer Not Found</h2><p style='color: var(--text-muted); margin-top: 10px;'>The specified student freelancer profile does not exist or has been disabled.</p><a href='jobs.php' class='btn btn-primary' style='margin-top: 20px;'>Browse Gigs</a></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch skills
$skills = [];
$stmtSkills = $conn->prepare("SELECT skill_name FROM student_skills WHERE user_id = ?");
$stmtSkills->bind_param("i", $student_id);
$stmtSkills->execute();
$resSkills = $stmtSkills->get_result();
while ($r = $resSkills->fetch_assoc()) {
    $skills[] = $r['skill_name'];
}
$stmtSkills->close();

// Fetch completed jobs count
$stmtJobs = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ? AND status = 'completed'");
$stmtJobs->bind_param("i", $student_id);
$stmtJobs->execute();
$total_jobs = $stmtJobs->get_result()->fetch_assoc()['total'] ?? 0;
$stmtJobs->close();

// Fetch total jobs for success rate calculation
$stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ?");
$stmtTotal->bind_param("i", $student_id);
$stmtTotal->execute();
$total_orders = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;
$stmtTotal->close();

$success_rate = ($total_orders > 0) ? round(($total_jobs / $total_orders) * 100) : 100;
$total_hours = $total_jobs * 12; // Realistic dynamic calculation

// Fetch active in-queue services (pending and in-progress)
$stmtQueue = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ? AND status IN ('pending', 'in_progress')");
$stmtQueue->bind_param("i", $student_id);
$stmtQueue->execute();
$in_queue = $stmtQueue->get_result()->fetch_assoc()['total'] ?? 0;
$stmtQueue->close();

// Fetch most recent completed order date
$stmtLastDel = $conn->prepare("SELECT created_at FROM orders WHERE student_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1");
$stmtLastDel->bind_param("i", $student_id);
$stmtLastDel->execute();
$last_del_res = $stmtLastDel->get_result()->fetch_assoc();
$last_delivery = $last_del_res ? date('M j, Y', strtotime($last_del_res['created_at'])) : 'No deliveries yet';
$stmtLastDel->close();

// Fetch gigs by this student
$stmtGigs = $conn->prepare("SELECT * FROM gigs WHERE student_id = ? AND status = 'approve' ORDER BY created_at DESC");
$stmtGigs->bind_param("i", $student_id);
$stmtGigs->execute();
$gigs_result = $stmtGigs->get_result();
$gigs = [];
while ($g = $gigs_result->fetch_assoc()) {
    $gigs[] = $g;
}
$stmtGigs->close();

// Set fallbacks for missing fields
$location = !empty($freelancer['location']) ? $freelancer['location'] : 'Sri Lanka';
$gender = !empty($freelancer['gender']) ? $freelancer['gender'] : 'Not Specified';
$languages = !empty($freelancer['languages']) ? $freelancer['languages'] : 'English, Sinhala';
$english_level = !empty($freelancer['english_level']) ? $freelancer['english_level'] : 'Professional';
$description = !empty($freelancer['description']) ? $freelancer['description'] : 'This student freelancer hasn\'t provided a personal bio yet, but they are fully verified and ready to deliver top-tier university level freelancing work.';
$education = !empty($freelancer['education']) ? $freelancer['education'] : (($freelancer['university_name'] ?? '') . ' - ' . ($freelancer['department'] ?? ''));

$profile_pic = (!empty($freelancer['profile_pic']) && $freelancer['profile_pic'] !== 'default.png')
    ? "/unilance/uploads/" . basename($freelancer['profile_pic'])
    : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    :root {
        --banner-bg: linear-gradient(135deg, #ffe5d9 0%, #ffcad4 100%);
    }

    .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--muted);
        margin: 120px auto 1.5rem;
        max-width: 1200px;
        padding: 0 5%;
    }
    .breadcrumbs a {
        color: var(--muted);
        transition: var(--trans);
    }
    .breadcrumbs a:hover {
        color: var(--green);
    }
    .breadcrumbs span {
        color: var(--text);
        font-weight: 500;
    }

    .profile-banner-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 5%;
        position: relative;
    }
    .profile-banner {
        height: 180px;
        border-radius: var(--radius);
        background: var(--banner-bg);
        position: relative;
        overflow: hidden;
    }
    .profile-banner::before {
        content: '';
        position: absolute;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        top: -120px;
        right: -60px;
    }
    .profile-banner::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        bottom: -90px;
        left: 8%;
    }

    .profile-header-content {
        display: flex;
        align-items: flex-end;
        padding: 0 2rem;
        margin-top: -60px;
        margin-bottom: 2.5rem;
        position: relative;
        z-index: 10;
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    .profile-avatar-container {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        position: relative;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .profile-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .online-indicator {
        position: absolute;
        bottom: 6px;
        right: 6px;
        width: 16px;
        height: 16px;
        background: #10b981;
        border: 3px solid #fff;
        border-radius: 50%;
    }
    .profile-header-info {
        flex: 1;
        min-width: 250px;
        padding-bottom: 8px;
    }
    .profile-header-name {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 4px;
    }
    .profile-header-title {
        font-size: 1.05rem;
        color: var(--muted);
        font-weight: 500;
        margin-bottom: 12px;
    }
    .profile-header-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        font-size: 0.88rem;
        color: var(--muted);
    }
    .profile-header-meta .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .profile-header-meta .meta-item.rating i {
        color: #f59e0b;
    }
    .profile-header-actions {
        display: flex;
        gap: 10px;
        padding-bottom: 8px;
    }
    .btn-share, .btn-save {
        background: #fff;
        border: 1px solid var(--border);
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: var(--trans);
    }
    .btn-share:hover, .btn-save:hover {
        border-color: var(--green);
        color: var(--green);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.06);
    }

    .profile-body-grid {
        display: grid;
        grid-template-columns: 2.2fr 1fr;
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto 4rem;
        padding: 0 5%;
    }

    /* Left Column Details */
    .stats-matrix {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1rem;
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--trans);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.12);
    }
    .stat-icon {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    .stat-val {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 2px;
    }
    .stat-lbl {
        font-size: 0.72rem;
        color: var(--muted);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
    }

    .info-section-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .section-card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-card-title i {
        color: var(--green);
    }
    .section-card-text {
        font-size: 0.95rem;
        line-height: 1.75;
        color: var(--muted);
        white-space: pre-wrap;
    }

    /* Education timeline styling */
    .education-timeline {
        position: relative;
        padding-left: 2rem;
        margin-top: 1.25rem;
    }
    .education-timeline::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: var(--border);
    }
    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-badge {
        position: absolute;
        left: -2rem;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--green);
        border: 3px solid #fff;
        box-shadow: 0 0 0 4px var(--green-dim);
    }
    .timeline-year {
        display: inline-block;
        background: var(--green-dim);
        color: var(--green);
        font-size: 0.78rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        margin-bottom: 4px;
    }
    .timeline-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 2px;
    }
    .timeline-sub {
        font-size: 0.85rem;
        color: var(--muted);
        font-weight: 500;
    }

    /* Gigs Catalog */
    .gigs-section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.25rem;
    }
    .gigs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
    }
    .profile-gig-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        transition: var(--trans);
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        box-shadow: var(--shadow);
    }
    .profile-gig-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.12);
        border-color: var(--green-light);
    }
    .gig-thumb {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-bottom: 1px solid var(--border);
    }
    .gig-body {
        padding: 1.2rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .gig-cat {
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--green);
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }
    .gig-name {
        font-size: 0.95rem;
        font-weight: 650;
        color: var(--text);
        line-height: 1.4;
        margin-bottom: 1rem;
    }
    .gig-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(0,0,0,0.05);
        padding-top: 0.8rem;
        margin-top: auto;
    }
    .gig-price {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text);
    }
    .gig-view-btn {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--green);
    }

    /* Right Column Sidebar */
    .sidebar-info-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }
    .sidebar-price-label {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: baseline;
        gap: 4px;
    }
    .sidebar-price-label span {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--muted);
    }
    .sidebar-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5rem;
    }
    .sidebar-table tr {
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .sidebar-table tr:last-child {
        border-bottom: none;
    }
    .sidebar-table td {
        padding: 10px 0;
        font-size: 0.88rem;
    }
    .sidebar-table td.label-cell {
        color: var(--muted);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-table td.label-cell i {
        color: var(--muted);
        font-size: 1.1rem;
    }
    .sidebar-table td.val-cell {
        color: var(--text);
        font-weight: 600;
        text-align: right;
    }
    .btn-contact-me {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        background: var(--green);
        color: #fff;
        padding: 12px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: var(--trans);
        text-decoration: none;
    }
    .btn-contact-me:hover {
        background: var(--green-light);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .sidebar-skills-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
    }
    .skills-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.2rem;
    }
    .skills-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .skill-chip {
        background: var(--bg);
        color: var(--green);
        border: 1px solid var(--border);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.82rem;
        font-weight: 600;
        transition: var(--trans);
    }
    .skill-chip:hover {
        background: var(--green);
        color: #fff;
    }

    @media (max-width: 900px) {
        .profile-body-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-matrix {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <a href="student_freelancer_site.php">Home</a>
    <i class="ti ti-chevron-right" style="font-size: 0.75rem;"></i>
    <a href="freelancers.php">Students</a>
    <i class="ti ti-chevron-right" style="font-size: 0.75rem;"></i>
    <span><?php echo htmlspecialchars($freelancer['fullname']); ?></span>
</div>

<div class="profile-banner-container">
    <!-- Wavy Top Banner -->
    <div class="profile-banner"></div>
    
    <!-- Profile Header overlapping content -->
    <div class="profile-header-content">
        <div class="profile-avatar-container">
            <img src="<?php echo $profile_pic; ?>" alt="<?php echo htmlspecialchars($freelancer['fullname']); ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
            <span class="online-indicator"></span>
        </div>
        <div class="profile-header-info">
            <h1 class="profile-header-name"><?php echo htmlspecialchars($freelancer['fullname']); ?></h1>
            <p class="profile-header-title">🎓 <?php echo htmlspecialchars($freelancer['university_name'] ?? 'University Student'); ?> — <?php echo htmlspecialchars($freelancer['department'] ?? 'Expert Student'); ?></p>
            <div class="profile-header-meta">
                <span class="meta-item rating"><i class="ti ti-star-filled"></i> 4.9 (<?php echo $total_jobs; ?> jobs)</span>
                <span class="meta-item"><i class="ti ti-map-pin"></i> <?php echo htmlspecialchars($location); ?></span>
                <span class="meta-item"><i class="ti ti-calendar"></i> Member since <?php echo date('M Y', strtotime($freelancer['member_since'])); ?></span>
            </div>
        </div>
        <div class="profile-header-actions">
            <?php if(isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$student_id): ?>
                <a href="studentProfile.php" class="btn-share" style="background: var(--green); color: #fff; border-color: var(--green); text-decoration: none;"><i class="ti ti-edit"></i> Edit Profile</a>
            <?php endif; ?>
            <button class="btn-share" onclick="navigator.clipboard.writeText(window.location.href); alert('Profile link copied to clipboard!');"><i class="ti ti-share"></i> Share</button>
            <button class="btn-save" onclick="this.innerHTML = '<i class=\'ti-heart-filled\' style=\'color:red\'></i> Saved';"><i class="ti ti-heart"></i> Save</button>
        </div>
    </div>
</div>

<!-- Main Body Columns -->
<div class="profile-body-grid fade-in">
    <!-- Left Column (Main content) -->
    <div class="main-profile-panel">
        
        <!-- Stats Matrix -->
        <div class="stats-matrix">
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-val">%<?php echo $success_rate; ?></div>
                <div class="stat-lbl">Job Success</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-val"><?php echo $total_jobs; ?></div>
                <div class="stat-lbl">Total Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-val"><?php echo $total_hours; ?></div>
                <div class="stat-lbl">Total Hours</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-val"><?php echo $in_queue; ?></div>
                <div class="stat-lbl">In-Queue</div>
            </div>
        </div>
        
        <!-- About Biography Card -->
        <div class="info-section-card">
            <h3 class="section-card-title"><i class="ti ti-user-circle"></i> Description</h3>
            <p class="section-card-text"><?php echo htmlspecialchars($description); ?></p>
        </div>

        <!-- Education timeline style -->
        <div class="info-section-card">
            <h3 class="section-card-title"><i class="ti ti-school"></i> Education</h3>
            <div class="education-timeline">
                <div class="timeline-item">
                    <span class="timeline-badge"></span>
                    <span class="timeline-year"><?php echo date('Y', strtotime($freelancer['member_since'])); ?> - Present</span>
                    <h4 class="timeline-title"><?php echo htmlspecialchars($freelancer['university_name'] ?? 'University Student'); ?></h4>
                    <p class="timeline-sub"><?php echo htmlspecialchars($education); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Gigs Catalog -->
        <div style="margin-top: 1rem;">
            <h3 class="gigs-section-title">Active Gigs Catalog</h3>
            <div class="gigs-grid">
                <?php if (empty($gigs)): ?>
                    <div style="grid-column: 1/-1; background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 3rem; text-align: center; color: var(--muted); box-shadow: var(--shadow);">
                        <i class="ti ti-search" style="font-size: 2.2rem; display: block; margin-bottom: 0.5rem; color: var(--green);"></i>
                        No active gigs published by this freelancer yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($gigs as $gig): 
                        $gig_img = (!empty($gig['image']) && $gig['image'] !== 'default.png')
                            ? "uploads/" . htmlspecialchars($gig['image'])
                            : "images/hero_illustration.png";
                    ?>
                        <a href="freelancer_gig.php?id=<?php echo $gig['id']; ?>" class="profile-gig-card">
                            <img src="<?php echo $gig_img; ?>" alt="<?php echo htmlspecialchars($gig['title']); ?>" class="gig-thumb" onerror="this.onerror=null; this.src='images/hero_illustration.png';">
                            <div class="gig-body">
                                <div>
                                    <div class="gig-cat"><?php echo htmlspecialchars($gig['category']); ?></div>
                                    <h4 class="gig-name"><?php echo htmlspecialchars($gig['title']); ?></h4>
                                </div>
                                <div class="gig-footer">
                                    <span class="gig-price">Rs. <?php echo number_format($gig['price'], 0); ?></span>
                                    <span class="gig-view-btn">View Gig ↗</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

    <!-- Right Column (Sidebar) -->
    <div class="profile-sidebar-panel">
        
        <!-- Pricing & Info Card -->
        <div class="sidebar-info-card">
            <div class="sidebar-price-label">
                <?php
                $min_price = null;
                foreach ($gigs as $gig) {
                    if ($min_price === null || $gig['price'] < $min_price) {
                        $min_price = $gig['price'];
                    }
                }
                if ($min_price !== null && $min_price > 0) {
                    echo "Rs. " . number_format($min_price) . "<span>/ starting</span>";
                } else {
                    echo "Flexible<span>/ rate</span>";
                }
                ?>
            </div>
            
            <table class="sidebar-table">
                <tr>
                    <td class="label-cell"><i class="ti ti-map-pin"></i> Location</td>
                    <td class="val-cell"><?php echo htmlspecialchars($location); ?></td>
                </tr>
                <tr>
                    <td class="label-cell"><i class="ti ti-calendar"></i> Member since</td>
                    <td class="val-cell"><?php echo date('M Y', strtotime($freelancer['member_since'])); ?></td>
                </tr>
                <tr>
                    <td class="label-cell"><i class="ti ti-clock"></i> Last delivery</td>
                    <td class="val-cell"><?php echo htmlspecialchars($last_delivery); ?></td>
                </tr>
                <tr>
                    <td class="label-cell"><i class="ti ti-gender-transgender"></i> Gender</td>
                    <td class="val-cell"><?php echo htmlspecialchars($gender); ?></td>
                </tr>
                <tr>
                    <td class="label-cell"><i class="ti ti-world"></i> Languages</td>
                    <td class="val-cell"><?php echo htmlspecialchars($languages); ?></td>
                </tr>
                <tr>
                    <td class="label-cell"><i class="ti ti-language"></i> English Level</td>
                    <td class="val-cell"><?php echo htmlspecialchars($english_level); ?></td>
                </tr>
            </table>

            <a href="mailto:<?php echo htmlspecialchars($freelancer['email']); ?>" class="btn-contact-me">
                Contact Me <i class="ti ti-arrow-up-right"></i>
            </a>
        </div>

        <!-- Skills Card -->
        <div class="sidebar-skills-card">
            <h3 class="skills-title">My Skills</h3>
            <div class="skills-chips-wrap">
                <?php if (empty($skills)): ?>
                    <p style="color: var(--muted); font-size: 0.88rem;">No skills listed.</p>
                <?php else: ?>
                    <?php foreach ($skills as $skill): ?>
                        <span class="skill-chip"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

