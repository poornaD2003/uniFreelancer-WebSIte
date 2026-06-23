<?php
include 'includes/db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

// Fetch payment transactions for the client
$payments = [];
$query = "
    SELECT p.paymentId, p.amount, p.payment_status, p.payment_date, g.title AS gig_title, u.fullname AS student_name 
    FROM payment p 
    JOIN orders o ON p.orderId = o.orderId 
    JOIN gigs g ON o.gig_id = g.id 
    JOIN users u ON p.student_id = u.id 
    WHERE p.client_id = ? 
    ORDER BY p.payment_date DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/client_dashboard.css">

<style>
    .billing-table-card {
        background-color: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
    }
    .billing-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .billing-table th {
        font-size: 0.85rem;
        color: var(--muted);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 2px solid var(--border);
    }
    .billing-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
        color: var(--text);
    }
    .billing-table tr:hover {
        background-color: var(--bg2);
    }
</style>

<div class="dashboard-wrapper">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <i class="ti ti-activity" style="font-size: 1.5rem;"></i> Client Analytics
    </div>
    <ul class="sidebar-menu">
      <li class="sidebar-item">
        <a href="client-dashboard.php"><i class="ti ti-smart-home"></i> Pipeline Hub</a>
      </li>
      <li class="sidebar-item">
        <a href="jobs.php"><i class="ti ti-square-plus"></i> Launch Order</a>
      </li>
      <li class="sidebar-item">
        <a href="post-job.php"><i class="ti ti-circle-plus"></i> Post a Job</a>
      </li>
      <li class="sidebar-item">
        <a href="client-postings.php"><i class="ti ti-clipboard-list"></i> My Job Postings</a>
      </li>
      <li class="sidebar-item active">
        <a href="client-payments.php"><i class="ti ti-receipt"></i> Billing & Payments</a>
      </li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="header-section" style="margin-bottom: 2rem;">
      <div>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 6px; color: var(--text);">Billing & Payments</h1>
        <p style="color: var(--muted); font-size: 0.9rem; font-weight: 500;">Monitor your transactional history ledger, invoices, and bank deposits.</p>
      </div>
    </div>

    <div class="billing-table-card">
      <div class="section-title" style="margin-bottom: 1.5rem;">
        <i class="ti ti-receipt" style="color: var(--green); font-size: 1.3rem;"></i> Payment Protection Ledger
      </div>

      <?php if (empty($payments)): ?>
          <div style="text-align: center; padding: 3rem 0; color: var(--muted); font-weight: 500;">
              <i class="ti ti-receipt-off" style="font-size: 2.5rem; color: var(--green); display: block; margin-bottom: 10px;"></i>
              No billing transaction logs found on this account node.
          </div>
      <?php else: ?>
          <div style="overflow-x: auto;">
              <table class="billing-table">
                  <thead>
                      <tr>
                          <th>Transaction ID</th>
                          <th>Service/Gig Purchased</th>
                          <th>Assigned Developer</th>
                          <th>Payment Date</th>
                          <th>Amount (LKR)</th>
                          <th>Status</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach ($payments as $p): 
                          $badge = ($p['payment_status'] === 'completed') ? 'badge-completed' : 'badge-pending';
                          $status_txt = ($p['payment_status'] === 'completed') ? 'Completed' : 'Awaiting Settlement';
                      ?>
                          <tr>
                              <td style="font-weight: 600; color: var(--muted);">#TXN-<?php echo htmlspecialchars($p['paymentId']); ?></td>
                              <td style="font-weight: 600; color: var(--text);"><?php echo htmlspecialchars($p['gig_title']); ?></td>
                              <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                              <td><?php echo date('M d, Y, h:i A', strtotime($p['payment_date'])); ?></td>
                              <td style="font-weight: 700; color: var(--text);">Rs. <?php echo number_format($p['amount'], 2); ?></td>
                              <td><span class="badge <?php echo $badge; ?>"><?php echo $status_txt; ?></span></td>
                          </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
