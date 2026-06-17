<?php
include 'includes/db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Testing purposes
}
$client_id = $_SESSION['user_id'];

$user_query = $conn->query("SELECT fullname FROM users WHERE id = '$client_id'");
if ($user_query && $user_query->num_rows > 0) {
    $user_data = $user_query->fetch_assoc();
    $customer_name = $user_data['fullname'];
} else {
    $customer_name = "Guest User";
}

$words = explode(" ", $customer_name);
$initials = "";
foreach ($words as $w) {
    $initials .= strtoupper($w[0]);
}
$initials = substr($initials, 0, 2); 

$total_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id'");
$total_orders = $total_orders_res ? $total_orders_res->fetch_assoc()['total'] : 0;

$pending_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id' AND status = 'pending'");
$pending_orders = $pending_orders_res ? $pending_orders_res->fetch_assoc()['total'] : 0;

$completed_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id' AND status = 'completed'");
$completed_orders = $completed_orders_res ? $completed_orders_res->fetch_assoc()['total'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order_action'])) {
    $gig_id = intval($_POST['gig_id']);
    $requirements = $conn->real_escape_string($_POST['requirements']);

    $gig_check = $conn->query("SELECT student_id FROM gigs WHERE id = '$gig_id'");
    if ($gig_check && $gig_check->num_rows > 0) {
        $student_id = $gig_check->fetch_assoc()['student_id'];

        $insert_query = "INSERT INTO orders (client_id, student_id, gig_id, status) VALUES ('$client_id', '$student_id', '$gig_id', 'pending')";
        if ($conn->query($insert_query)) {
            header("Location: client_dashboard.php?success=1#status");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniLance Client Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --color-background-primary: #f0fdf8; /* Light cool mint tint */
            --color-background-secondary: #ffffff; /* Pure white surface */
            --color-sidebar-bg: #f8fafc; /* Very light slate-gray */
            --color-border-tertiary: rgba(16, 185, 129, 0.14); /* Soft emerald/mint border */
            --color-text-primary: #0f172a; /* Dark slate text */
            --color-text-secondary: #64748b; /* Muted slate text */
            --border-radius-md: 10px;
            --border-radius-lg: 18px;
            --shadow-sm: 0 4px 20px rgba(16, 185, 129, 0.05);
            --shadow-lg: 0 16px 48px rgba(16, 185, 129, 0.10);
            --color-accent: #1D9E75;
        }
        
        body { 
            background: var(--color-background-primary); 
            color: var(--color-text-primary); 
            font-family: 'Outfit', sans-serif; 
            padding: 30px 20px; 
        }
        
        .main-container {
            display: flex; 
            border: 1px solid var(--color-border-tertiary); 
            border-radius: var(--border-radius-lg); 
            overflow: hidden; 
            min-height: 680px;
            background: var(--color-background-secondary);
            box-shadow: var(--shadow-lg);
        }

        .sidebar {
            width: 260px; 
            min-height: 600px;
            background: var(--color-sidebar-bg);
            border-right: 1px solid var(--color-border-tertiary);
            padding: 1.75rem 0;
            display: flex; 
            flex-direction: column; 
            gap: 6px;
        }
        
        .nav-item {
            display: flex; 
            align-items: center; 
            gap: 12px;
            padding: 14px 24px;
            font-size: 14.5px; 
            color: var(--color-text-secondary);
            cursor: pointer; 
            border: none; 
            background: none;
            width: 100%; 
            text-align: left; 
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
            font-weight: 500;
        }
        
        .nav-item:hover {
            background: rgba(16, 185, 129, 0.04);
            color: var(--color-text-primary);
        }

        .nav-item.active {
            background: var(--color-background-primary);
            color: var(--color-accent);
            font-weight: 600;
            border-left: 4px solid var(--color-accent);
            padding-left: 20px;
        }
        
        .nav-item i { font-size: 19px; }
        
        .metric-card {
            background: var(--color-background-secondary);
            border-radius: var(--border-radius-md);
            padding: 1.25rem 1.5rem;
            flex: 1; 
            min-width: 220px;
            border: 1px solid var(--color-border-tertiary);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.08);
        }
        
        .metric-label { 
            font-size: 13px; 
            color: var(--color-text-secondary); 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-weight: 500;
        }
        
        .metric-value { font-size: 26px; font-weight: 700; color: var(--color-text-primary); }
        
        .metric-sub { font-size: 11px; color: var(--color-text-secondary); margin-top: 4px; }
        
        .badge { font-size: 11px; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-pending { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .badge-completed { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        
        .section { display: none; }
        .section.visible { display: block; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-btn {
            font-size: 13px; 
            padding: 8px 16px;
            border: 1px solid var(--color-border-tertiary);
            background: var(--color-background-secondary);
            border-radius: var(--border-radius-md);
            color: var(--color-text-secondary); 
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-family: inherit;
        }

        .filter-btn:hover {
            border-color: var(--color-accent);
            color: var(--color-accent);
        }
        
        .filter-btn.active-filter { 
            background: var(--color-accent); 
            color: #fff; 
            border-color: var(--color-accent); 
            box-shadow: 0 4px 14px rgba(29, 158, 117, 0.25);
        }
        
        .gig-card {
            border: 1px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-lg);
            background: var(--color-background-secondary);
            padding: 1.5rem;
            display: flex; 
            flex-direction: column; 
            gap: 12px;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .gig-card:hover {
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.08);
        }
        
        textarea { 
            width: 100%; 
            height: 100px; 
            padding: 12px; 
            background: #f8fafc; 
            border: 1px solid var(--color-border-tertiary); 
            color: var(--color-text-primary); 
            border-radius: var(--border-radius-md); 
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        textarea:focus {
            border-color: var(--color-accent);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
        }
    </style>

    <script>
        function switchTab(name, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('visible'));
            var target = document.getElementById(name);
            if (target) {
                target.classList.add('visible');
            }
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else {
                var activeBtn = document.querySelector(`.nav-item[onclick*="${name}"]`);
                if (activeBtn) activeBtn.classList.add('active');
            }
            if (history.pushState) {
                history.pushState(null, null, '#' + name);
            } else {
                location.hash = name;
            }
        }

        function handleRouting() {
            var tab = window.location.hash.substring(1);
            var match = window.location.search.match(/[?&]tab=([^&]+)/);
            var tabParam = match ? match[1] : null;
            
            var successMatch = window.location.search.match(/[?&]success=([^&]+)/);
            var successParam = successMatch ? successMatch[1] : null;

            if (!tab && tabParam) tab = tabParam;
            if (!tab && successParam) tab = 'status';

            var allowedTabs = ['dashboard', 'order', 'status'];
            if (tab && allowedTabs.indexOf(tab) !== -1) {
                switchTab(tab);
            }
        }

        window.addEventListener('DOMContentLoaded', handleRouting);
        window.addEventListener('hashchange', handleRouting);

        function showOrderForm(id, gig, student, price) {
            document.getElementById('form-gig-id').value = id;
            document.getElementById('form-gig-title').textContent = gig;
            document.getElementById('form-student-name').textContent = student;
            document.getElementById('form-gig-price').textContent = price;
            document.getElementById('order-form-container').style.display = 'block';
            document.getElementById('order-form-container').scrollIntoView({behavior:'smooth'});
        }

        function filterOrders(type, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active-filter'));
            btn.classList.add('active-filter');

            document.querySelectorAll('.order-row').forEach(row => {
                if (type === 'all' || row.dataset.status === type) {
                    row.style.display = 'block';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</head>
<body>

<?php if (isset($_GET['success'])): ?>
    <div id="success-toast" style="position: fixed; top: 30px; right: 20px; background: rgba(29, 158, 117, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: white; padding: 14px 24px; border-radius: var(--border-radius-md); box-shadow: 0 8px 32px 0 rgba(16,185,129,0.2); border: 1px solid rgba(255,255,255,0.1); z-index: 1000; display: flex; align-items: center; gap: 12px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-20px); opacity: 0;">
        <i class="ti ti-circle-check" style="font-size: 20px;"></i>
        <span style="font-size: 14px; font-weight: 500;">Order placed successfully!</span>
        <button onclick="dismissToast()" style="background: none; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 18px; margin-left: 8px; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; transition: color 0.2s;">&times;</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('success-toast');
            if (toast) {
                toast.offsetHeight;
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
                setTimeout(function() { dismissToast(); }, 5000);
            }
        });
        function dismissToast() {
            var toast = document.getElementById('success-toast');
            if (toast) {
                toast.style.transform = 'translateY(-20px)';
                toast.style.opacity = '0';
                setTimeout(function() { toast.remove(); }, 400);
            }
        }
    </script>
<?php endif; ?>

<div class="main-container">
  
  <div class="sidebar">
    <div style="padding: 0 24px 1.25rem; border-bottom: 1px solid var(--color-border-tertiary); margin-bottom: 0.75rem;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--color-accent);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:white;box-shadow:0 4px 12px rgba(29,158,117,0.2);"><?php echo $initials; ?></div>
        <div>
            <div style="font-size:11.5px;color:var(--color-text-secondary);font-weight: 500;">Customer Portal</div>
        </div>
      </div>
    </div>
    <button class="nav-item active" onclick="switchTab('dashboard',this)"><i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard</button>
    <button class="nav-item" onclick="switchTab('order',this)"><i class="ti ti-shopping-cart" aria-hidden="true"></i> Place Order</button>
    <button class="nav-item" onclick="switchTab('status',this)"><i class="ti ti-list-check" aria-hidden="true"></i> Order Status</button>
  </div>

  <div style="flex:1; padding: 2rem; overflow:auto;">

    <div id="dashboard" class="section visible">
      <div style="margin-bottom:1.5rem;">
        <div style="font-size:22px;font-weight:700;letter-spacing:-0.02em;">Welcome, <?php echo $customer_name; ?> 👋</div>
        <div style="font-size:13.5px;color:var(--color-text-secondary);margin-top:4px;font-weight:500;">Here's an overview of your workspace activities</div>
      </div>

      <div style="display:flex;gap:16px;margin-bottom:2rem;flex-wrap:wrap;">
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-shopping-bag" style="color: var(--color-accent)"></i> Total orders</div>
          <div class="metric-value"><?php echo $total_orders; ?></div>
          <div class="metric-sub">All time activity</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-clock" style="color:#f59e0b;"></i> Pending</div>
          <div class="metric-value" style="color:#d97706;"><?php echo $pending_orders; ?></div>
          <div class="metric-sub">Awaiting delivery execution</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-circle-check" style="color:#10b981;"></i> Completed</div>
          <div class="metric-value" style="color:#10b981;"><?php echo $completed_orders; ?></div>
          <div class="metric-sub">Successfully deployed</div>
        </div>
      </div>

      <div style="background:var(--color-background-secondary);border-radius:var(--border-radius-lg);padding:1.25rem 1.5rem; border: 1px solid var(--color-border-tertiary); box-shadow: var(--shadow-sm);">
        <div style="font-size:15px;font-weight:600;margin-bottom:14px;color:var(--color-text-primary);">Recent Pipeline Entries</div>
        <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
          <thead>
            <tr style="color:var(--color-text-secondary); text-align: left; font-weight: 600;">
              <th style="padding:10px 0;">Job Architecture</th>
              <th style="padding:10px 0;">Student Freelancer</th>
              <th style="padding:10px 0;">Status Badge</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $recent_query = $conn->query("SELECT o.status, g.title, u.fullname as student_name FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.student_id = u.id WHERE o.client_id = '$client_id' ORDER BY o.orderId DESC LIMIT 3");
            if ($recent_query && $recent_query->num_rows > 0) {
                while($r = $recent_query->fetch_assoc()) {
                    $badge = ($r['status'] === 'pending') ? 'badge-pending' : 'badge-completed';
                    echo "<tr style='border-top:1px solid var(--color-border-tertiary);'>
                            <td style='padding:14px 0; font-weight:500;'>{$r['title']}</td>
                            <td style='padding:14px 0;color:var(--color-text-secondary);'>{$r['student_name']}</td>
                            <td style='padding:14px 0;'><span class='badge {$badge}'>" . ucfirst($r['status']) . "</span></td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='padding:14px 0;color:var(--color-text-secondary);text-align:center;'>No recent records found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="order" class="section">
      <div style="font-size:22px;font-weight:700;margin-bottom:4px;letter-spacing:-0.02em;">Place an order</div>
      <div style="font-size:13.5px;color:var(--color-text-secondary);margin-bottom:1.5rem;font-weight:500;">Browse student listings and register a project requirement entry</div>

      <div style="display:flex;flex-direction:column;gap:16px;margin-bottom:2rem;">
        <?php
        $gigs_query = $conn->query("SELECT g.id, g.title, g.description, g.price, u.fullname as student_name FROM gigs g JOIN users u ON g.student_id = u.id");
        if ($gigs_query && $gigs_query->num_rows > 0) {
            while($gig = $gigs_query->fetch_assoc()) {
                echo "<div class='gig-card'>
                        <div style='display:flex;justify-content:space-between;align-items:flex-start;'>
                            <div>
                                <div style='font-size:16px;font-weight:600;color:var(--color-text-primary);'>{$gig['title']}</div>
                                <div style='font-size:12.5px;color:var(--color-text-secondary);margin-top:2px;font-weight:500;'>by {$gig['student_name']}</div>
                            </div>
                            <div style='text-align:right;'>
                                <div style='font-size:16px;font-weight:700;color:var(--color-accent);'>Rs. " . number_format($gig['price']) . "</div>
                            </div>
                        </div>
                        <div style='font-size:13px;color:var(--color-text-secondary);line-height:1.5;'>{$gig['description']}</div>
                        <button onclick=\"showOrderForm('{$gig['id']}','" . addslashes($gig['title']) . "','" . addslashes($gig['student_name']) . "','Rs. " . number_format($gig['price']) . "')\" style='align-self:flex-start;background:var(--color-accent);color:white;border:none;padding:8px 16px;border-radius:6px;font-size:12.5px;font-weight:600;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 12px rgba(29,158,117,0.15);font-family:inherit;'>Order now</button>
                      </div>";
            }
        }
        ?>
      </div>

      <div id="order-form-container" style="display:none;background:var(--color-background-secondary);border-radius:var(--border-radius-lg);padding:1.5rem; border:1px solid var(--color-border-tertiary);box-shadow:var(--shadow-sm); animation: fadeIn 0.3s ease;">
        <div style="font-size:15px;font-weight:600;margin-bottom:14px;color:var(--color-text-primary);">Confirm your transactional order record</div>
        <form method="POST" action="client_dashboard.php" style="display:flex;flex-direction:column;gap:14px;max-width:460px;">
          <input type="hidden" name="place_order_action" value="1">
          <input type="hidden" id="form-gig-id" name="gig_id">

          <div style="font-size:13.5px;color:var(--color-text-secondary);font-weight:500;">Target: <span id="form-gig-title" style="color:var(--color-text-primary);font-weight:600;"></span></div>
          <div style="font-size:13.5px;color:var(--color-text-secondary);font-weight:500;">Developer: <span id="form-student-name" style="color:var(--color-text-primary);font-weight:600;"></span></div>
          <div style="font-size:13.5px;color:var(--color-text-secondary);font-weight:500;">Costing: <span id="form-gig-price" style="color:var(--color-accent);font-weight:700;"></span></div>

          <div>
            <label style="font-size:13px;color:var(--color-text-secondary);display:block;margin-bottom:6px;font-weight:600;">Scope Requirements Documentation</label>
            <textarea name="requirements" placeholder="Provide system specific context mapping rules..."></textarea>
          </div>
          <div style="display:flex;gap:10px;margin-top:4px;">
            <button type="submit" style="background:var(--color-accent);color:white;border:none;padding:10px 22px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(29,158,117,0.2);font-family:inherit;">Confirm order</button>
            <button type="button" onclick="document.getElementById('order-form-container').style.display='none'" style="background:none;border:1px solid var(--color-border-tertiary);padding:10px 22px;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;color:var(--color-text-secondary);font-family:inherit;transition:all 0.2s;">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div id="status" class="section">
      <div style="font-size:22px;font-weight:700;margin-bottom:1.25rem;letter-spacing:-0.02em;">My order status</div>
      <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <button class="filter-btn active-filter" onclick="filterOrders('all',this)">All Listings</button>
        <button class="filter-btn" onclick="filterOrders('pending',this)">Pending Execution</button>
        <button class="filter-btn" onclick="filterOrders('completed',this)">Completed Pipeline</button>
      </div>

      <div id="order-rows" style="display:flex;flex-direction:column;gap:14px;">
        <?php
        $status_query = $conn->query("SELECT o.orderId, o.status, g.title, u.fullname as student_name, g.price FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.student_id = u.id WHERE o.client_id = '$client_id' ORDER BY o.orderId DESC");
        if ($status_query && $status_query->num_rows > 0) {
            while ($row = $status_query->fetch_assoc()) {
                $progress_width = '0%';
                $progress_color = '#e2e8f0';
                $badge = 'badge-pending';
                
                if ($row['status'] === 'pending') {
                    $progress_width = '25%';
                    $progress_color = '#6366f1';
                    $badge = 'badge-pending';
                } elseif ($row['status'] === 'in_progress') {
                    $progress_width = '60%';
                    $progress_color = '#3b82f6';
                    $badge = 'badge-progress';
                } elseif ($row['status'] === 'completed' || $row['status'] === 'pending_payment') {
                    $progress_width = '100%';
                    $progress_color = '#10b981';
                    $badge = 'badge-completed';
                } elseif ($row['status'] === 'paid') {
                    $progress_width = '100%';
                    $progress_color = '#10b981';
                    $badge = 'badge-completed';
                }

                // Determine the correct action button based on payment status
                $action_button = '';
                if ($row['status'] === 'completed' || $row['status'] === 'pending_payment') {
                    $action_button = '<a href="payment.php?order_id=' . urlencode($row['orderId']) . '">
                                        <button class="btn btn-primary" style="padding: 6px 14px; font-size: 0.85rem; border-radius: 8px;">Pay Now</button>
                                      </a>';
                } else if ($row['status'] === 'paid') {
                    $action_button = '<button class="btn btn-primary" style="opacity: 0.5; cursor: not-allowed; padding: 6px 14px; font-size: 0.85rem; border-radius: 8px;" disabled>Paid</button>';
                }
?>
                <div class='pipeline-card' style='background:var(--color-surface-primary);border:1px solid var(--color-border-primary);border-radius:24px;padding:20px;box-shadow:var(--shadow-sm);margin-bottom:16px;'>
                    <div style='display:flex;justify-content:between;align-items:center;gap:16px;flex-wrap:wrap;'>
                        <div style='flex:1;'>
                            <h4 style='font-size:16px;font-weight:600;color:var(--color-text-primary);margin-bottom:4px;'><?php echo htmlspecialchars($row['title']); ?></h4>
                            <p style='font-size:13px;color:var(--color-text-secondary);'>Order ID: #<?php echo htmlspecialchars($row['orderId']); ?></p>
                        </div>
                        <div style='display:flex;align-items:center;gap:14px;'>
                            <span style='font-size:15px;font-weight:600;color:var(--color-text-primary);'>Rs. <?php echo number_format($row['price'], 2); ?></span>
                            <span class='badge <?php echo $badge; ?>'><?php echo ucfirst($row['status']); ?></span>
                            <?php echo $action_button; ?>                                
                        </div>  
                    </div>
                    <div style='margin-top:14px;'>
                        <div style='background:var(--color-background-primary);border-radius:20px;height:7px;overflow:hidden;'>
                            <div style='width:<?php echo $progress_width; ?>;height:100%;background:<?php echo $progress_color; ?>;border-radius:20px;'></div>
                        </div>
                    </div>
                </div>
<?php
            }
        } else {
            echo "<p style='color:var(--color-text-secondary);text-align:center;padding:20px;font-weight:500;'>No registered transactional pipeline records tracked.</p>";
        }
        ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>