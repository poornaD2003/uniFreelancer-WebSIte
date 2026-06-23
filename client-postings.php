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
$msg = "";
$error_msg = "";

// 1. Process Update Posting Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_posting'])) {
    $gig_id = intval($_POST['gig_id']);
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    if ($gig_id > 0 && !empty($title) && !empty($description) && $price > 0) {
        $stmt = $conn->prepare("UPDATE gigs SET title = ?, price = ?, category = ?, description = ? WHERE id = ? AND student_id = ?");
        if ($stmt) {
            $stmt->bind_param("sdssii", $title, $price, $category, $description, $gig_id, $client_id);
            if ($stmt->execute()) {
                $msg = "✓ Job posting updated successfully.";
            } else {
                $error_msg = "Failed to update job posting database entry.";
            }
            $stmt->close();
        }
    } else {
        $error_msg = "Please fill in all fields with valid information.";
    }
}

// 2. Process Delete Posting Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_posting'])) {
    $gig_id = intval($_POST['gig_id']);
    if ($gig_id > 0) {
        $stmt = $conn->prepare("DELETE FROM gigs WHERE id = ? AND student_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $gig_id, $client_id);
            if ($stmt->execute()) {
                $msg = "✓ Job posting deleted successfully.";
            } else {
                $error_msg = "Failed to delete the job posting.";
            }
            $stmt->close();
        }
    }
}

// 3. Fetch Client Postings
$postings = [];
$stmt = $conn->prepare("SELECT * FROM gigs WHERE student_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $postings[] = $row;
    }
    $stmt->close();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/client_dashboard.css">

<style>
    .edit-form-panel {
        display: none;
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-top: 1rem;
        animation: slideDown 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .edit-form-panel.open {
        display: block;
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
      <li class="sidebar-item active">
        <a href="client-postings.php"><i class="ti ti-clipboard-list"></i> My Job Postings</a>
      </li>
      <li class="sidebar-item">
        <a href="client-payments.php"><i class="ti ti-receipt"></i> Billing & Payments</a>
      </li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="header-section" style="margin-bottom: 2rem;">
      <div>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 6px; color: var(--text);">My Job Postings</h1>
        <p style="color: var(--muted); font-size: 0.9rem; font-weight: 500;">Manage and edit your active and pending job requirements.</p>
      </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16,185,129,0.2); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; color: var(--green); font-weight: 600;">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239,68,68,0.2); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; color: #ef4444; font-weight: 600;">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="section-card">
      <div class="section-title" style="margin-bottom: 1.5rem;">
        <i class="ti ti-clipboard-list" style="color: var(--green); font-size: 1.3rem;"></i> Active Postings Queue
      </div>

      <div class="pipeline-list">
        <?php if (empty($postings)): ?>
            <div style="text-align: center; padding: 3rem 0; color: var(--muted); font-weight: 500;">
                <i class="ti ti-folder-off" style="font-size: 2.5rem; color: var(--green); display: block; margin-bottom: 10px;"></i>
                You haven't posted any jobs yet.
                <a href="post-job.php" style="color: var(--green); text-decoration: underline; display: block; margin-top: 8px;">Post your first project now!</a>
            </div>
        <?php else: foreach ($postings as $gig): 
            $gig_id = $gig['id'];
            $badge = ($gig['status'] === 'approve') ? 'badge-completed' : 'badge-pending';
            $status_display = ($gig['status'] === 'approve') ? 'Approved' : 'Pending Approval';
        ?>
            <div class="pipeline-item" id="posting-row-<?php echo $gig_id; ?>">
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%; flex-wrap:wrap; gap:15px;">
                    <div style="flex:1; min-width:250px;">
                        <h4 style="font-size:1.05rem; font-weight:700; margin-bottom:6px; color: var(--text);"><?php echo htmlspecialchars($gig['title']); ?></h4>
                        <p style="font-size:0.85rem; color:var(--muted); font-weight:500;">
                            <strong>Category:</strong> <?php echo htmlspecialchars($gig['category']); ?> &nbsp;|&nbsp; 
                            <strong>Posted On:</strong> <?php echo date('M d, Y', strtotime($gig['created_at'])); ?>
                        </p>
                    </div>
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span style="font-size:1.05rem; font-weight:700; color:var(--text);">Rs. <?php echo number_format($gig['price'], 2); ?></span>
                        <span class="badge <?php echo $badge; ?>"><?php echo $status_display; ?></span>
                        <button onclick="toggleEdit(<?php echo $gig_id; ?>)" class="filter-btn" style="display:flex; align-items:center; gap:6px;">
                            <i class="ti ti-edit"></i> Edit
                        </button>
                        <form method="POST" action="client-postings.php" onsubmit="return confirm('Are you sure you want to delete this job posting?');" style="margin:0; padding:0;">
                            <input type="hidden" name="gig_id" value="<?php echo $gig_id; ?>">
                            <button type="submit" name="delete_posting" class="filter-btn" style="display:flex; align-items:center; gap:6px; background: rgba(239, 68, 68, 0.08); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                                <i class="ti ti-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                
                <div style="margin-top: 10px; font-size: 0.9rem; color: var(--text); line-height: 1.5; white-space: pre-wrap; background: rgba(0,0,0,0.02); padding: 10px 14px; border-radius: var(--radius); border-left: 3px solid var(--green);"><?php echo htmlspecialchars($gig['description']); ?></div>

                <!-- Inline Editing Form -->
                <div class="edit-form-panel" id="edit-panel-<?php echo $gig_id; ?>">
                    <form method="POST" action="client-postings.php">
                        <input type="hidden" name="gig_id" value="<?php echo $gig_id; ?>">
                        
                        <div class="input-group">
                            <label>Job Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($gig['title']); ?>" required>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <div class="input-group" style="flex: 1;">
                                <label>Category</label>
                                <select name="category" required>
                                    <?php foreach(['Development','Design','Writing','Tutoring','Other'] as $c): ?>
                                        <option value="<?php echo $c; ?>" <?php echo $gig['category']===$c ? 'selected':''; ?>><?php echo $c; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input-group" style="flex: 1;">
                                <label>Budget / Price (LKR)</label>
                                <input type="number" step="0.01" min="1" name="price" value="<?php echo $gig['price']; ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Description</label>
                            <textarea name="description" rows="5" required><?php echo htmlspecialchars($gig['description']); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                            <button type="button" onclick="toggleEdit(<?php echo $gig_id; ?>)" class="filter-btn">Cancel</button>
                            <button type="submit" name="edit_posting" class="chat-btn-send" style="height: 38px; box-shadow: none;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function toggleEdit(id) {
    const panel = document.getElementById('edit-panel-' + id);
    if (panel) {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
