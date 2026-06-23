<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$client_id = $_SESSION['user_id'];

if (isset($_GET['gig_id'])) {
    $gig_id = intval($_GET['gig_id']);

    $gig_query = $conn->query("SELECT student_id FROM gigs WHERE id = '$gig_id'");

    if ($gig_query && $gig_query->num_rows > 0) {
        $gig = $gig_query->fetch_assoc();
        $student_id = $gig['student_id'];

        $insert_order = "INSERT INTO orders (client_id, student_id, gig_id, status) VALUES ('$client_id', '$student_id', '$gig_id', 'pending')";

        if ($conn->query($insert_order)) {
            header("Location: client-dashboard.php?success=1#status");
            exit();
        } else {
            $error = "Database error handling entry: " . $conn->error;
        }
    } else {
        $error = "Selected student gig reference not found.";
    }
} else {
    $error = "No project gig was selected for purchase.";
}

include 'includes/header.php';
?>

<div class="container card fade-in" style="max-width: 600px; margin: 140px auto 40px; padding: 2.5rem; text-align: center;">
    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 1.5rem; border-radius: 12px; color: #ef4444; font-weight: 600; margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="jobs.php" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="fas fa-th-large"></i> Return to Gigs
        </a>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>