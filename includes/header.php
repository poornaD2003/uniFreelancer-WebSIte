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
    </style>
</head>
<body>
    <nav id="nav">
        <a href="student_freelancer_site.php" class="logo">UniLance</a>
        <ul class="nav-links">
            <li><a href="student_freelancer_site.php">Home</a></li>
            <li><a href="jobs.php">Browse Jobs</a></li>
            <li><a href="freelancers.php">Students</a></li>
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
                <li><a href="client-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                 <li><a href="student-post-job.php">Post a Gig</a></li>
                <li><a href="student-dashboard.php">Dashboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isset($_SESSION['user_id'])): 
                $profile_page = ($_SESSION['role'] === 'student') ? 'studentProfile.php' : 'clientProfile.php';
                
                if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])) {
                    $pure_filename = basename($_SESSION['profile_pic']); 
                    $profile_pic = '/unilance/uploads/' . $pure_filename;
                } else {
                    $profile_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                }
            ?>
                <div class="nav-profile" style="display: flex; align-items: center; gap: 12px;">
                    <a href="<?php echo $profile_page; ?>">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                             alt="Profile Picture" 
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary, #7c3aed);"
                             onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
                    </a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>  
                <a href="login.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>