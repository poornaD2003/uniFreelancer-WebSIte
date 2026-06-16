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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --color-background-primary: #0b0f19;
            --color-background-secondary: #111827;
            --color-border-tertiary: #1f2937;
            --color-text-primary: #f3f4f6;
            --color-text-secondary: #9ca3af;
            --border-radius-md: 8px;
            --border-radius-lg: 12px;
        }
        body { background: var(--color-background-primary); color: var(--color-text-primary); font-family: sans-serif; padding: 20px; }
        .sidebar {
            width: 240px; min-height: 600px;
            background: var(--color-background-secondary);
            border-right: 0.5px solid var(--color-border-tertiary);
            padding: 1.5rem 0;
            display: flex; flex-direction: column; gap: 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 20px;
            font-size: 14px; color: var(--color-text-secondary);
            cursor: pointer; border: none; background: none;
            width: 100%; text-align: left; transition: all 0.2s;
        }
        .nav-item.active {
            background: var(--color-background-primary);
            color: var(--color-text-primary);
            font-weight: 500;
            border-left: 3px solid #1D9E75;
            padding-left: 17px;
        }
        .nav-item i { font-size: 18px; }
        .metric-card {
            background: var(--color-background-secondary);
            border-radius: var(--border-radius-md);
            padding: 1rem 1.25rem;
            flex: 1; min-width: 200px;
            border: 0.5px solid var(--color-border-tertiary);
        }
        .metric-label { font-size: 12px; color: var(--color-text-secondary); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .metric-value { font-size: 22px; font-weight: 500; color: var(--color-text-primary); }
        .metric-sub { font-size: 11px; color: var(--color-text-secondary); margin-top: 4px; }
        .badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 500; display: inline-block; }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-completed { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .section { display: none; }
        .section.visible { display: block; }
        .filter-btn {
            font-size: 12px; padding: 6px 14px;
            border: 0.5px solid var(--color-border-tertiary);
            background: var(--color-background-primary);
            border-radius: var(--border-radius-md);
            color: var(--color-text-secondary); cursor: pointer;
        }
        .filter-btn.active-filter { background: #1D9E75; color: #fff; border-color: #1D9E75; }
        .gig-card {
            border: 0.5px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-lg);
            background: var(--color-background-secondary);
            padding: 1.25rem;
            display: flex; flex-direction: column; gap: 10px;
        }
        textarea { width: 100%; height: 90px; padding: 10px; background: var(--color-background-primary); border: 0.5px solid var(--color-border-tertiary); color: white; border-radius: var(--border-radius-md); }
    </style>

    <script>
        function switchTab(name, btn) {
            // 1. Hide all sections
            document.querySelectorAll('.section').forEach(s => s.classList.remove('visible'));

            // 2. Show the target section
            var target = document.getElementById(name);
            if (target) {
                target.classList.add('visible');
            }

            // 3. Remove active state from all buttons
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));

            // 4. Add active state to the clicked button
            if (btn) {
                btn.classList.add('active');
            } else {
                // If btn isn't provided (e.g., loaded from URL hash), find the right button and activate it
                var activeBtn = document.querySelector(`.nav-item[onclick*="${name}"]`);
                if (activeBtn) activeBtn.classList.add('active');
            }

            // 5. Update browser URL hash quietly
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
    <div id="success-toast" style="position: fixed; top: 80px; right: 20px; background: rgba(29, 158, 117, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: white; padding: 14px 24px; border-radius: var(--border-radius-md); box-shadow: 0 8px 32px 0 rgba(0,0,0,0.37); border: 1px solid rgba(255,255,255,0.1); z-index: 1000; display: flex; align-items: center; gap: 12px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-20px); opacity: 0;">
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

