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

$current_user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

if ($message_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid message identifier.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT om.sender_id, om.order_id, o.client_id, o.student_id 
        FROM order_messages om 
        JOIN orders o ON om.order_id = o.orderId 
        WHERE om.id = ?
    ");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();

    if (!$msg) {
        echo json_encode(['status' => 'error', 'message' => 'Message not found.']);
        exit();
    }

    $client_id = intval($msg['client_id']);
    $student_id = intval($msg['student_id']);
    $sender_id = intval($msg['sender_id']);

    if ($current_user_id !== $client_id && $current_user_id !== $student_id) {
        echo json_encode(['status' => 'error', 'message' => 'You are not authorized to access this message.']);
        exit();
    }

    if ($current_user_id === $sender_id) {
        $delete_stmt = $pdo->prepare("DELETE FROM order_messages WHERE id = ?");
        $delete_stmt->execute([$message_id]);
        echo json_encode(['status' => 'success', 'message' => 'Message globally deleted.']);
    } else {
        if ($current_user_id === $client_id) {
            $update_stmt = $pdo->prepare("UPDATE order_messages SET deleted_by_client = 1 WHERE id = ?");
            $update_stmt->execute([$message_id]);
        } else if ($current_user_id === $student_id) {
            $update_stmt = $pdo->prepare("UPDATE order_messages SET deleted_by_student = 1 WHERE id = ?");
            $update_stmt->execute([$message_id]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Message hidden for your view.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
