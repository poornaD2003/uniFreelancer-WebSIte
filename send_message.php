<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$sender_id = intval($_SESSION['user_id']);

// 2. Accept POST request containing order_id and message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $redirect_to = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '';

    // 3. Securely validate message
    if ($order_id <= 0 || $message === '') {
        redirect_back($order_id, $redirect_to, 'empty_message');
    }

    try {
        // Authorization check: Ensure the sender is either the client or the student of this order
        $auth_stmt = $pdo->prepare("SELECT client_id, student_id FROM orders WHERE orderId = ?");
        $auth_stmt->execute([$order_id]);
        $order = $auth_stmt->fetch();

        if (!$order) {
            redirect_back($order_id, $redirect_to, 'order_not_found');
        }

        if ($sender_id !== intval($order['client_id']) && $sender_id !== intval($order['student_id'])) {
            // Unauthorized sender
            redirect_back($order_id, $redirect_to, 'unauthorized');
        }

        // 4. Insert message into order_messages table
        $insert_stmt = $pdo->prepare("INSERT INTO order_messages (order_id, sender_id, message) VALUES (:order_id, :sender_id, :message)");
        $insert_stmt->execute([
            'order_id' => $order_id,
            'sender_id' => $sender_id,
            'message' => $message
        ]);

        // 5. Successful insert: redirect back with active chat thread
        redirect_back($order_id, $redirect_to, 'success');

    } catch (PDOException $e) {
        // Database error
        redirect_back($order_id, $redirect_to, 'error');
    }
} else {
    // Non-POST request redirected to home
    header("Location: index.php");
    exit();
}

/**
 * Safe helper redirect back to client or student dashboard.
 */
function redirect_back($order_id, $redirect_url, $status_code) {
    // Secure Open Redirect Check: Validate redirect destination
    $allowed_urls = ['dashboard.php', 'client-dashboard.php'];
    if (!in_array($redirect_url, $allowed_urls)) {
        $redirect_url = 'index.php';
    }
    
    // Add query params to re-focus status tab and specific chat thread
    $params = [];
    $params[] = "tab=status";
    if ($order_id > 0) {
        $params[] = "order_id=" . $order_id;
    }
    $params[] = "msg_status=" . $status_code;
    
    header("Location: " . $redirect_url . "?" . implode("&", $params) . "#status");
    exit();
}
?>
