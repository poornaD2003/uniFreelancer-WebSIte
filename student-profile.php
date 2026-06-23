<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

$msg = ""; $error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname=$trim=$_POST['fullname']??''; $fullname=trim($fullname);
        $uni_name=$_POST['university_name']??''; $faculty=$_POST['faculty']??'';
        $dept=$_POST['department']??''; $clubs=trim($_POST['club_affiliations']??'');
        if (!empty($fullname)&&!empty($uni_name)&&!empty($faculty)&&!empty($dept)) {
            $s=$conn->prepare("UPDATE users SET fullname=? WHERE id=?");
            if($s){$s->bind_param("si",$fullname,$user_id);$s->execute();$s->close();}
            $s=$conn->prepare("INSERT INTO student_profiles (user_id,university_name,faculty,department,club_affiliations) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE university_name=?,faculty=?,department=?,club_affiliations=?");
            if($s){$s->bind_param("issssssss",$user_id,$uni_name,$faculty,$dept,$clubs,$uni_name,$faculty,$dept,$clubs);$s->execute();$s->close();}
            $msg="Profile updated successfully!";
        } else { $error_msg="Please fill in all required fields."; }
    }
    if (isset($_POST['change_password'])) {
        $cur=$_POST['current_password']??''; $new=$_POST['new_password']??'';
        if(!empty($cur)&&!empty($new)){
            $s=$conn->prepare("SELECT password FROM users WHERE id=?");
            if($s){$s->bind_param("i",$user_id);$s->execute();$u=$s->get_result()->fetch_assoc();$s->close();
                if($u&&password_verify($cur,$u['password'])){
                    $h=password_hash($new,PASSWORD_BCRYPT);
                    $s=$conn->prepare("UPDATE users SET password=? WHERE id=?");
                    if($s){$s->bind_param("si",$h,$user_id);$s->execute();$s->close();}
                    $msg="Password changed successfully!";
                } else { $error_msg="Incorrect current password."; }
            }
        } else { $error_msg="Both fields are required."; }
    }
    if (isset($_POST['add_skill'])) {
        $skill=trim($_POST['skill_name']??'');
        if(!empty($skill)){$s=$conn->prepare("INSERT INTO student_skills (user_id,skill_name) VALUES(?,?)");if($s){$s->bind_param("is",$user_id,$skill);$s->execute();$s->close();$msg="Skill added!";}}
    }
    if (isset($_POST['delete_skill'])) {
        $sid=(int)$_POST['skill_id'];$s=$conn->prepare("DELETE FROM student_skills WHERE id=? AND user_id=?");if($s){$s->bind_param("ii",$sid,$user_id);$s->execute();$s->close();$msg="Skill deleted.";}
    }
}

$user_data=[]; $s=$conn->prepare("SELECT * FROM users WHERE id=?");
if($s){$s->bind_param("i",$user_id);$s->execute();$user_data=$s->get_result()->fetch_assoc();$s->close();}

$profile_data=[]; $s=$conn->prepare("SELECT * FROM student_profiles WHERE user_id=?");
if($s){$s->bind_param("i",$user_id);$s->execute();$profile_data=$s->get_result()->fetch_assoc();$s->close();}
if(!$profile_data) $profile_data=['university_name'=>'','faculty'=>'','department'=>'','club_affiliations'=>''];

$skills=[];
$s=$conn->prepare("SELECT * FROM student_skills WHERE user_id=?");
if($s!==false){$s->bind_param("i",$user_id);$s->execute();$res=$s->get_result();while($r=$res->fetch_assoc())$skills[]=$r;$s->close();}

