<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

include_once __DIR__ . '/includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $file_path = null;

    if ($order_id <= 0 || ($message === '' && empty($_FILES['attachment']['name']))) {
        echo json_encode(['status' => 'error', 'message' => 'Message or attachment is required.']);
        exit();
    }

    if (!empty($_FILES['attachment']['name'])) {
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['attachment']['name']);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $file_path = 'uploads/' . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO order_messages (order_id, sender_id, message, file_path, sent_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iiss", $order_id, $user_id, $message, $file_path);
        if ($stmt->execute()) {
            $msg_id = mysqli_insert_id($conn);
            $response_data = [
                'id' => $msg_id,
                'sender_id' => $user_id,
                'fullname' => $_SESSION['fullname'] ?? 'Me',
                'message' => $message,
                'file_path' => $file_path,
                'sent_at' => date('M d, g:i A')
            ];
            echo json_encode([
                'status' => 'success',
                'data' => $response_data
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: Unable to send message.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database query.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>