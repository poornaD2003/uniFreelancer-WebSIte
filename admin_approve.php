<?php
// 1. Session start කිරීම (Admin ලොග් වී ඇත්දැයි බැලීමට අවශ්‍ය නිසා)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. MySQLi connection එක ($conn) සහිත db.php ඇතුළත් කිරීම
include 'includes/db.php';
include 'includes/header.php';

// 3. Admin කෙනෙක්ද කියා ආරක්ෂාව පරීක්ෂාව (Admin panel එකක් නිසා role එක admin විය යුතුයි)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";
$error_msg = "";

// 4. Secure Approval Action (MySQLi Prepared Statements)
if (isset($_GET['approve_id'])) {
    $approve_id = (int)$_GET['approve_id'];
    
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $approve_id);
    
    if ($stmt->execute()) {
        $msg = "User approved successfully!";
    }
    $stmt->close();
}

// 5. Secure Rejection/Deletion Action (MySQLi Prepared Statements)
if (isset($_GET['reject_id'])) {
    $reject_id = (int)$_GET['reject_id'];
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $reject_id);
    
    if ($stmt->execute()) {
        $error_msg = "Registration request rejected and removed.";
    }
    $stmt->close();
}

// 6. Pending Users Fetch කිරීම (MySQLi)
$query = "SELECT id, fullname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($query);
$pending_users = $result->fetch_all(MYSQLI_ASSOC);
?>

<div style="padding: 120px 5% 4rem; font-family: sans-serif; background: #1a202c; color: white; min-height: 80vh;">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem; color: #fff;">Admin Control Hub</h2>
    <p style="color: #a0aec0; margin-bottom: 2rem;">Review pending user registration approval requests.</p>

    <?php if(!empty($msg)): ?>
        <div class='success-badge' style='display:block; margin: 1rem 0; background: rgba(46, 204, 113, 0.2); color: #2ecc71; padding: 0.75rem 1rem; border-radius: 6px; font-weight: bold;'>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div class='error-message' style='display:block; margin: 1rem 0; background: rgba(239,68,68,0.1); color: #f87171; padding:0.75rem 1rem; border-radius:6px; font-weight: bold;'>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending_users)): ?>
        <div class="card" style="text-align: center; color: #a0aec0; padding: 3rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
            🎉 All caught up! No pending registrations need evaluation right now.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow-x: auto; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                        <th style="padding: 1rem 1.5rem; color: #a0aec0;">Full Name</th>
                        <th style="padding: 1rem 1.5rem; color: #a0aec0;">Email Address</th>
                        <th style="padding: 1rem 1.5rem; color: #a0aec0;">Role Type</th>
                        <th style="padding: 1rem 1.5rem; color: #a0aec0;">Registered On</th>
                        <th style="padding: 1rem 1.5rem; color: #a0aec0; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1.2rem 1.5rem; font-weight: 500; color: #fff;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td style="padding: 1.2rem 1.5rem; color: #a0aec0;"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td style="padding: 1.2rem 1.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: <?php echo $user['role'] === 'student' ? '#2ecc71' : '#60a5fa'; ?>;">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td style="padding: 1.2rem 1.5rem; color: #a0aec0; font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td style="padding: 1.2rem 1.5rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="admin_approve.php?approve_id=<?php echo $user['id']; ?>" class="btn btn-primary" style="background: #2ecc71; color: white; text-decoration: none; padding: 0.4rem 0.9rem; font-size: 0.85rem; border-radius: 6px; display: inline-block;">
                                    Approve
                                </a>
                                <a href="admin_approve.php?reject_id=<?php echo $user['id']; ?>" class="btn btn-outline" style="text-decoration: none; padding: 0.4rem 0.9rem; font-size: 0.85rem; border-radius: 6px; color: #f87171; border: 1px solid rgba(239,68,68,0.2); display: inline-block;" onclick="return confirm('Are you sure you want to reject this user?');">
                                    Reject
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>