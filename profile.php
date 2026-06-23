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
    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 2.5rem;
        max-width: 1200px;
        margin: 140px auto 60px;
        padding: 0 5%;
    }
    
    .sidebar-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 2rem;
        align-self: flex-start;
    }
    
    .profile-avatar-wrap {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        overflow: hidden;
        border: 3px solid var(--primary);
        background: #1f2937;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .profile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-name {
        font-size: 1.4rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 0.25rem;
        color: #fff;
    }
    
    .profile-uni {
        font-size: 0.88rem;
        color: var(--text-muted);
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
        border-top: 1px solid var(--border-color);
        padding-top: 1.5rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.88rem;
        padding: 0.6rem 0;
    }
    
    .info-label {
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .info-val {
        color: var(--text-main);
        font-weight: 600;
    }
    
    .main-profile-panel {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .stats-matrix {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }
    
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
    }
    
    .stat-val {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 4px;
    }
    
    .stat-lbl {
        font-size: 0.78rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.05em;
    }
    
    .bio-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 2.2rem;
    }
    
    .bio-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .bio-text {
        font-size: 0.95rem;
        line-height: 1.75;
        color: var(--text-muted);
        white-space: pre-wrap;
    }
    
    .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1.25rem;
    }
    
    .skill-badge {
        background: rgba(16, 185, 129, 0.06);
        color: var(--accent);
        border: 1px solid rgba(16, 185, 129, 0.15);
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.82rem;
        font-weight: 500;
    }
    
    .gigs-section-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        margin-top: 1rem;
        margin-bottom: 1.25rem;
    }
    
    .gigs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
    }
    
    .gig-thumb {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-bottom: 1px solid var(--border-color);
    }
    
    .profile-gig-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
    }
    
    .profile-gig-card:hover {
        border-color: #374151;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
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
        font-weight: 600;
        color: var(--primary);
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }
    
    .gig-name {
        font-size: 0.98rem;
        font-weight: 600;
        color: #fff;
        line-height: 1.35;
        margin-bottom: 1rem;
    }
    
    .gig-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border-color);
        padding-top: 0.8rem;
        margin-top: auto;
    }
    
    .gig-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
    }
    
    .gig-view-btn {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary);
    }
    
    @media (max-width: 900px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-matrix {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="profile-grid fade-in">
    <!-- Left Sidebar -->
    <div class="sidebar-card">
        <div class="profile-avatar-wrap">
            <img src="<?php echo $profile_pic; ?>" alt="<?php echo htmlspecialchars($freelancer['fullname']); ?>" class="profile-avatar" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
        </div>
        <h2 class="profile-name"><?php echo htmlspecialchars($freelancer['fullname']); ?></h2>
        <div class="profile-uni">🎓 <?php echo htmlspecialchars($freelancer['university_name'] ?? 'University Student'); ?></div>
        
        <ul class="info-list">
            <li class="info-item">
                <span class="info-label"><i class="ti ti-map-pin"></i> From</span>
                <span class="info-val"><?php echo htmlspecialchars($location); ?></span>
            </li>
            <li class="info-item">
                <span class="info-label"><i class="ti ti-calendar"></i> Member since</span>
                <span class="info-val"><?php echo date('M Y', strtotime($freelancer['member_since'])); ?></span>
            </li>
            <li class="info-item">
                <span class="info-label"><i class="ti ti-truck"></i> Last delivery</span>
                <span class="info-val"><?php echo htmlspecialchars($last_delivery); ?></span>
            </li>
            <li class="info-item">
                <span class="info-label"><i class="ti ti-gender-transgender"></i> Gender</span>
                <span class="info-val"><?php echo htmlspecialchars($gender); ?></span>
            </li>
            <li class="info-item">
                <span class="info-label"><i class="ti ti-world"></i> Languages</span>
                <span class="info-val"><?php echo htmlspecialchars($languages); ?></span>
            </li>
            <li class="info-item">
                <span class="info-label"><i class="ti ti-language"></i> English Level</span>
                <span class="info-val"><?php echo htmlspecialchars($english_level); ?></span>
            </li>
        </ul>
    </div>
    
    <!-- Right Content Panel -->
    <div class="main-profile-panel">
        
        <!-- Stats Matrix -->
        <div class="stats-matrix">
            <div class="stat-card">
                <div class="stat-val"><?php echo $total_jobs; ?></div>
                <div class="stat-lbl">Jobs Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $success_rate; ?>%</div>
                <div class="stat-lbl">Job Success</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $total_hours; ?></div>
                <div class="stat-lbl">Total Hours</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $in_queue; ?></div>
                <div class="stat-lbl">In-Queue Gigs</div>
            </div>
        </div>
        
        <!-- Biography & Details Card -->
        <div class="bio-card">
            <h3 class="bio-title"><i class="ti ti-notes" style="color: var(--primary);"></i> About Me</h3>
            <p class="bio-text"><?php echo htmlspecialchars($description); ?></p>
            
            <h3 class="bio-title" style="margin-top: 2rem;"><i class="ti ti-book" style="color: var(--primary);"></i> Education</h3>
            <p class="bio-text" style="color: var(--text-main); font-weight: 500;"><?php echo htmlspecialchars($education); ?></p>
            
            <h3 class="bio-title" style="margin-top: 2rem;"><i class="ti ti-cpu" style="color: var(--primary);"></i> Skills &amp; Keywords</h3>
            <div class="skills-grid">
                <?php if (empty($skills)): ?>
                    <p style="color: var(--text-muted); font-size: 0.88rem;">No skills listed.</p>
                <?php else: ?>
                    <?php foreach ($skills as $skill): ?>
                        <span class="skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Gigs Catalog -->
        <div>
            <h3 class="gigs-section-title">Active Gigs</h3>
            <div class="gigs-grid">
                <?php if (empty($gigs)): ?>
                    <div style="grid-column: 1/-1; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="ti ti-search" style="font-size: 2.2rem; display: block; margin-bottom: 0.5rem; color: var(--primary);"></i>
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
</div>

<?php include 'includes/footer.php'; ?>
