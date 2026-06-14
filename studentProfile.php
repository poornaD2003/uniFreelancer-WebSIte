<?php
// 1. Correct the paths to match your project folder structure
include 'includes/header.php';

// 2. Ensure session security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// 3. Handle Form Submissions safely with PDO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Academic & Personal Details
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $uni_name = $_POST['university_name'];
        $faculty = $_POST['faculty'];
        $dept = $_POST['department'];
        $clubs = trim($_POST['club_affiliations']);

        // Update core users table
        $stmt1 = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt1->execute([$fullname, $user_id]);

        // Upsert student specific profile info
        $stmt2 = $pdo->prepare("INSERT INTO student_profiles (user_id, university_name, faculty, department, club_affiliations) 
                                VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE university_name = ?, faculty = ?, department = ?, club_affiliations = ?");
        $stmt2->execute([$user_id, $uni_name, $faculty, $dept, $clubs, $uni_name, $faculty, $dept, $clubs]);
        $msg = "Profile updated successfully!";
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$hashed_pw, $user_id]);
            $msg = "Password changed successfully!";
        } else {
            $msg = "Incorrect current password.";
        }
    }

    // Add a Skill
    if (isset($_POST['add_skill'])) {
        $skill = trim($_POST['skill_name']);
        if (!empty($skill)) {
            $stmt = $pdo->prepare("INSERT INTO student_skills (user_id, skill_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $skill]);
        }
    }

    // Delete a Skill
    if (isset($_POST['delete_skill'])) {
        $skill_id = intval($_POST['skill_id']);
        $stmt = $pdo->prepare("DELETE FROM student_skills WHERE id = ? AND user_id = ?");
        $stmt->execute([$skill_id, $user_id]);
    }
}

// 4. Fetch the existing records to populate form inputs
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user_data = $stmtUser->fetch();

$stmtProfile = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmtProfile->execute([$user_id]);
$profile_data = $stmtProfile->fetch();

// Fallback values if profile description is not generated yet
if (!$profile_data) {
    $profile_data = ['university_name'=>'', 'faculty'=>'', 'department'=>'', 'club_affiliations'=>''];
}

// Fetch skills
$stmtSkills = $pdo->prepare("SELECT * FROM student_skills WHERE user_id = ?");
$stmtSkills->execute([$user_id]);
$skills = $stmtSkills->fetchAll();

$universities = [
    "University of Colombo", "University of Kelaniya", "University of Peradeniya",
    "University of Sri Jayewardenepura", "University of Moratuwa", "University of Ruhuna",
    "University of Jaffna", "Eastern University, Sri Lanka", "Wayamba University of Sri Lanka",
    "Sabaragamuwa University of Sri Lanka", "Uva Wellassa University", "South Eastern University of Sri Lanka",
    "University of Visual and Performing Arts", "NSBM Green University Town",
    "SLIIT (Sri Lanka Institute of Information Technology)", "Kotelawala Defence University"
];
$saved_university = $profile_data['university_name'] ?? '';

// PHP arrays defined to match our JS configuration
$faculties = [
    "Faculty of Science", "Faculty of Arts", "Faculty of Computing", 
    "Faculty of Engineering", "Faculty of Management and Finance"
];
$saved_faculty = $profile_data['faculty'] ?? '';
$saved_department = $profile_data['department'] ?? '';
?>

<div class="container card fade-in" style="max-width: 800px; margin: 40px auto; padding: 20px; color: white;">
    <h2>Student Profile Management</h2>
    <?php if(!empty($msg)): ?><p style="color: #2ecc71; font-weight: bold;"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>

    <form method="POST" style="background: var(--glass-bg); padding: 20px; margin-bottom: 30px; border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Academic & Personal Info</h3>
        
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
        
        <div class="input-group">
            <label>Club Affiliations</label>
            <textarea name="club_affiliations" style="width:100%; min-height: 80px; padding:12px; border-radius:8px; background: rgba(255,255,255,0.05); color:white; border: 1px solid var(--glass-border);"><?php echo htmlspecialchars($profile_data['club_affiliations'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
    </form>

    <div style="background: var(--glass-bg); padding: 20px; margin-bottom: 30px; border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">My Skills</h3>
        <div style="margin-bottom: 15px;">
            <?php foreach($skills as $skill): ?>
                <span style="display: inline-block; background: var(--primary); color: white; padding: 6px 12px; margin: 4px; border-radius: 20px; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                    <form method="POST" style="display: inline; margin-left: 8px;">
                        <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                        <button type="submit" name="delete_skill" style="border:none; background:none; color:white; font-weight:bold; cursor:pointer;">&times;</button>
                    </form>
                </span>
            <?php endforeach; ?>
        </div>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
            <div class="input-group" style="flex: 1; margin-bottom:0;">
                <input type="text" name="skill_name" placeholder="e.g. PHP, Flutter, UI Design" required>
            </div>
            <button type="submit" name="add_skill" class="btn btn-primary" style="height: 46px;">Add Skill</button>
        </form>
    </div>

    <form method="POST" style="background: var(--glass-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Security Settings</h3>
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
        "Department of Chemistry",
        "Department of Physics",
        "Department of Mathematics",
        "Department of Plant Sciences",
        "Department of Zoology",
        "Department of Statistics"
    ],
    "Faculty of Arts": [
        "Department of Economics",
        "Department of English",
        "Department of Pali and Buddhist Studies",
        "Department of Philosophy",
        "Department of Political Science",
        "Department of Socialogy",
        "Department of Geography",
        "Department of Archaeology"
    ],
    "Faculty of Computing": [
        "Department of Computer Science",
        "Department of Information Technology",
        "Department of Information Systems Engineering"
    ],
    "Faculty of Engineering": [
        "Department of Computer Science & Engineering",
        "Department of Electronic & Telecommunication Engineering",
        "Department of Civil Engineering",
        "Department of Mechanical Engineering"
    ],
    "Faculty of Management and Finance": [
        "Department of Accounting",
        "Department of Business Administration",
        "Department of Finance",
        "Department of Marketing Management"
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

        facultySelect.addEventListener("change", function () {
            loadDepartments();
        });

        loadDepartments();
    }
});
</script>

<?php include 'includes/footer.php'; ?>