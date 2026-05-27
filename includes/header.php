<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniLance | University Freelancing Platform</title>
    <link rel="stylesheet" href="css/style.css?v=2.0">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav>
        <a href="index.php" class="logo">UniLance</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="jobs.php">Browse Jobs</a></li>
            <li><a href="freelancers.php">Students</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="post-job.php">Post a Job</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>
