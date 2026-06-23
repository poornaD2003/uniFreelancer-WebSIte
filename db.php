<?php
// Database connection config matching your custom XAMPP ports
$host = "127.0.0.1:3307";
$user = "root";
$pass = "";
$dbname = "unilance_db";

$conn = new mysqli($host, $user, $pass, $dbname);

// Error check connection link
if ($conn->connect_error) {
    die("Database Link Failed: " . $conn->connect_error);
}
?>