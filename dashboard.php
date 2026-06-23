<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

if ($role === 'client') {
    header("Location: client-dashboard.php");
    exit();
} elseif ($role === 'student') {
    header("Location: student-dashboard.php");
    exit();
} elseif ($role === 'admin') {
    header("Location: admin_approve.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
