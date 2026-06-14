<?php
$host = 'localhost';
$dbname = 'unilance_db';
$username = 'root';
$password = '';

// mysqli_connect() මඟින් database එකට සම්බන්ධ වීම
$conn = mysqli_connect($host, $username, $password, $dbname);

// Connection එක සාර්ථකද කියා පරීක්ෂා කිරීම
if (!$conn) {
    // Local development වලදී error එක බලාගන්න (Production වලදී මෙය log කරන්න)
    die("Database connection failed: " . mysqli_connect_error());
}

// Database එකට Unicode (Sinhala/Emoji) දත්ත හරියට ඇතුල් වෙන්න මේ පේළිය දාන එක හොඳයි
mysqli_set_charset($conn, "utf8mb4");
?>