<?php
include 'includes/db.php';

$sql = "ALTER TABLE users 
        ADD COLUMN phonenumber VARCHAR(20) DEFAULT NULL, 
        ADD COLUMN address VARCHAR(255) DEFAULT NULL;";

if ($conn->query($sql)) {
    echo "<h1>Database successfully updated!</h1>";
    echo "<p>The phonenumber and address columns have been added to the users table.</p>";
    echo "<p><a href='adminProfile.php'>Click here to return to your Admin Profile</a></p>";
} else {
    echo "<h1>Notice</h1>";
    echo "<p>The columns may already exist or there was an error: " . $conn->error . "</p>";
    echo "<p><a href='adminProfile.php'>Click here to return to your Admin Profile</a></p>";
}
?>
