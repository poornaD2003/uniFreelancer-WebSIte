<?php
include 'db.php';
include 'includes/header.php';

session_start();

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
            header("Location: client_dashboard.php?success=1#status");
            exit();
        } else {
            echo "Database error handling entry: " . $conn->error;
            echo "System Database Error: " . $conn->error;
        }
    } else {
        echo "Selected student gig reference not found.";
        echo "Error: The requested student gig record could not be found in our directory.";
    }
} else {
    echo "No project gig was selected for purchase.";
    echo "Error: No specific service gig was selected for ordering.";
}
?>
<?php include 'includes/footer.php'; ?>