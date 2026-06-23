<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Ensure only logged-in clubs can access this dashboard
if (!isset($_SESSION['club_id']) || $_SESSION['role'] !== 'club') {
    header("Location: login_club.php");
    exit();
}

$club_id = (int)$_SESSION['club_id'];
include 'includes/db.php';

$msg = "";
$error_msg = "";

// 0. Handle Member Removal Action (Clear club_id for the student)
if (isset($_GET['remove_student_id'])) {
    $remove_student_id = (int)$_GET['remove_student_id'];
    $remove_stmt = $conn->prepare("UPDATE student_profiles SET club_id = NULL, club_affiliations = '' WHERE user_id = ? AND club_id = ?");
    if ($remove_stmt) {
        $remove_stmt->bind_param("ii", $remove_student_id, $club_id);
        if ($remove_stmt->execute()) {
            $msg = "Member successfully removed from your club.";
        } else {
            $error_msg = "Failed to remove member. Please try again.";
        }
        $remove_stmt->close();
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/student.css">

<?php
// 1. Fetch club details
$club_stmt = $conn->prepare("SELECT club_name, club_code, contribution_rate, description FROM clubs WHERE id = ? LIMIT 1");
$club_name = "";
$club_code = "";
$contribution_rate = 0.00;
$description = "";

if ($club_stmt) {
    $club_stmt->bind_param("i", $club_id);
    $club_stmt->execute();
    $club_res = $club_stmt->get_result()->fetch_assoc();
    if ($club_res) {
        $club_name = $club_res['club_name'];
        $club_code = $club_res['club_code'];
        $contribution_rate = (float)$club_res['contribution_rate'];
        $description = $club_res['description'];
    }
    $club_stmt->close();
}

// 2. Fetch metrics
$balance = 0.00;
$contributions_count = 0;

$balance_stmt = $conn->prepare("SELECT SUM(amount) AS total FROM club_ledger WHERE club_id = ?");
if ($balance_stmt) {
    $balance_stmt->bind_param("i", $club_id);
    $balance_stmt->execute();
    $balance_row = $balance_stmt->get_result()->fetch_assoc();
    if (!empty($balance_row['total'])) {
        $balance = (float)$balance_row['total'];
    }
    $balance_stmt->close();
}

$count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM club_ledger WHERE club_id = ?");
if ($count_stmt) {
    $count_stmt->bind_param("i", $club_id);
    $count_stmt->execute();
    $count_row = $count_stmt->get_result()->fetch_assoc();
    if ($count_row) {
        $contributions_count = (int)$count_row['c'];
    }
    $count_stmt->close();
}

// 3. Fetch transaction log
$transactions = [];
$log_stmt = $conn->prepare("
    SELECT cl.id, cl.amount, cl.description, cl.created_at, u.fullname AS student_name
    FROM club_ledger cl
    LEFT JOIN payment p ON cl.payment_id = p.paymentId
    LEFT JOIN users u ON p.student_id = u.id
    WHERE cl.club_id = ?
    ORDER BY cl.created_at DESC
");
if ($log_stmt) {
    $log_stmt->bind_param("i", $club_id);
    $log_stmt->execute();
    $res = $log_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $transactions[] = $r;
    }
    $log_stmt->close();
}

// 4. Fetch club members list
$members = [];
$members_stmt = $conn->prepare("
    SELECT u.id, u.fullname, u.email, sp.university_name, sp.faculty, sp.department 
    FROM student_profiles sp
    JOIN users u ON sp.user_id = u.id
    WHERE sp.club_id = ?
    ORDER BY u.fullname ASC
");
if ($members_stmt) {
    $members_stmt->bind_param("i", $club_id);
    $members_stmt->execute();
    $res = $members_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $members[] = $r;
    }
    $members_stmt->close();
}
?>

<div class="wrap" style="background: #1a202c; color: white; min-height: 90vh;">
    <aside class="sidebar" style="background: #111827; padding-top: 100px;">
        <h2>Club Panel</h2>
        <nav>
            <a href="club_dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main" style="padding-top: 100px;">
        <h1>Club Dashboard Overview</h1>
        <p style="color: #a0aec0; margin-bottom: 2rem;">Logged in as: <strong><?php echo htmlspecialchars($club_name); ?></strong></p>
        
        <?php if(!empty($msg)): ?>
            <div style='display:block; margin: 1rem 0; background: rgba(46, 204, 113, 0.15); color: #2ecc71; padding: 0.75rem 1rem; border-radius: 6px; font-weight: bold; border: 1px solid rgba(46,204,113,0.3);'>
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div style='display:block; margin: 1rem 0; background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 0.75rem 1rem; border-radius: 6px; font-weight: bold; border: 1px solid rgba(239,68,68,0.3);'>
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>
        
        <div class="cards">
            <div class="card" style="border-left: 4px solid #7c3aed;">
                <strong>Rs. <?php echo number_format($balance, 2); ?></strong>
                <div>Total Club Balance</div>
            </div>
            <div class="card">
                <strong><?php echo $contributions_count; ?></strong>
                <div>Contributions Received</div>
            </div>
            <div class="card">
                <strong><?php echo number_format($contribution_rate, 2); ?>%</strong>
                <div>Contribution Share Rate</div>
            </div>
            <div class="card">
                <strong style="font-family: monospace; letter-spacing: 1px; color: #60a5fa;"><?php echo htmlspecialchars($club_code); ?></strong>
                <div>Club Registration Code</div>
            </div>
        </div>

        <div class="container" style="margin-top: 2rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(0,0,0,0.05); padding: 2rem; border-radius: 12px;">
            <div class="section-header" style="color: black; border-bottom: 1px solid rgba(0,0,0,0.1);"><i class="fas fa-receipt" style="color:#7c3aed;"></i> Club Contribution Ledger</div>
            
            <?php if (empty($transactions)): ?>
                <p style="color: #4a5568; margin-top: 1rem;">No contributions recorded in the ledger yet.</p>
            <?php else: ?>
                <table class="activity-table" style="color: black; margin-top: 1rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,0,0,0.1);">
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">Student Member</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">Description</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">Contribution Date</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1); text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                <td style="padding: 1rem 0; font-weight: 600; color: black;">
                                    <?php echo htmlspecialchars($t['student_name'] ?? 'Unknown Member'); ?>
                                </td>
                                <td style="padding: 1rem 0; color: black;">
                                    <?php echo htmlspecialchars($t['description']); ?>
                                </td>
                                <td style="padding: 1rem 0; color: black;">
                                    <?php echo date('M d, Y g:i A', strtotime($t['created_at'])); ?>
                                </td>
                                <td style="padding: 1rem 0; text-align: right; color: #2ecc71; font-weight: bold;">
                                    Rs. <?php echo number_format($t['amount'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="container" style="margin-top: 2rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(0,0,0,0.05); padding: 2rem; border-radius: 12px; margin-bottom: 3rem;">
            <div class="section-header" style="color: black; border-bottom: 1px solid rgba(0,0,0,0.1);"><i class="fas fa-users" style="color:#7c3aed;"></i> Club Members</div>
            
            <?php if (empty($members)): ?>
                <p style="color: #4a5568; margin-top: 1rem;">No student members have joined your club yet.</p>
            <?php else: ?>
                <table class="activity-table" style="color: black; margin-top: 1rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(0,0,0,0.1);">
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">Full Name</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">Email Address</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1);">University / Faculty / Department</th>
                            <th style="color: black; background: transparent; border-bottom: 2px solid rgba(0,0,0,0.1); text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                <td style="padding: 1rem 0; font-weight: 600; color: black;">
                                    <?php echo htmlspecialchars($m['fullname']); ?>
                                </td>
                                <td style="padding: 1rem 0; color: black;">
                                    <?php echo htmlspecialchars($m['email']); ?>
                                </td>
                                <td style="padding: 1rem 0; color: black; font-size: 0.88rem;">
                                    <strong><?php echo htmlspecialchars($m['university_name']); ?></strong><br>
                                    <span style="color: #4a5568;"><?php echo htmlspecialchars($m['faculty']); ?> &mdash; <?php echo htmlspecialchars($m['department']); ?></span>
                                </td>
                                <td style="padding: 1rem 0; text-align: right;">
                                    <a href="club_dashboard.php?remove_student_id=<?php echo $m['id']; ?>" class="btn btn-outline" style="text-decoration: none; padding: 0.4rem 0.8rem; font-size: 0.82rem; border-radius: 6px; color: #f87171; border: 1px solid rgba(239,68,68,0.2); display: inline-block;" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($m['fullname']); ?> from your club? This will clear their club association.');">
                                        <i class="fas fa-user-minus"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
