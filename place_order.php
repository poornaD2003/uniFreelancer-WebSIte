<?php
// Include the database configuration module
include 'db.php';
session_start();

// Mock authentication checking: Set default Client ID if session is empty
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$client_id = $_SESSION['user_id'];

// Check if a specific student gig selection has been requested
if (isset($_GET['gig_id'])) {
    $gig_id = intval($_GET['gig_id']);

    // Database Lookup: Identify which student developer created this specific gig
    $gig_query = $conn->query("SELECT student_id FROM gigs WHERE id = '$gig_id'");

    if ($gig_query && $gig_query->num_rows > 0) {
        $gig = $gig_query->fetch_assoc();
        $student_id = $gig['student_id'];

        // CORE WORKFLOW: Place a new order inside the tracking management table
        // Notice: This handles order instantiation only, not financial payment processing.
        $insert_order = "INSERT INTO orders (client_id, student_id, gig_id, status) VALUES ('$client_id', '$student_id', '$gig_id', 'pending')";

        if ($conn->query($insert_order)) {
            // Success: Securely forward the client directly back to their main dashboard view
            header("Location: client_dashboard.php");
            exit();
        } else {
            echo "System Database Error: " . $conn->error;
        }
    } else {
        echo "Error: The requested student gig record could not be found in our directory.";
    }
} else {
    echo "Error: No specific service gig was selected for ordering.";
}
?>