<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

$msg = ""; $error_msg = ""; $show_step2 = false;
$temp_title = ""; $temp_category = "Development"; $temp_price = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_step1'])) {
        $temp_title=trim($_POST['title']??''); $temp_category=trim($_POST['category']??'Development'); $temp_price=(float)($_POST['price']??0);
        if(!empty($temp_title)&&$temp_price>0){$show_step2=true;}else{$error_msg="Please fill in all fields.";}
    }
    if (isset($_POST['back_to_step1'])) {
        $temp_title=trim($_POST['title']??''); $temp_category=trim($_POST['category']??'Development'); $temp_price=(float)($_POST['price']??0); $show_step2=false;
    }
    if (isset($_POST['post_gig'])) {
        $title=trim($_POST['title']??''); $category=trim($_POST['category']??'Development'); $price=(float)($_POST['price']??0); $desc=trim($_POST['description']??'');
        if(!empty($title)&&!empty($desc)&&$price>0){
<<<<<<< Updated upstream
            $img = 'default.png';
            if (isset($_FILES['gig_image']) && $_FILES['gig_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['gig_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    if (!is_dir(__DIR__ . '/uploads')) { mkdir(__DIR__ . '/uploads', 0777, true); }
                    $img = uniqid('gig_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['gig_image']['tmp_name'], __DIR__ . '/uploads/' . $img);
=======
            if (!is_dir(__DIR__ . '/uploads')) { mkdir(__DIR__ . '/uploads', 0777, true); }
            $imgs = [];
            if (isset($_FILES['gig_images']) && is_array($_FILES['gig_images']['name'])) {
                $total = count($_FILES['gig_images']['name']);
                for ($i = 0; $i < $total; $i++) {
                    if ($_FILES['gig_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['gig_images']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                            $fname = uniqid('gig_', true) . '.' . $ext;
                            if (move_uploaded_file($_FILES['gig_images']['tmp_name'][$i], __DIR__ . '/uploads/' . $fname)) {
                                $imgs[] = $fname;
                            }
                        }
                    }
>>>>>>> Stashed changes
                }
            }
            $img_str = !empty($imgs) ? implode(',', $imgs) : 'default.png';
            $s=$conn->prepare("INSERT INTO gigs (student_id,title,image,description,price,category,status) VALUES(?,?,?,?,?,?,'pending')");
            if($s){$s->bind_param("isssds",$user_id,$title,$img_str,$desc,$price,$category);if($s->execute())$msg="✓ Gig posted! (Pending Approval)";else $error_msg="Failed to post.";$s->close();}
        } else { $error_msg="Please complete all fields."; }
    }
    if (isset($_POST['edit_gig'])) {
<<<<<<< Updated upstream
        $gid=(int)($_POST['gig_id']??0); $et=trim($_POST['e_title']??''); $ec=trim($_POST['e_category']??'Development'); $ep=(float)($_POST['e_price']??0); $ed=trim($_POST['e_description']??'');
        if($gid>0&&!empty($et)&&!empty($ed)&&$ep>0){
            $s=$conn->prepare("SELECT status, image FROM gigs WHERE id=? AND student_id=? LIMIT 1");
            if($s){$s->bind_param("ii",$gid,$user_id);$s->execute();$chk=$s->get_result()->fetch_assoc();$s->close();
                if($chk){
                    $img = $chk['image'];
                    if (isset($_FILES['e_gig_image']) && $_FILES['e_gig_image']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['e_gig_image']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                            if (!is_dir(__DIR__ . '/uploads')) { mkdir(__DIR__ . '/uploads', 0777, true); }
                            $new_img = uniqid('gig_', true) . '.' . $ext;
                            if (move_uploaded_file($_FILES['e_gig_image']['tmp_name'], __DIR__ . '/uploads/' . $new_img)) {
                                if ($img !== 'default.png' && file_exists(__DIR__ . '/uploads/' . $img)) {
                                    unlink(__DIR__ . '/uploads/' . $img);
                                }
                                $img = $new_img;
                            }
                        }
                    }
                    $s=$conn->prepare("UPDATE gigs SET title=?,description=?,price=?,category=?,image=? WHERE id=? AND student_id=?");
                    if($s){$s->bind_param("ssdsiii",$et,$ed,$ep,$ec,$img,$gid,$user_id);if($s->execute())$msg="✓ Gig updated.";else $error_msg="Update failed.";$s->close();}
                } else { $error_msg="Only pending gigs can be edited."; }
            }
        } else { $error_msg="Fill all fields to update."; }
=======
        $gid = (int)($_POST['gig_id'] ?? 0); 
        $et  = trim($_POST['e_title'] ?? ''); 
        $ec  = trim($_POST['e_category'] ?? 'Development'); 
        $ep  = (float)($_POST['e_price'] ?? 0); 
        $ed  = trim($_POST['e_description'] ?? '');
        
        if ($gid > 0 && !empty($et) && !empty($ed) && $ep > 0) {
            $s = $conn->prepare("SELECT image FROM gigs WHERE id=? AND student_id=? LIMIT 1");
            if ($s) {
                $s->bind_param("ii", $gid, $user_id);
                $s->execute();
                $chk = $s->get_result()->fetch_assoc();
                $s->close();
                
                if ($chk) {
                    $target_dir = __DIR__ . '/uploads/';
                    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                    
                    $current = ($chk['image'] !== 'default.png') ? explode(',', $chk['image']) : [];
                    
                    // 1. Delete checked images
                    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                        $keep = [];
                        foreach ($current as $img_name) {
                            $img_name = trim($img_name);
                            if (in_array($img_name, $_POST['delete_images'])) {
                                if (file_exists($target_dir . $img_name)) {
                                    unlink($target_dir . $img_name);
                                }
                            } else {
                                $keep[] = $img_name;
                            }
                        }
                        $current = $keep;
                    }
                    
                    // 2. Upload new images
                    if (isset($_FILES['e_gig_images']) && is_array($_FILES['e_gig_images']['name'])) {
                        $total = count($_FILES['e_gig_images']['name']);
                        for ($i = 0; $i < $total; $i++) {
                            if ($_FILES['e_gig_images']['error'][$i] === UPLOAD_ERR_OK) {
                                $ext = strtolower(pathinfo($_FILES['e_gig_images']['name'][$i], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                                    $fname = uniqid('gig_', true) . '.' . $ext;
                                    if (move_uploaded_file($_FILES['e_gig_images']['tmp_name'][$i], $target_dir . $fname)) {
                                        $current[] = $fname;
                                    }
                                }
                            }
                        }
                    }
                    
                    // 3. Build new image string
                    $img_str = !empty($current) ? implode(',', $current) : 'default.png';
                    
                    // 4. Update gig
                    if (empty($error_msg)) {
                        $s = $conn->prepare("UPDATE gigs SET title=?, description=?, price=?, category=?, image=? WHERE id=? AND student_id=?");
                        if ($s) {
                            $s->bind_param("ssdssii", $et, $ed, $ep, $ec, $img_str, $gid, $user_id);
                            if ($s->execute()) {
                                $msg = "✓ Gig updated successfully.";
                            } else {
                                $error_msg = "Database update failed: " . $s->error;
                            }
                            $s->close();
                        }
                    }
                } else { 
                    $error_msg = "Gig not found."; 
                }
            }
        } else { 
            $error_msg = "Please fill in all fields."; 
        }
>>>>>>> Stashed changes
    }
    if (isset($_POST['delete_gig'])) {
        $gid=(int)$_POST['gig_id'];
        $s=$conn->prepare("SELECT image FROM gigs WHERE id=? AND student_id=? LIMIT 1");
        if($s){$s->bind_param("ii",$gid,$user_id);$s->execute();$chk=$s->get_result()->fetch_assoc();$s->close();
            if($chk && $chk['image'] !== 'default.png') {
                foreach (explode(',', $chk['image']) as $f) {
                    $f = trim($f);
                    if (!empty($f) && file_exists(__DIR__ . '/uploads/' . $f)) {
                        unlink(__DIR__ . '/uploads/' . $f);
                    }
                }
            }
        }
        $s=$conn->prepare("DELETE FROM gigs WHERE id=? AND student_id=?");
        if($s){$s->bind_param("ii",$gid,$user_id);if($s->execute())$msg="✓ Gig deleted.";else $error_msg="Delete failed.";$s->close();}
    }
}

$gigs=[];
$s=$conn->prepare("SELECT * FROM gigs WHERE student_id=? ORDER BY created_at DESC");
if($s){$s->bind_param("i",$user_id);$s->execute();$res=$s->get_result();while($r=$res->fetch_assoc())$gigs[]=$r;$s->close();}
?>
<link rel="stylesheet" href="css/student.css">
<div class="wrap">
    <aside class="sidebar"><h2>Student Hub</h2><nav>
        <a href="student-dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="student-profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
        <a href="student-post-job.php" class="active"><i class="fas fa-briefcase"></i> Post Gig</a>
        <a href="student-orders.php"><i class="fas fa-shopping-basket"></i> Orders</a>
    </nav></aside>
    <main class="main">
        <h1>Post a Service Gig</h1>
        <?php if(!empty($msg)): ?><div style="background:rgba(16,185,129,.1);border:1px solid var(--primary);color:var(--primary);padding:1rem;border-radius:8px;margin-bottom:1rem;"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if(!empty($error_msg)): ?><div style="background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#ef4444;padding:1rem;border-radius:8px;margin-bottom:1rem;"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

        <div class="container">
            <?php if(!$show_step2): ?>
                <div class="step-progress"><div class="step-wrapper"><div class="step-bubble active">1</div><div class="step-label">Basic Info</div></div><div class="step-line"></div><div class="step-wrapper"><div class="step-bubble">2</div><div class="step-label">Description</div></div></div>
                <div class="section-header"><i class="fas fa-info-circle"></i> Step 1: Gig Information</div>
                <form method="POST" action="student-post-job.php">
                    <div class="input-group"><label>Gig Title</label><input type="text" name="title" placeholder="e.g. I will build a React website" required value="<?php echo htmlspecialchars($temp_title); ?>"></div>
                    <div class="input-group"><label>Category</label><select name="category" required><?php foreach(['Development','Design','Writing','Tutoring','Other'] as $c): ?><option value="<?php echo $c; ?>"<?php echo $temp_category===$c?' selected':''; ?>><?php echo $c; ?></option><?php endforeach; ?></select></div>
                    <div class="input-group"><label>Price / Budget (LKR)</label><input type="number" step="0.01" min="1" name="price" placeholder="e.g. 4999.00" required value="<?php echo htmlspecialchars($temp_price); ?>"></div>
                    <button type="submit" name="submit_step1">Continue &rarr;</button>
                </form>
            <?php else: ?>
                <div class="step-progress"><div class="step-wrapper"><div class="step-bubble done">✓</div><div class="step-label">Basic Info</div></div><div class="step-line" style="opacity:.7;"></div><div class="step-wrapper"><div class="step-bubble active">2</div><div class="step-label">Description</div></div></div>
                <div class="section-header"><i class="fas fa-align-left"></i> Step 2: Description</div>
                <form method="POST" action="student-post-job.php" enctype="multipart/form-data">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($temp_title); ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($temp_category); ?>">
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($temp_price); ?>">
                    <div class="input-group"><label>Describe Your Service</label><textarea name="description" rows="6" placeholder="Describe what you offer, deliverables, timelines..." required></textarea></div>
                    <div class="input-group"><label>Service Images (Optional, select multiple)</label><input type="file" name="gig_images[]" multiple accept="image/*"></div>
                    <div style="display:flex;gap:1rem;margin-top:.5rem;">
                        <button type="submit" name="back_to_step1" formnovalidate style="background:transparent;border:1px solid var(--border-color);color:var(--text-main);flex:1;">&larr; Back</button>
                        <button type="submit" name="post_gig" style="flex:2;">✓ Post Service Gig</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="container">
            <div class="section-header"><i class="fas fa-list-alt"></i> Your Active &amp; Pending Gigs</div>
            <div class="posts-list">
                <?php if(empty($gigs)): ?><p style="color:var(--text-muted);">No gigs posted yet.</p>
                <?php else: foreach($gigs as $gig): $ip=($gig['status']==='pending'); ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="post-title"><?php echo htmlspecialchars($gig['title']); ?></div>
                            <span class="badge badge-<?php echo $gig['status']; ?>"><?php echo $gig['status']==='approve'?'Approved':'Pending'; ?></span>
                        </div>
                        <div class="post-meta"><strong>Category:</strong> <?php echo htmlspecialchars($gig['category']); ?> &nbsp;|&nbsp; <strong>Price:</strong> Rs. <?php echo number_format($gig['price'],2); ?> &nbsp;|&nbsp; <strong>Created:</strong> <?php echo date('M d, Y',strtotime($gig['created_at'])); ?></div>
                        <?php
                        $display_imgs = (!empty($gig['image']) && $gig['image'] !== 'default.png') ? explode(',', $gig['image']) : [];
                        if (!empty($display_imgs)):
                        ?>
                            <div class="post-image" style="margin: 10px 0; display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php foreach($display_imgs as $dimg): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(trim($dimg)); ?>" alt="Gig Image" style="max-width: 120px; max-height: 80px; border-radius: 6px; border: 1px solid var(--border-color); object-fit: cover;">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="post-desc"><?php echo nl2br(htmlspecialchars($gig['description'])); ?></div>
                        <div class="actions">
                            <button type="button" class="btn-small" onclick="toggleEdit(<?php echo $gig['id']; ?>)">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                            <form method="POST" action="student-post-job.php" onsubmit="return confirm('Delete this gig?');" style="margin:0;padding:0;"><input type="hidden" name="gig_id" value="<?php echo $gig['id']; ?>"><button type="submit" name="delete_gig" class="btn-small" style="background:#ef4444;border:none;color:#fff;cursor:pointer;margin:0;"><i class="fas fa-trash"></i> Delete</button></form>
                        </div>
                        <div class="edit-panel" id="edit-panel-<?php echo $gig['id']; ?>">
                            <form method="POST" action="student-post-job.php" enctype="multipart/form-data">
                                <input type="hidden" name="gig_id" value="<?php echo $gig['id']; ?>">
                                <label>Gig Title</label><input type="text" name="e_title" value="<?php echo htmlspecialchars($gig['title']); ?>" required>
                                <label>Category</label><select name="e_category" required><?php foreach(['Development','Design','Writing','Tutoring','Other'] as $c): ?><option value="<?php echo $c; ?>"<?php echo $gig['category']===$c?' selected':''; ?>><?php echo $c; ?></option><?php endforeach; ?></select>
                                <label>Price (LKR)</label><input type="number" step="0.01" min="1" name="e_price" value="<?php echo $gig['price']; ?>" required>
                                <label>Description</label><textarea name="e_description" rows="4" required><?php echo htmlspecialchars($gig['description']); ?></textarea>
<<<<<<< Updated upstream
                                <label>Change Service Image (Optional)</label><input type="file" name="e_gig_image" accept="image/*" style="margin-bottom: 1rem;">
=======
                                <?php
                                $edit_imgs = (!empty($gig['image']) && $gig['image'] !== 'default.png') ? explode(',', $gig['image']) : [];
                                if (!empty($edit_imgs)):
                                ?>
                                    <label>Current Images (check to delete)</label>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:1rem;">
                                        <?php foreach($edit_imgs as $eimg): $eimg = trim($eimg); ?>
                                            <div style="position:relative;display:inline-block;">
                                                <img src="uploads/<?php echo htmlspecialchars($eimg); ?>" style="max-width:100px;max-height:80px;border-radius:6px;border:1px solid var(--border-color);object-fit:cover;display:block;">
                                                <label style="position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,.6);color:#fff;font-size:11px;padding:2px 6px;border-radius:4px;cursor:pointer;">
                                                    <input type="checkbox" name="delete_images[]" value="<?php echo htmlspecialchars($eimg); ?>"> Del
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <label>Add More Images (Optional, select multiple)</label><input type="file" name="e_gig_images[]" multiple accept="image/*" style="margin-bottom: 1rem;">
>>>>>>> Stashed changes
                                <div class="edit-actions">
                                    <button type="submit" name="edit_gig" style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:.75rem 1.5rem;cursor:pointer;font-weight:600;font-family:inherit;flex:1;"><i class="fas fa-save"></i> Save Changes</button>
                                    <button type="button" onclick="toggleEdit(<?php echo $gig['id']; ?>)" style="background:transparent;border:1px solid var(--border-color);color:var(--text-main);border-radius:8px;padding:.75rem 1.5rem;cursor:pointer;font-weight:600;font-family:inherit;">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>
<script>function toggleEdit(id){const p=document.getElementById('edit-panel-'+id);if(!p)return;p.classList.toggle('open');if(p.classList.contains('open'))p.scrollIntoView({behavior:'smooth',block:'nearest'});}</script>
<script src="js/student.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
