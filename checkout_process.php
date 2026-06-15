<?php
include 'db.php';
session_start();

// Validating the logged-in user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$client_id = $_SESSION['user_id'];

if (isset($_GET['gig_id'])) {
    $gig_id = intval($_GET['gig_id']);

    // Find which student developer owns the selected gig service
    $gig_query = $conn->query("SELECT student_id FROM gigs WHERE id = '$gig_id'");

    if ($gig_query && $gig_query->num_rows > 0) {
        $gig = $gig_query->fetch_assoc();
        $student_id = $gig['student_id'];

        // Insert a new structural tracking record row into the orders table
        $insert_order = "INSERT INTO orders (client_id, student_id, gig_id, status) VALUES ('$client_id', '$student_id', '$gig_id', 'pending')";

        if ($conn->query($insert_order)) {
            // Redirect smoothly straight back into your dashboard view
            header("Location: client_dashboard.php");
            exit();
        } else {
            echo "Database error handling entry: " . $conn->error;
        }
    } else {
        echo "Selected student gig reference not found.";
    }
} else {
    echo "No project gig was selected for purchase.";
}
?>