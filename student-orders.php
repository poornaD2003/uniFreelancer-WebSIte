<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

$msg = ""; $error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id=(int)$_POST['order_id']; $new_status=$_POST['status']??'';
    $valid=['in_progress','completed','cancelled'];
    if (in_array($new_status,$valid)) {
        $s=$conn->prepare("SELECT o.client_id,o.student_id,g.price FROM orders o JOIN gigs g ON o.gig_id=g.id WHERE o.orderId=? AND o.student_id=?");
        if($s){$s->bind_param("ii",$order_id,$user_id);$s->execute();$od=$s->get_result()->fetch_assoc();$s->close();
            if($od){
                $s=$conn->prepare("UPDATE orders SET status=? WHERE orderId=? AND student_id=?");
                if($s){$s->bind_param("sii",$new_status,$order_id,$user_id);
                    if($s->execute()){
                        $msg="✓ Order #$order_id status updated to ".ucfirst(str_replace('_',' ',$new_status)).".";
                        if($new_status==='completed'){
                            $cid=(int)$od['client_id']; $amt=(float)$od['price'];
                            $pc=$conn->prepare("SELECT paymentId FROM payment WHERE orderId=? LIMIT 1");
                            if($pc){$pc->bind_param("i",$order_id);$pc->execute();$has=$pc->get_result()->num_rows>0;$pc->close();
                                if(!$has){$ps=$conn->prepare("INSERT INTO payment (orderId,client_id,student_id,amount,payment_status) VALUES(?,?,?,?,'completed')");
                                    if($ps){$ps->bind_param("iiid",$order_id,$cid,$user_id,$amt);$ps->execute();$ps->close();$msg.=" Payment of Rs. ".number_format($amt,2)." registered.";}
                                }
                            }
                        }
                    } else { $error_msg="Failed to update order status."; }
                    $s->close();
                }
            } else { $error_msg="Unauthorized or invalid order."; }
        }
    } else { $error_msg="Invalid status request."; }
}

$orders=[];
$s=$conn->prepare("SELECT o.orderId,o.status,o.created_at,g.title AS gig_title,g.price AS gig_price,u.fullname AS client_name FROM orders o JOIN gigs g ON o.gig_id=g.id JOIN users u ON o.client_id=u.id WHERE o.student_id=? ORDER BY o.created_at DESC");
if($s){$s->bind_param("i",$user_id);$s->execute();$res=$s->get_result();while($r=$res->fetch_assoc())$orders[]=$r;$s->close();}
?>
<link rel="stylesheet" href="css/student.css">
<div class="wrap">
    <aside class="sidebar"><h2>Student Hub</h2><nav>
        <a href="student-dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="student-post-job.php"><i class="fas fa-briefcase"></i> Post Gig</a>
        <a href="student-orders.php" class="active"><i class="fas fa-shopping-basket"></i> Orders</a>
    </nav></aside>
    <main class="main">
        <h1>Manage Client Orders</h1>
        <?php if(!empty($msg)): ?><div style="background:rgba(16,185,129,.1);border:1px solid var(--primary);color:var(--primary);padding:1rem;border-radius:8px;"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if(!empty($error_msg)): ?><div style="background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#ef4444;padding:1rem;border-radius:8px;"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
        <div class="container">
            <div class="section-header"><i class="fas fa-list"></i> Order Activity Queue</div>
            <div class="orders-list">
                <?php if(empty($orders)): ?><p style="color:var(--text-muted);text-align:center;padding:2rem 0;">📭 No orders received yet.</p>
                <?php else: foreach($orders as $o): ?>
                    <div class="order">
                        <div class="order-header">
                            <div>
                                <div class="order-title"><?php echo htmlspecialchars($o['gig_title']); ?></div>
                                <div class="order-meta" style="margin-top:5px;">
                                    <strong>Client:</strong> <?php echo htmlspecialchars($o['client_name']); ?> &nbsp;|&nbsp;
                                    <strong>Order ID:</strong> #<?php echo $o['orderId']; ?> &nbsp;|&nbsp;
                                    <strong>Value:</strong> Rs. <?php echo number_format($o['gig_price'],2); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo str_replace('_','-',$o['status']); ?>"><?php echo ucfirst(str_replace('_',' ',$o['status'])); ?></span>
                        </div>
                        <div class="order-desc" style="font-size:.85rem;color:var(--text-muted);"><strong>Received On:</strong> <?php echo date('M d, Y, h:i A',strtotime($o['created_at'])); ?></div>
                        <div class="actions">
                            <?php if($o['status']==='pending'): ?>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;"><input type="hidden" name="order_id" value="<?php echo $o['orderId']; ?>"><input type="hidden" name="status" value="in_progress"><button type="submit" name="update_status" class="btn-small" style="background:var(--primary);border:none;color:#fff;cursor:pointer;margin:0;">Accept Order</button></form>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;" onsubmit="return confirm('Decline this order?');"><input type="hidden" name="order_id" value="<?php echo $o['orderId']; ?>"><input type="hidden" name="status" value="cancelled"><button type="submit" name="update_status" class="btn-small" style="background:transparent;border:1px solid #ef4444;color:#ef4444;cursor:pointer;margin:0;">Decline</button></form>
                            <?php elseif($o['status']==='in_progress'): ?>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;" onsubmit="return confirm('Mark as delivered?');"><input type="hidden" name="order_id" value="<?php echo $o['orderId']; ?>"><input type="hidden" name="status" value="completed"><button type="submit" name="update_status" class="btn-small" style="background:var(--primary);border:none;color:#fff;cursor:pointer;margin:0;">Deliver &amp; Complete</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="js/student.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>