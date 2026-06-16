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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Extract inputs
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order identifier.']);
    exit();
}

try {
    // Authorization check: Ensure the user is either the client or the student of this order
    $auth_stmt = $pdo->prepare("SELECT client_id, student_id FROM orders WHERE orderId = ?");
    $auth_stmt->execute([$order_id]);
    $order = $auth_stmt->fetch();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit();
    }

    if ($current_user_id !== intval($order['client_id']) && $current_user_id !== intval($order['student_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You are not authorized to view messages in this order.']);
        exit();
    }

    $is_client = ($current_user_id === intval($order['client_id'])) ? 1 : 0;
    $is_student = ($current_user_id === intval($order['student_id'])) ? 1 : 0;

    // 2. Query for all visible messages
    $query_stmt = $pdo->prepare("
        SELECT om.id, om.sender_id, om.message, om.file_path, om.sent_at, u.fullname 
        FROM order_messages om 
        JOIN users u ON om.sender_id = u.id 
        WHERE om.order_id = ? 
          AND (
            (? = 1 AND om.deleted_by_client = 0) OR
            (? = 1 AND om.deleted_by_student = 0)
          )
        ORDER BY om.sent_at ASC, om.id ASC
    ");
    $query_stmt->execute([$order_id, $is_client, $is_student]);
    $messages = $query_stmt->fetchAll();

    $formatted_messages = [];
    foreach ($messages as $msg) {
        $formatted_messages[] = [
            'id' => intval($msg['id']),
            'sender_id' => intval($msg['sender_id']),
            'fullname' => $msg['fullname'],
            'message' => $msg['message'] ?? '',
            'file_path' => $msg['file_path'],
            'sent_at' => date('M d, g:i A', strtotime($msg['sent_at']))
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_messages
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
