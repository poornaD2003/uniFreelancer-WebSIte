<?php
$host = 'localhost';
$dbname = 'unilance_db';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Initialize PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    // Auto-create order_messages if it does not exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        deleted_by_client TINYINT(1) DEFAULT 0,
        deleted_by_student TINYINT(1) DEFAULT 0,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(orderId) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Self-healing database migrations for existing order_messages table
    // 1. Ensure file_path column exists
    $check_file = $pdo->query("SHOW COLUMNS FROM order_messages LIKE 'file_path'");
    $has_file_path = $check_file->fetch();
    $check_file->closeCursor();
    if (!$has_file_path) {
        $pdo->exec("ALTER TABLE order_messages ADD COLUMN file_path VARCHAR(255) DEFAULT NULL AFTER message");
    }

    // 2. Ensure sent_at column exists
    $check_sent = $pdo->query("SHOW COLUMNS FROM order_messages LIKE 'sent_at'");
    $has_sent_at = $check_sent->fetch();
    $check_sent->closeCursor();
    if (!$has_sent_at) {
        try {
            // Attempt to rename created_at to sent_at if created_at exists
            $pdo->exec("ALTER TABLE order_messages CHANGE COLUMN created_at sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        } catch (PDOException $e2) {
            $pdo->exec("ALTER TABLE order_messages ADD COLUMN sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    }

    // 3. Ensure message column is nullable (so files can be uploaded without text messages)
    try {
        $pdo->exec("ALTER TABLE order_messages MODIFY COLUMN message TEXT NULL");
    } catch (PDOException $e) {
        // Ignore if alter fails
    }

    // 4. Ensure deleted_by_client column exists
    $check_del_client = $pdo->query("SHOW COLUMNS FROM order_messages LIKE 'deleted_by_client'");
    $has_del_client = $check_del_client->fetch();
    $check_del_client->closeCursor();
    if (!$has_del_client) {
        $pdo->exec("ALTER TABLE order_messages ADD COLUMN deleted_by_client TINYINT(1) DEFAULT 0 AFTER file_path");
    }

    // 5. Ensure deleted_by_student column exists
    $check_del_student = $pdo->query("SHOW COLUMNS FROM order_messages LIKE 'deleted_by_student'");
    $has_del_student = $check_del_student->fetch();
    $check_del_student->closeCursor();
    if (!$has_del_student) {
        $pdo->exec("ALTER TABLE order_messages ADD COLUMN deleted_by_student TINYINT(1) DEFAULT 0 AFTER deleted_by_client");
    }
} catch (PDOException $e) {
    die("Database connection failed (PDO): " . $e->getMessage());
}
?>