$universities=["University of Colombo","University of Kelaniya","University of Peradeniya","University of Sri Jayewardenepura","University of Moratuwa","University of Ruhuna","University of Jaffna","Eastern University, Sri Lanka","Wayamba University of Sri Lanka","Sabaragamuwa University of Sri Lanka","Uva Wellassa University","South Eastern University of Sri Lanka","University of Visual and Performing Arts","NSBM Green University Town","SLIIT (Sri Lanka Institute of Information Technology)","Kotelawala Defence University"];
$faculties=["Faculty of Science","Faculty of Arts","Faculty of Computing","Faculty of Engineering","Faculty of Management and Finance"];
$saved_university=$profile_data['university_name']??'';
$saved_faculty=$profile_data['faculty']??'';
$saved_department=$profile_data['department']??'';
?>
<link rel="stylesheet" href="css/student.css">
<div class="container card fade-in" style="max-width:750px;margin:140px auto 40px;padding:2.5rem;background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;">
    <h2 style="margin-bottom:1.5rem;font-size:1.8rem;font-weight:700;color:#fff;">Student Profile Management</h2>
    <a href="student-dashboard.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--primary);text-decoration:none;font-weight:500;font-size:0.95rem;margin-bottom:1.5rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <?php if(!empty($msg)): ?><div class="status-alert" style="padding:.75rem 1rem;margin-bottom:1.5rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:var(--primary);border-radius:8px;"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if(!empty($error_msg)): ?><div class="status-alert" style="padding:.75rem 1rem;margin-bottom:1.5rem;color:#ef4444;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

    <form method="POST" style="margin-bottom:2.5rem;border-bottom:1px solid var(--border-color);padding-bottom:2rem;">
        <h3 style="color:var(--primary);margin-bottom:1.25rem;font-size:1.2rem;font-weight:600;">Academic &amp; Personal Info</h3>
        <div class="input-group"><label>Full Name</label><input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname']??''); ?>" required placeholder="Your full name"></div>
        <div class="input-group"><label>University</label><select name="university_name" required><option value="">-- Select University --</option><?php foreach($universities as $u): ?><option value="<?php echo htmlspecialchars($u); ?>"<?php echo $saved_university===$u?' selected':''; ?>><?php echo htmlspecialchars($u); ?></option><?php endforeach; ?></select></div>
        <div class="input-group"><label>Faculty</label><select name="faculty" id="faculty" required><option value="">-- Select Faculty --</option><?php foreach($faculties as $f): ?><option value="<?php echo htmlspecialchars($f); ?>"<?php echo $saved_faculty===$f?' selected':''; ?>><?php echo htmlspecialchars($f); ?></option><?php endforeach; ?></select></div>
        <div class="input-group"><label>Department</label><select name="department" id="department" required><option value="">-- Select Department --</option></select></div>
        <div class="input-group"><label>Club Affiliations</label><textarea name="club_affiliations" style="min-height:90px;resize:vertical;"><?php echo htmlspecialchars($profile_data['club_affiliations']??''); ?></textarea></div>
        <button type="submit" name="update_profile" style="width:100%;">Save Changes</button>
    </form>

    <div style="margin-bottom:2.5rem;border-bottom:1px solid var(--border-color);padding-bottom:2rem;">
        <h3 style="color:var(--primary);margin-bottom:1.25rem;font-size:1.2rem;font-weight:600;">My Skills</h3>
        <div class="skills-container">
            <?php if(empty($skills)): ?><p style="color:var(--text-muted);font-size:.9rem;">No skills added yet.</p>
            <?php else: foreach($skills as $skill): ?>
                <span class="skill-tag"><?php echo htmlspecialchars($skill['skill_name']); ?><form method="POST" style="display:inline;margin-left:8px;"><input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>"><button type="submit" name="delete_skill">&times;</button></form></span>
            <?php endforeach; endif; ?>
        </div>
        <form method="POST" style="display:flex;flex-direction:row;gap:1rem;align-items:flex-end;">
            <div class="input-group" style="flex:1;margin-bottom:0;"><input type="text" name="skill_name" placeholder="e.g. PHP, Flutter, UI Design" required></div>
            <button type="submit" name="add_skill" style="height:44px;margin-top:0;padding:0 1.5rem;">Add</button>
        </form>
    </div>

    <form method="POST">
        <h3 style="color:var(--primary);margin-bottom:1.25rem;font-size:1.2rem;font-weight:600;">Security Settings</h3>
        <div class="input-group"><label>Current Password</label><input type="password" name="current_password" required placeholder="••••••••"></div>
        <div class="input-group"><label>New Password</label><input type="password" name="new_password" required placeholder="••••••••"></div>
        <button type="submit" name="change_password" style="width:100%;background:transparent;border:1px solid var(--primary);color:var(--primary);">Change Password</button>
    </form>
</div>
<script>
const departmentsByFaculty={
    "Faculty of Science":["Department of Chemistry","Department of Physics","Department of Mathematics","Department of Plant Sciences","Department of Zoology","Department of Statistics"],
    "Faculty of Arts":["Department of Economics","Department of English","Department of Pali and Buddhist Studies","Department of Philosophy","Department of Political Science","Department of Sociology","Department of Geography","Department of Archaeology"],
    "Faculty of Computing":["Department of Computer Science","Department of Information Technology","Department of Information Systems Engineering"],
    "Faculty of Engineering":["Department of Computer Science & Engineering","Department of Electronic & Telecommunication Engineering","Department of Civil Engineering","Department of Mechanical Engineering"],
    "Faculty of Management and Finance":["Department of Accounting","Department of Business Administration","Department of Finance","Department of Marketing Management"]
};
document.addEventListener("DOMContentLoaded",function(){
    const fSel=document.getElementById("faculty"),dSel=document.getElementById("department"),saved=<?php echo json_encode($saved_department??''); ?>;
    if(fSel&&dSel){
        function loadDepts(){
            dSel.innerHTML='<option value="">-- Select Department --</option>';
            const f=fSel.value;
            if(f&&departmentsByFaculty[f]) departmentsByFaculty[f].forEach(d=>{const o=document.createElement("option");o.value=d;o.textContent=d;if(d===saved)o.selected=true;dSel.appendChild(o);});
        }
        fSel.addEventListener("change",loadDepts);loadDepts();
    }
});
</script>
<script src="js/student.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
