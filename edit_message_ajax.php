<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$new_message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($message_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid message identifier.']);
    exit();
}

if ($new_message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT sender_id FROM order_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();

    if (!$msg) {
        echo json_encode(['status' => 'error', 'message' => 'Message not found.']);
        exit();
    }

    if (intval($msg['sender_id']) !== $current_user_id) {
        echo json_encode(['status' => 'error', 'message' => 'You are not authorized to edit this message.']);
        exit();
    }

    $update_stmt = $pdo->prepare("UPDATE order_messages SET message = ? WHERE id = ?");
    $update_stmt->execute([$new_message, $message_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Message updated successfully.',
        'data' => [
            'id' => $message_id,
            'message' => $new_message
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
