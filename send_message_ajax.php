<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login.']);
    exit();
}

include 'includes/db.php';

$sender_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Extract inputs
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$file_path = null;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order identifier.']);
    exit();
}

try {
    // Authorization check: Ensure the sender is part of this order (client or student)
    $auth_stmt = $pdo->prepare("SELECT client_id, student_id FROM orders WHERE orderId = ?");
    $auth_stmt->execute([$order_id]);
    $order = $auth_stmt->fetch();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit();
    }

    if ($sender_id !== intval($order['client_id']) && $sender_id !== intval($order['student_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You are not authorized to send messages in this order.']);
        exit();
    }

    // 2. Handle File Attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];

        // Validate upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'File upload encountered an error (Code ' . $file['error'] . ').']);
            exit();
        }

        // Validate file size (e.g., limit to 20MB)
        $max_size = 20 * 1024 * 1024; // 20 megabytes
        if ($file['size'] > $max_size) {
            echo json_encode(['status' => 'error', 'message' => 'File size exceeds maximum limit of 20MB.']);
            exit();
        }

        // Validate file extension
        $allowed_extensions = ['pdf', 'docx', 'zip', 'rar', 'png', 'jpg', 'jpeg'];
        $original_name = $file['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_extensions)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)]);
            exit();
        }

        // Ensure target directory exists
        $upload_dir = 'uploads/chat/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique, randomized filename
        $safe_filename = uniqid('msg_', true) . '.' . $ext;
        $destination = $upload_dir . $safe_filename;

        // Move file from temporary storage to target folder
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $file_path = $destination;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file attachment.']);
            exit();
        }
    }

    // Validate that there is either text or a file
    if ($message === '' && $file_path === null) {
        echo json_encode(['status' => 'error', 'message' => 'Message or file attachment is required.']);
        exit();
    }

    // 3. Write message record to database using PDO Prepared Statement
    $insert_stmt = $pdo->prepare("INSERT INTO order_messages (order_id, sender_id, message, file_path) VALUES (:order_id, :sender_id, :message, :file_path)");
    $insert_stmt->execute([
        'order_id' => $order_id,
        'sender_id' => $sender_id,
        'message' => $message !== '' ? $message : null,
        'file_path' => $file_path
    ]);

    $message_id = $pdo->lastInsertId();

    // Fetch sender's name to return in JSON
    $name_stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
    $name_stmt->execute([$sender_id]);
    $sender_name = $name_stmt->fetchColumn() ?: 'User';

    // Format timestamp nicely for UI: "Jun 16, 12:45 PM"
    $sent_at_formatted = date('M d, g:i A');

    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully.',
        'data' => [
            'id' => $message_id,
            'sender_id' => $sender_id,
            'fullname' => $sender_name,
            'message' => $message,
            'file_path' => $file_path,
            'sent_at' => $sent_at_formatted
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
