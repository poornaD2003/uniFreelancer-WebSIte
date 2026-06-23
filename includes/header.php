<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
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
    </style>
</head>
<body>
    <nav>
        <a href="student_freelancer_site.php" class="logo">UniLance</a>
        <ul class="nav-links">
            <li><a href="student_freelancer_site.php">Home</a></li>
            <li><a href="jobs.php">Browse Jobs</a></li>
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
                <li><a href="client-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <li><a href="student-post-job.php">Post a Gig</a></li>
                <li><a href="student-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['club_id']) && $_SESSION['role'] === 'club'): ?>
                <li><a href="club_dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin_approve.php">Admin Panel</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isset($_SESSION['user_id'])): 
                $profile_page = ($_SESSION['role'] === 'student') ? 'studentProfile.php' : 'clientProfile.php';
                $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.png';
                $display_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'User';
                $icon_class = ($_SESSION['role'] === 'admin') ? 'fa-user-shield' : 'fa-user';
            ?>
                <div class="nav-profile">
                    <span class="user-greeting">
                        <i class="fas <?php echo $icon_class; ?>" style="color: var(--green); margin-right: 5px;"></i>
                        <strong><?php echo htmlspecialchars($display_name); ?></strong>
                    </span>
                    <?php if ($_SESSION['role'] !== 'admin'): ?>
                        <a href="<?php echo $profile_page; ?>" class="btn btn-outline">
                           Profile
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php elseif(isset($_SESSION['club_id']) && $_SESSION['role'] === 'club'): ?>
                <div class="nav-profile">
                    <span class="user-greeting">
                        <i class="fas fa-users" style="color: var(--green); margin-right: 5px;"></i>
                        <strong><?php echo htmlspecialchars($_SESSION['club_name'] ?? 'Club'); ?></strong>
                    </span>
                    <a href="club_dashboard.php" class="btn btn-outline">
                       Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>