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
    <nav>
        <a href="index.php" class="logo">UniLance</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="jobs.php">Browse Jobs</a></li>
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
                <li><a href="client-dashboard.php">Dashboard</a></li>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                 <li><a href="post-job.php">Post a Job</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isset($_SESSION['user_id'])): 
          //  echo $_SESSION['user_id'];
            //echo $_SESSION['role'];
                // Determine the correct profile page based on user role
                $profile_page = ($_SESSION['role'] === 'student') ? 'studentProfile.php' : 'clientProfile.php';
                $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.png';
            ?>
                <div class="nav-profile">
                    <a href="<?php echo $profile_page; ?>" class="btn btn-outline">
                       Profile
                    </a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>