<div style="display:flex; border: 0.5px solid var(--color-border-tertiary); border-radius: var(--border-radius-lg); overflow: hidden; min-height: 650px;">
  
  <div class="sidebar">
    <div style="padding: 0 20px 1rem; border-bottom: 0.5px solid var(--color-border-tertiary); margin-bottom: 0.5rem;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:38px;height:38px;border-radius:50%;background:#1D9E75;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:500;color:white;"><?php echo $initials; ?></div>
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--color-text-primary);"><?php echo $customer_name; ?></div>
          <div style="font-size:11px;color:var(--color-text-secondary);">Customer Portal</div>
        </div>
      </div>
    </div>
    <button class="nav-item active" onclick="switchTab('dashboard',this)"><i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard</button>
    <button class="nav-item" onclick="switchTab('order',this)"><i class="ti ti-shopping-cart" aria-hidden="true"></i> Place Order</button>
    <button class="nav-item" onclick="switchTab('status',this)"><i class="ti ti-list-check" aria-hidden="true"></i> Order Status</button>
  </div>

  <div style="flex:1; padding: 1.5rem; overflow:auto;">

    <div id="dashboard" class="section visible">
      <div style="margin-bottom:1.25rem;">
        <div style="font-size:18px;font-weight:500;">Welcome, <?php echo $customer_name; ?> 👋</div>
        <div style="font-size:13px;color:var(--color-text-secondary);margin-top:4px;">Here's an overview of your workspace activities</div>
      </div>

      <div style="display:flex;gap:12px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-shopping-bag"></i> Total orders</div>
          <div class="metric-value"><?php echo $total_orders; ?></div>
          <div class="metric-sub">All time activity</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-clock"></i> Pending</div>
          <div class="metric-value" style="color:#f59e0b;"><?php echo $pending_orders; ?></div>
          <div class="metric-sub">Awaiting delivery execution</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-circle-check"></i> Completed</div>
          <div class="metric-value" style="color:#10b981;"><?php echo $completed_orders; ?></div>
          <div class="metric-sub">Successfully deployed</div>
        </div>
      </div>

      <div style="background:var(--color-background-secondary);border-radius:var(--border-radius-md);padding:1rem 1.25rem; border: 0.5px solid var(--color-border-tertiary);">
        <div style="font-size:14px;font-weight:500;margin-bottom:12px;">Recent Pipeline Entries</div>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
          <thead>
            <tr style="color:var(--color-text-secondary); text-align: left;">
              <th style="padding:8px 0;">Job Architecture</th>
              <th style="padding:8px 0;">Student Freelancer</th>
              <th style="padding:8px 0;">Status Badge</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $recent_query = $conn->query("SELECT o.status, g.title, u.fullname as student_name FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.student_id = u.id WHERE o.client_id = '$client_id' ORDER BY o.orderId DESC LIMIT 3");
            if ($recent_query && $recent_query->num_rows > 0) {
                while($r = $recent_query->fetch_assoc()) {
                    $badge = ($r['status'] === 'pending') ? 'badge-pending' : 'badge-completed';
                    echo "<tr style='border-top:0.5px solid var(--color-border-tertiary);'>
                            <td style='padding:12px 0;'>{$r['title']}</td>
                            <td style='padding:12px 0;color:var(--color-text-secondary);'>{$r['student_name']}</td>
                            <td style='padding:12px 0;'><span class='badge {$badge}'>" . ucfirst($r['status']) . "</span></td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='padding:10px 0;color:var(--color-text-secondary);'>No recent records found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="order" class="section">
      <div style="font-size:18px;font-weight:500;margin-bottom:4px;">Place an order</div>
      <div style="font-size:13px;color:var(--color-text-secondary);margin-bottom:1.25rem;">Browse student listings and register a project requirement entry</div>

      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:1.5rem;">
        <?php
        $gigs_query = $conn->query("SELECT g.id, g.title, g.description, g.price  , u.fullname as student_name FROM gigs g JOIN users u ON g.student_id = u.id");
        if ($gigs_query && $gigs_query->num_rows > 0) {
            while($gig = $gigs_query->fetch_assoc()) {
                echo "<div class='gig-card'>
                        <div style='display:flex;justify-content:space-between;'>
                            <div>
                                <div style='font-size:14px;font-weight:500;'>{$gig['title']}</div>
                                <div style='font-size:12px;color:var(--color-text-secondary);margin-top:2px;'>by {$gig['student_name']}</div>
                            </div>
                            <div style='text-align:right;'>
                                <div style='font-size:14px;font-weight:500;color:#1D9E75;'>Rs. " . number_format($gig['price']) . "</div>
                                <div style='font-size:11px;color:var(--color-text-secondary);'>Runtime: {$gig['delivery_days']} days</div>
                            </div>
                        </div>
                        <div style='font-size:12px;color:var(--color-text-secondary);'>{$gig['description']}</div>
                        <button onclick=\"showOrderForm('{$gig['id']}','" . addslashes($gig['title']) . "','" . addslashes($gig['student_name']) . "','Rs. " . number_format($gig['price']) . "')\" style='align-self:flex-start;background:#1D9E75;color:white;border:none;padding:6px 14px;border-radius:4px;font-size:12px;cursor:pointer;'>Order now</button>
                      </div>";
            }
        }
        ?>
      </div>

      <div id="order-form-container" style="display:none;background:var(--color-background-secondary);border-radius:var(--border-radius-md);padding:1.25rem; border:0.5px solid var(--color-border-tertiary);">
        <div style="font-size:14px;font-weight:500;margin-bottom:12px;">Confirm your transactional order record</div>
        <form method="POST" action="client_dashboard.php" style="display:flex;flex-direction:column;gap:12px;max-width:420px;">
          <input type="hidden" name="place_order_action" value="1">
          <input type="hidden" id="form-gig-id" name="gig_id">

          <div style="font-size:13px;color:var(--color-text-secondary);">Target: <span id="form-gig-title" style="color:white;font-weight:500;"></span></div>
          <div style="font-size:13px;color:var(--color-text-secondary);">Developer: <span id="form-student-name" style="color:white;font-weight:500;"></span></div>
          <div style="font-size:13px;color:var(--color-text-secondary);">Costing: <span id="form-gig-price" style="color:#1D9E75;font-weight:500;"></span></div>

          <div>
            <label style="font-size:13px;color:var(--color-text-secondary);display:block;margin-bottom:5px;">Scope Requirements Documentation</label>
            <textarea name="requirements" placeholder="Provide system specific context mapping rules..."></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" style="background:#1D9E75;color:white;border:none;padding:9px 20px;border-radius:4px;font-size:13px;cursor:pointer;">Confirm order</button>
            <button type="button" onclick="document.getElementById('order-form-container').style.display='none'" style="background:none;border:0.5px solid var(--color-border-tertiary);padding:9px 20px;border-radius:4px;font-size:13px;cursor:pointer;color:var(--color-text-secondary);">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div id="status" class="section">
      <div style="font-size:18px;font-weight:500;margin-bottom:1.25rem;">My order status</div>
      <div style="display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap;">
        <button class="filter-btn active-filter" onclick="filterOrders('all',this)">All Listings</button>
        <button class="filter-btn" onclick="filterOrders('pending',this)">Pending Execution</button>
        <button class="filter-btn" onclick="filterOrders('completed',this)">Completed Pipeline</button>
      </div>

      <div id="order-rows" style="display:flex;flex-direction:column;gap:10px;">
        <?php
        $status_query = $conn->query("SELECT o.orderId, o.status, g.title, u.fullname as student_name, g.price FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.student_id = u.id WHERE o.client_id = '$client_id' ORDER BY o.orderId DESC");
        if ($status_query && $status_query->num_rows > 0) {
            while($row = $status_query->fetch_assoc()) {
                $badge = ($row['status'] === 'pending') ? 'badge-pending' : 'badge-completed';
                $progress_width = ($row['status'] === 'pending') ? '45%' : '100%';
                $progress_color = ($row['status'] === 'pending') ? '#1D9E75' : '#0F6E56';

                echo "<div class='order-row' data-status='{$row['status']}' style='border:0.5px solid var(--color-border-tertiary);border-radius:var(--border-radius-md);padding:1rem 1.25rem; background: var(--color-background-secondary);'>
                        <div style='display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;'>
                            <div>
                                <div style='font-size:14px;font-weight:500;'>{$row['title']}</div>
                                <div style='font-size:12px;color:var(--color-text-secondary);margin-top:2px;'>Developer Relation: {$row['student_name']}</div>
                            </div>
                            <div style='display:flex;align-items:center;gap:12px;'>
                                <span style='font-size:13px;font-weight:500;'>Rs. " . number_format($row['price']) . "</span>
                                <span class='badge {$badge}'>" . ucfirst($row['status']) . "</span>
                            </div>
                        </div>
                        <div style='margin-top:10px;'>
                            <div style='background:var(--color-background-primary);border-radius:20px;height:6px;overflow:hidden;'>
                                <div style='width:{$progress_width};height:100%;background:{$progress_color};border-radius:20px;'></div>
                            </div>
                        </div>
                      </div>";
            }
        } else {
            echo "<p style='color:var(--color-text-secondary);'>No registered transactional pipeline records tracked.</p>";
        }
        ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>