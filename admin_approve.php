<?php
include 'includes/db.php';
include 'includes/header.php';

// Handle approval action securely
if (isset($_GET['approve_id'])) {
    $approve_id = (int)$_GET['approve_id'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    if ($stmt->execute([$approve_id])) {
        echo "<div class='success-badge' style='display:block; margin: 1rem 5%;'>User approved successfully!</div>";
    }
}

// Handle rejection/deletion action safely
if (isset($_GET['reject_id'])) {
    $reject_id = (int)$_GET['reject_id'];
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$reject_id])) {
        echo "<div class='error-message' style='display:block; margin: 1rem 5%; background: rgba(239,68,68,0.1); padding:0.5rem 1rem; border-radius:6px;'>Registration request rejected and removed.</div>";
    }
}

// Fetch all users with 'pending' status status
$stmt = $pdo->query("SELECT id, fullname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$pending_users = $stmt->fetchAll();
?>

<div style="padding: 120px 5% 4rem;">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem;">Admin Control Hub</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Review pending user registration approval requests.</p>

    <?php if (empty($pending_users)): ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
            🎉 All caught up! No pending registrations need evaluation right now.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow-x: auto; border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.02);">
                        <th style="padding: 1rem 1.5rem; color: var(--text-muted);">Full Name</th>
                        <th style="padding: 1rem 1.5rem; color: var(--text-muted);">Email Address</th>
                        <th style="padding: 1rem 1.5rem; color: var(--text-muted);">Role Type</th>
                        <th style="padding: 1rem 1.5rem; color: var(--text-muted);">Registered On</th>
                        <th style="padding: 1rem 1.5rem; color: var(--text-muted); text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1.2rem 1.5rem; font-weight: 500; color: #fff;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td style="padding: 1.2rem 1.5rem; color: var(--text-muted);"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td style="padding: 1.2rem 1.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: <?php echo $user['role'] === 'student' ? 'var(--primary)' : '#60a5fa'; ?>;">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td style="padding: 1.2rem 1.5rem; color: var(--text-muted); font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td style="padding: 1.2rem 1.5rem; text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="admin_approve.php?approve_id=<?php echo $user['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; border-radius: 6px;">
                                    Approve
                                </a>
                                <a href="admin_approve.php?reject_id=<?php echo $user['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; border-radius: 6px; color: #f87171; border-color: rgba(239,68,68,0.2);" onclick="return confirm('Are you sure you want to reject this user?');">
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