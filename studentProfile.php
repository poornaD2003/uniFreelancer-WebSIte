<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php'; 
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error_msg = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 📸 NEW: PROFILE PICTURE UPLOAD LOGIC
    if (isset($_POST['upload_image'])) {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $file = $_FILES['profile_image'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExt, $allowedExtensions)) {
                if ($fileSize < 5000000) { // Max 5MB
                    // Unique නමක් සෑදීම (පරණ duplicate path ප්‍රශ්න මඟහරවා ගැනීමට)
                    $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;
                    
                    // Upload වන Folder එක නිවැරදිව තැබීම
                    $uploadDirectory = 'uploads/';
                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0777, true);
                    }
                    
                    $fileDestination = $uploadDirectory . $newFileName;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        // Database එකේ save කරන්නේ පිරිසිදු file name එක පමණි
                        $stmtImg = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                        $stmtImg->bind_param("si", $newFileName, $user_id);
                        
                        if ($stmtImg->execute()) {
                            // 💡 වැදගත්ම කොටස: Header එකට ක්ෂණිකව පෙනීමට Session එක Update කිරීම
                            $_SESSION['profile_pic'] = $newFileName;
                            $msg = "Profile picture updated successfully!";
                        } else {
                            $error_msg = "Database update failed.";
                        }
                        $stmtImg->close();
                    } else {
                        $error_msg = "Failed to move uploaded file.";
                    }
                } else {
                    $error_msg = "Your file is too large. Max size is 5MB.";
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
            }
        } else {
            $error_msg = "Please select a valid image file to upload.";
        }
    }
    
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $uni_name = $_POST['university_name'] ?? '';
        $faculty = $_POST['faculty'] ?? '';
        $dept = $_POST['department'] ?? '';
        $clubs = trim($_POST['club_affiliations']);

        $stmt1 = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt1->bind_param("si", $fullname, $user_id);
        $stmt1->execute();
        $stmt1->close();
        
        // Header එකේ fullname එකත් update වීමට
        $_SESSION['fullname'] = $fullname;

        $stmt2 = $conn->prepare("INSERT INTO student_profiles (user_id, university_name, faculty, department, club_affiliations) 
                                VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE university_name = ?, faculty = ?, department = ?, club_affiliations = ?");
        $stmt2->bind_param("issssssss", $user_id, $uni_name, $faculty, $dept, $clubs, $uni_name, $faculty, $dept, $clubs);
        $stmt2->execute();
        $stmt2->close();
        
        // Fetch current profile to check if they are already in a club
        $stmtProfileCheck = $conn->prepare("SELECT club_id, club_affiliations FROM student_profiles WHERE user_id = ? LIMIT 1");
        $club_id = null;
        $club_affiliations = "";
        
        if ($stmtProfileCheck) {
            $stmtProfileCheck->bind_param("i", $user_id);
            $stmtProfileCheck->execute();
            $current_profile = $stmtProfileCheck->get_result()->fetch_assoc();
            $stmtProfileCheck->close();
            
            if ($current_profile) {
                $club_id = $current_profile['club_id'];
                $club_affiliations = $current_profile['club_affiliations'];
            }
        }

        $club_update_success = true;

        if (isset($_POST['leave_club']) && $_POST['leave_club'] == '1') {
            $club_id = null;
            $club_affiliations = '';
        } elseif ($club_id === null && isset($_POST['club_id']) && $_POST['club_id'] !== "") {
            $join_club_id = (int)$_POST['club_id'];
            $join_club_code = isset($_POST['club_code']) ? trim($_POST['club_code']) : "";

            $club_check = $conn->prepare("SELECT club_name, club_code FROM clubs WHERE id = ? AND status = 'approved' LIMIT 1");
            if ($club_check) {
                $club_check->bind_param("i", $join_club_id);
                $club_check->execute();
                $club_res = $club_check->get_result()->fetch_assoc();
                $club_check->close();

                if (!$club_res) {
                    $error_msg = "Selected club is invalid or not approved yet.";
                    $club_update_success = false;
                } elseif ($club_res['club_code'] !== $join_club_code) {
                    $error_msg = "Incorrect secret access code for the selected club. The correct code is: " . htmlspecialchars($club_res['club_code']);
                    $club_update_success = false;
                } else {
                    $club_id = $join_club_id;
                    $club_affiliations = $club_res['club_name'];
                }
            } else {
                $error_msg = "Failed to query club database.";
                $club_update_success = false;
            }
        }

        if ($club_update_success) {
            $stmt1 = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
            $stmt1->bind_param("si", $fullname, $user_id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("INSERT INTO student_profiles (user_id, university_name, faculty, department, club_id, club_affiliations) 
                                    VALUES (?, ?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE university_name = ?, faculty = ?, department = ?, club_id = ?, club_affiliations = ?");
            $stmt2->bind_param("isssissssis", $user_id, $uni_name, $faculty, $dept, $club_id, $club_affiliations, $uni_name, $faculty, $dept, $club_id, $club_affiliations);
            $stmt2->execute();
            $stmt2->close();
            
            $msg = "Profile updated successfully!";
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $hashed_pw, $user_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $msg = "Password changed successfully!";
        } else {
            $error_msg = "Incorrect current password.";
        }
    }

    if (isset($_POST['add_skill'])) {
        $skill = trim($_POST['skill_name']);
        if (!empty($skill)) {
            $stmt = $conn->prepare("INSERT INTO student_skills (user_id, skill_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $skill);
            $stmt->execute();
            $stmt->close();
            $msg = "Skill added successfully!";
        }
    }

    if (isset($_POST['delete_skill'])) {
        $skill_id = intval($_POST['skill_id']);
        $stmt = $conn->prepare("DELETE FROM student_skills WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $skill_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Skill deleted successfully!";
    }
}

$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user_data = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$stmtProfile = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmtProfile->bind_param("i", $user_id);
$stmtProfile->execute();
$profile_data = $stmtProfile->get_result()->fetch_assoc();
$stmtProfile->close();

if (!$profile_data) {
    $profile_data = ['university_name'=>'', 'faculty'=>'', 'department'=>'', 'club_id'=>null, 'club_affiliations'=>''];
}

$current_club_name = "";
if (!empty($profile_data['club_id'])) {
    $club_q = $conn->prepare("SELECT club_name FROM clubs WHERE id = ?");
    if ($club_q) {
        $club_q->bind_param("i", $profile_data['club_id']);
        $club_q->execute();
        $club_res = $club_q->get_result()->fetch_assoc();
        if ($club_res) {
            $current_club_name = $club_res['club_name'];
        }
        $club_q->close();
    }
}

$stmtSkills = $conn->prepare("SELECT * FROM student_skills WHERE user_id = ?");
$stmtSkills->bind_param("i", $user_id);
$stmtSkills->execute();
$skills_result = $stmtSkills->get_result();
$skills = $skills_result->fetch_all(MYSQLI_ASSOC);
$stmtSkills->close();

$universities = [
    "University of Colombo", "University of Kelaniya", "University of Peradeniya",
    "University of Sri Jayewardenepura", "University of Moratuwa", "University of Ruhuna",
    "University of Jaffna", "Eastern University, Sri Lanka", "Wayamba University of Sri Lanka",
    "Sabaragamuwa University of Sri Lanka", "Uva Wellassa University", "South Eastern University of Sri Lanka",
    "University of Visual and Performing Arts", "NSBM Green University Town",
    "SLIIT (Sri Lanka Institute of Information Technology)", "Kotelawala Defence University"
];
$saved_university = $profile_data['university_name'] ?? '';

$faculties = [
    "Faculty of Science", "Faculty of Arts", "Faculty of Computing", 
    "Faculty of Engineering", "Faculty of Management and Finance"
];
$saved_faculty = $profile_data['faculty'] ?? '';
$saved_department = $profile_data['department'] ?? '';

// Current UI Display Image URL සෑදීම
if (!empty($user_data['profile_pic']) && $user_data['profile_pic'] !== 'default.png') {
    $display_pic = '/unilance/uploads/' . basename($user_data['profile_pic']);
} else {
    $display_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
}
?>

<div class="container card fade-in" style="max-width: 750px; margin: 140px auto 40px; padding: 2.5rem;">
    <h2 style="margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">Student Profile Management</h2>
    
    <?php if(!empty($msg)): ?>
        <div class="success-badge" style="display: block; width: 100%; padding: 0.6rem 1rem; margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div class="success-badge" style="display: block; width: 100%; padding: 0.6rem 1rem; margin-bottom: 1.5rem; color: #ef4444; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2);">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 2rem;">
        <h3 style="color: var(--primary); align-self: flex-start; margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Profile Picture</h3>
        
        <img src="<?php echo htmlspecialchars($display_pic); ?>" 
             alt="Current Profile Picture" 
             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary, #7c3aed); margin-bottom: 1rem;"
             onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
        
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; width: 100%; max-width: 320px;">
            <input type="file" name="profile_image" accept="image/png, image/jpeg, image/jpg" required style="font-size: 0.9rem; color: var(--text-muted);">
            <button type="submit" name="upload_image" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 0.5rem;">Upload New Image</button>
        </form>
    </div>

    <form method="POST" style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Academic & Personal Info</h3>
        
        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>" required>
        </div>
        
        <div class="input-group">
            <label>University Name</label>
            <select name="university_name" id="university_name" required>
                <option value="">-- Select University --</option>
                <?php foreach ($universities as $uni): ?>
                    <option value="<?php echo htmlspecialchars($uni); ?>" <?php echo ($saved_university === $uni) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($uni); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="input-group">
            <label>Faculty</label>
            <select name="faculty" id="faculty" required>
                <option value="">-- Select Faculty --</option>
                <?php foreach ($faculties as $fac): ?>
                    <option value="<?php echo htmlspecialchars($fac); ?>" <?php echo ($saved_faculty === $fac) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fac); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="input-group">
            <label>Department</label>
            <select name="department" id="department" required>
                <option value="">-- Select Department --</option>
            </select>
        </div>
        
        <?php if (!empty($profile_data['club_id'])): ?>
            <div class="input-group" style="margin-bottom: 1.5rem;">
                <label>Club Affiliation</label>
                <div style="background: rgba(255,255,255,0.03); padding: 1.2rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                    <div>
                        <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); display: block; margin-bottom: 2px;">Current Active Club</span>
                        <strong style="font-size: 1.05rem; color: var(--text-main);"><i class="fas fa-users" style="color: var(--primary); margin-right: 6px;"></i> <?php echo htmlspecialchars($current_club_name); ?></strong>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; color: #f87171; font-weight: 600; font-size: 0.85rem; margin-bottom: 0;">
                        <input type="checkbox" name="leave_club" value="1" style="width: auto; height: auto;"> Leave Club
                    </label>
                </div>
            </div>
        <?php else: ?>
            <div class="input-group" style="margin-bottom: 1.25rem;">
                <label for="club_id">Join Club / Society</label>
                <select name="club_id" id="club_id" style="width: 100%;">
                    <option value="">-- No Club (Independent Freelancer) --</option>
                    <?php
                    $clubs_res = mysqli_query($conn, "SELECT id, club_name FROM clubs WHERE status = 'approved' ORDER BY club_name ASC");
                    if ($clubs_res) {
                        while ($club = mysqli_fetch_assoc($clubs_res)) {
                            echo '<option value="' . htmlspecialchars($club['id']) . '">' . htmlspecialchars($club['club_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="input-group" id="club_code_group" style="display: none; margin-bottom: 1.5rem;">
                <label for="club_code">Secret Club Access Code</label>
                <input type="text" name="club_code" id="club_code" placeholder="Enter Club's Access Code">
                <small style="color: var(--text-muted); display: block; margin-top: 0.3rem; font-size: 0.75rem;">Enter the verification code to join this club.</small>
            </div>
        <?php endif; ?>
        
        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
    </form>

    <div style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">My Skills</h3>
        
        <div style="margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <?php if(empty($skills)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No skills added yet.</p>
            <?php else: ?>
                <?php foreach($skills as $skill): ?>
                    <span style="display: inline-flex; align-items: center; background: rgba(16, 185, 129, 0.08); color: var(--accent); padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(16, 185, 129, 0.15);">
                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                        <form method="POST" style="display: inline; margin-left: 8px;">
                            <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                            <button type="submit" name="delete_skill" style="border:none; background:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size: 1.1rem; line-height: 1; margin-left: 4px;">&times;</button>
                        </form>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="input-group" style="flex: 1; margin-bottom: 0;">
                <input type="text" name="skill_name" placeholder="e.g. PHP, Flutter, UI Design" required>
            </div>
            <button type="submit" name="add_skill" class="btn btn-primary" style="height: 44px;">Add</button>
        </form>
    </div>

    <form method="POST">
        <h3 style="color: var(--primary); margin-bottom: 1.25rem; font-size: 1.2rem; font-weight: 600;">Security Settings</h3>
        
        <div class="input-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        
        <button type="submit" name="change_password" class="btn btn-outline">Change Password</button>
    </form>
</div>

<script>
const departmentsByFaculty = {
    "Faculty of Science": [
        "Department of Chemistry", "Department of Physics", "Department of Mathematics",
        "Department of Plant Sciences", "Department of Zoology", "Department of Statistics"
    ],
    "Faculty of Arts": [
        "Department of Economics", "Department of English", "Department of Pali and Buddhist Studies",
        "Department of Philosophy", "Department of Political Science", "Department of Sociology",
        "Department of Geography", "Department of Archaeology"
    ],
    "Faculty of Computing": [
        "Department of Computer Science", "Department of Information Technology",
        "Department of Information Systems Engineering"
    ],
    "Faculty of Engineering": [
        "Department of Computer Science & Engineering",
        "Department of Electronic & Telecommunication Engineering",
        "Department of Civil Engineering", "Department of Mechanical Engineering"
    ],
    "Faculty of Management and Finance": [
        "Department of Accounting", "Department of Business Administration",
        "Department of Finance", "Department of Marketing Management"
    ]
};

document.addEventListener("DOMContentLoaded", function () {
    const facultySelect = document.getElementById("faculty");
    const departmentSelect = document.getElementById("department");
    const savedDepartment = <?php echo json_encode($saved_department ?? ''); ?>;

    if (facultySelect && departmentSelect) {
        function loadDepartments() {
            const selectedFaculty = facultySelect.value;
            departmentSelect.innerHTML = '<option value="">-- Select Department --</option>';

            if (selectedFaculty && departmentsByFaculty[selectedFaculty]) {
                departmentsByFaculty[selectedFaculty].forEach(function (dept) {
                    const option = document.createElement("option");
                    option.value = dept;
                    option.textContent = dept;
                    
                    if (dept === savedDepartment) {
                        option.selected = true;
                    }
                    departmentSelect.appendChild(option);
                });
            }
        }
        facultySelect.addEventListener("change", loadDepartments);
        loadDepartments();
    }

    const clubSelect = document.getElementById("club_id");
    const clubCodeGroup = document.getElementById("club_code_group");
    const clubCodeInput = document.getElementById("club_code");
    
    if (clubSelect && clubCodeGroup) {
        // Enforce state on page load/restore
        if (clubSelect.value !== "") {
            clubCodeGroup.style.display = "block";
            clubCodeInput.required = true;
        } else {
            clubCodeGroup.style.display = "none";
            clubCodeInput.required = false;
        }

        clubSelect.addEventListener("change", function () {
            if (this.value !== "") {
                clubCodeGroup.style.display = "block";
                clubCodeInput.required = true;
            } else {
                clubCodeGroup.style.display = "none";
                clubCodeInput.required = false;
                clubCodeInput.value = "";
            }
        });
    }
});
</script>

<?php 
 include 'includes/footer.php'; 
?>