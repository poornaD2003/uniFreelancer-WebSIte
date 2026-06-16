<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/student.css">
<?php
$gigs_count = $orders_count = 0; $earnings = 0.00; $profile_exists = false;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM gigs WHERE student_id = ?");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); $gigs_count=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close(); }
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE student_id = ?");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); $orders_count=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close(); }
$stmt = $conn->prepare("SELECT id FROM student_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); if ($stmt->get_result()->num_rows>0) $profile_exists=true; $stmt->close(); }
$stmt = $conn->prepare("SELECT SUM(amount) AS total FROM payment WHERE student_id = ? AND payment_status = 'completed'");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); if (!empty($row['total'])) $earnings=(float)$row['total']; $stmt->close(); }
$recent_gigs = [];
$stmt = $conn->prepare("SELECT title,price,status,created_at FROM gigs WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); $res=$stmt->get_result(); while($r=$res->fetch_assoc()) $recent_gigs[]=$r; $stmt->close(); }
$recent_orders = [];
$stmt = $conn->prepare("SELECT o.orderId,o.status,o.created_at,g.title AS gig_title,u.fullname AS client_name FROM orders o JOIN gigs g ON o.gig_id=g.id JOIN users u ON o.client_id=u.id WHERE o.student_id=? ORDER BY o.created_at DESC LIMIT 5");
if ($stmt) { $stmt->bind_param("i",$user_id); $stmt->execute(); $res=$stmt->get_result(); while($r=$res->fetch_assoc()) $recent_orders[]=$r; $stmt->close(); }
?>
<div class="wrap">
    <aside class="sidebar">
        <h2>Student Hub</h2>
        <nav>
            <a href="student-dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="student-profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
            <a href="student-post-job.php"><i class="fas fa-briefcase"></i> Post Gig</a>
            <a href="student-orders.php"><i class="fas fa-shopping-basket"></i> Orders</a>
        </nav>
    </aside>
    <main class="main">
        <h1>Dashboard Overview</h1>
        <div class="cards">
            <div class="card"><strong>Rs. <?php echo number_format($earnings,2); ?></strong><div>Total Earnings</div></div>
            <div class="card"><strong><?php echo $gigs_count; ?></strong><div>Gigs Posted</div></div>
            <div class="card"><strong><?php echo $orders_count; ?></strong><div>Orders Received</div></div>
            <div class="card"><strong><?php echo $profile_exists ? '✓ Active' : '○ Pending'; ?></strong><div>Profile Status</div></div>
        </div>
        <div class="container" style="margin-top:1rem;">
            <div class="section-header"><i class="fas fa-shopping-bag"></i> Recent Orders Received</div>
            <?php if (empty($recent_orders)): ?><p style="color:var(--text-muted)">No orders received yet.</p><?php else: ?>
            <table class="activity-table"><thead><tr><th>Order ID</th><th>Gig Title</th><th>Client</th><th>Date</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recent_orders as $o): ?>
                <tr><td>#<?php echo $o['orderId']; ?></td><td style="color:#fff;font-weight:500"><?php echo htmlspecialchars($o['gig_title']); ?></td><td><?php echo htmlspecialchars($o['client_name']); ?></td><td><?php echo date('M d, Y',strtotime($o['created_at'])); ?></td><td><span class="badge badge-<?php echo str_replace('_','-',$o['status']); ?>"><?php echo str_replace('_',' ',$o['status']); ?></span></td></tr>
            <?php endforeach; ?>
            </tbody></table><?php endif; ?>
        </div>
        <div class="container">
            <div class="section-header"><i class="fas fa-folder-open"></i> Your Recent Gigs</div>
            <?php if (empty($recent_gigs)): ?><p style="color:var(--text-muted)">No gigs posted yet.</p><?php else: ?>
            <table class="activity-table"><thead><tr><th>Gig Title</th><th>Price</th><th>Date Created</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recent_gigs as $gig): ?>
                <tr><td style="color:#fff;font-weight:500"><?php echo htmlspecialchars($gig['title']); ?></td><td>Rs. <?php echo number_format($gig['price'],2); ?></td><td><?php echo date('M d, Y',strtotime($gig['created_at'])); ?></td><td><span class="badge badge-<?php echo $gig['status']; ?>"><?php echo $gig['status']==='approve'?'Approved':'Pending'; ?></span></td></tr>
            <?php endforeach; ?>
            </tbody></table><?php endif; ?>
        </div>
    </main>
</div>
<script src="js/student.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
