<?php
include 'db.php';
session_start();

// Fallback user validation logic if your team's login session isn't saved yet
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$user_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Client Form Profile Updates
if (isset($_POST['update_profile'])) {
    $business_name = mysqli_real_escape_string($conn, $_POST['business_name']);
    $business_type = mysqli_real_escape_string($conn, $_POST['business_type']);
    $business_phone = mysqli_real_escape_string($conn, $_POST['business_phone']);
    $business_address = mysqli_real_escape_string($conn, $_POST['business_address']);

    $check_profile = $conn->query("SELECT * FROM client_profiles WHERE user_id = '$user_id'");
    if ($check_profile->num_rows > 0) {
        $sql = "UPDATE client_profiles SET business_name='$business_name', business_type='$business_type', business_phone='$business_phone', business_address='$business_address' WHERE user_id='$user_id'";
    } else {
        $sql = "INSERT INTO client_profiles (user_id, business_name, business_type, business_phone, business_address) VALUES ('$user_id', '$business_name', '$business_type', '$business_phone', '$business_address')";
    }

    if ($conn->query($sql)) {
        $message = "Business Profile successfully saved!";
    } else {
        $message = "Database Sync Error: " . $conn->error;
    }
}

// 2. Load Existing Client Details from DB
$profile_res = $conn->query("SELECT * FROM client_profiles WHERE user_id = '$user_id'");
$profile = $profile_res->fetch_assoc();

// 3. Fetch Data rows for Order Status Track Dashboard
$orders_sql = "SELECT orders.orderId, orders.status, orders.created_at, gigs.title, gigs.price, users.fullname AS student_name
               FROM orders
               JOIN gigs ON orders.gig_id = gigs.id
               JOIN users ON orders.student_id = users.id
               WHERE orders.client_id = '$user_id'
               ORDER BY orders.created_at DESC";
$orders_res = $conn->query($orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Workspace | UniLance</title>
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: #141b2d;
            --text-color: #ffffff;
            --text-muted: #94a3b8;
            --primary-green: #10b981;
            --primary-hover: #059669;
            --border-color: #1e293b;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 40px;
        }

        .container { max-width: 1200px; margin: 0 auto; }
        .green-text { color: var(--primary-green); }
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-top: 30px; }
        .card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; }

        label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 14px; }
        input { width: 100%; padding: 10px; background-color: var(--bg-color); border: 1px solid var(--border-color); border-radius: 6px; color: white; margin-bottom: 15px; box-sizing: border-box; }
        input:focus { border-color: var(--primary-green); outline: none; }

        button { background-color: var(--primary-green); color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; transition: background 0.2s; }
        button:hover { background-color: var(--primary-hover); }
        .alert { background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--primary-green); color: var(--primary-green); padding: 12px; border-radius: 6px; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { border-bottom: 2px solid var(--border-color); color: var(--text-muted); padding: 12px; font-size: 14px; }
        td { padding: 14px; border-bottom: 1px solid var(--border-color); }

        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; text-transform: uppercase; font-weight: bold; }
        .status-pending { background-color: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .status-in_progress { background-color: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .status-completed { background-color: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-cancelled { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>

<div class="container">
    <h1 style="font-size: 28px;">UniLance <span class="green-text">Client Center</span></h1>

    <?php if(!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2 style="margin-top:0;">Business Profile Setup</h2>
            <form action="" method="POST">
                <label>Business / Corporate Name</label>
                <input type="text" name="business_name" value="<?php echo htmlspecialchars($profile['business_name'] ?? ''); ?>" required>

                <label>Industry Classification Type</label>
                <input type="text" name="business_type" value="<?php echo htmlspecialchars($profile['business_type'] ?? ''); ?>" required>

                <label>Contact Verification Phone</label>
                <input type="text" name="business_phone" value="<?php echo htmlspecialchars($profile['business_phone'] ?? ''); ?>" required>

                <label>Physical Address Headquarters</label>
                <input type="text" name="business_address" value="<?php echo htmlspecialchars($profile['business_address'] ?? ''); ?>">

                <button type="submit" name="update_profile">Apply Profile Sync</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Live Project Order Tracking Pipeline</h2>
            <?php if ($orders_res && $orders_res->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Code</th>
                            <th>Active Project Gig</th>
                            <th>Hired Student Developer</th>
                            <th>Total Budget</th>
                            <th>State Tracking Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $orders_res->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['orderId']; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td class="green-text">$<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo str_replace('_', ' ', $row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); margin: 0;">No ongoing tracking pipeline orders found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>