<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniLance | University Freelancing Platform</title>
    <link rel="stylesheet" href="css/style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-profile {
            display: flex;
            align-items: center;
            gap: 15px;

        }
        .user-greeting {
            font-size: 0.92rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--green-dim);
            border-radius: 50px;
            border: 1px solid var(--border);
            font-weight: 500;
        }
        .user-greeting {
            font-size: 0.92rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--green-dim);
            border-radius: 50px;
            border: 1px solid var(--border);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav id="nav">
        <a href="student_freelancer_site.php" class="logo">UniLance</a>
        <ul class="nav-links">
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                <!-- No navbar links for admin -->
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
                <li><a href="student_freelancer_site.php">Home</a></li>
                <li><a href="jobs.php">Browse Jobs</a></li>
                <li><a href="client-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <li><a href="student_freelancer_site.php">Home</a></li>
                <li><a href="jobs.php">Browse Jobs</a></li>
                <li><a href="student-post-job.php">Post a Gig</a></li>
                <li><a href="student-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['club_id']) && $_SESSION['role'] === 'club'): ?>
                <li><a href="club_dashboard.php">Dashboard</a></li>
            <?php else: ?>
                <li><a href="student_freelancer_site.php">Home</a></li>
                <li><a href="jobs.php">Browse Jobs</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> f2037cb2333b3b1dd00a691bee64ee041cba301b
            <?php if(isset($_SESSION['user_id'])):
                if ($_SESSION['role'] === 'student') {
                    $profile_page = 'studentProfile.php';
                } elseif ($_SESSION['role'] === 'client') {
                    $profile_page = 'clientProfile.php';
                } else {
                    $profile_page = 'adminProfile.php';
                }

                if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])) {
<<<<<<< HEAD
=======
            <?php if(isset($_SESSION['user_id'])): 
                $profile_page = ($_SESSION['role'] === 'student') ? 'studentProfile.php' : 'clientProfile.php';
                 if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])) {
>>>>>>> 51af2466c40eb904fe8bad67da69fad4b70d3397
=======
>>>>>>> f2037cb2333b3b1dd00a691bee64ee041cba301b
                    $pure_filename = basename($_SESSION['profile_pic']); 
                    $profile_pic = '/unilance/uploads/' . $pure_filename;
                } else {
                    $profile_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                }
<<<<<<< HEAD
<<<<<<< HEAD

                $display_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'User';
                $icon_class = ($_SESSION['role'] === 'admin') ? 'fa-user-shield' : 'fa-user';
            ?>
                <div class="nav-profile">
                    <span class="user-greeting">
                        <i class="fas <?php echo $icon_class; ?>" style="color: var(--green);"></i>
                        <strong><?php echo htmlspecialchars($display_name); ?></strong>
                    </span>
                    <a href="<?php echo $profile_page; ?>">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>"
                             alt="Profile Picture"
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary, #10b981);"
=======
=======
>>>>>>> f2037cb2333b3b1dd00a691bee64ee041cba301b
                $display_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'User';
                $icon_class = ($_SESSION['role'] === 'admin') ? 'fa-user-shield' : 'fa-user';
            ?>
             <div class="nav-profile" style="display: flex; align-items: center; gap: 12px;">
                    <a href="<?php echo $profile_page; ?>">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                             alt="Profile Picture" 
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary, #7c3aed);"
<<<<<<< HEAD
>>>>>>> 51af2466c40eb904fe8bad67da69fad4b70d3397
=======
>>>>>>> f2037cb2333b3b1dd00a691bee64ee041cba301b
                             onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
                    </a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
               
            <?php elseif(isset($_SESSION['club_id']) && $_SESSION['role'] === 'club'): ?>
                <div class="nav-profile">
                    <span class="user-greeting">
                        <i class="fas fa-users" style="color: var(--green);"></i>
                        <strong><?php echo htmlspecialchars($_SESSION['club_name'] ?? 'Club'); ?></strong>
                    </span>
                    <a href="club_dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>