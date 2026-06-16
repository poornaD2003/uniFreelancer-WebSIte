<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';       
include 'includes/header.php';

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_student_details'])) {
    $user_id           = (int) $_POST['user_id'];
    $university_name  = trim($_POST['university_name']);
    $faculty          = trim($_POST['faculty']);
    $department       = trim($_POST['department']);
    $club_affiliations = trim($_POST['club_affiliations'] ?? '');

    if (empty($university_name) || empty($faculty) || empty($department)) {
        $error = "Please fill in all required university fields.";
        $show_student_step2 = true;
        $step2_user_id = $user_id;
    } else {
        $query = "INSERT INTO student_profiles (user_id, university_name, faculty, department, club_affiliations)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                    university_name   = ?,
                    faculty           = ?,
                    department        = ?,
                    club_affiliations = ?";
                    
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "issssssss", 
                $user_id, $university_name, $faculty, $department, $club_affiliations, 
                $university_name, $faculty, $department, $club_affiliations     
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration complete! Your profile has been submitted for review. Once the administrator approves your account, you will be able to log in.";
            } else {
                $error = "Something went wrong saving your university details. Please try again.";
                $show_student_step2 = true;
                $step2_user_id = $user_id;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_client_details'])) {
    $user_id          = (int) $_POST['user_id'];
    $business_name    = trim($_POST['business_name']);
    $business_type    = trim($_POST['business_type']);
    $business_phone   = trim($_POST['business_phone']);
    $business_address = trim($_POST['business_address'] ?? '');

    if (empty($business_name) || empty($business_type) || empty($business_phone)) {
        $error = "Please fill in all required business fields.";
        $show_client_step2 = true;
        $step2_user_id = $user_id;
    } else {
        $query = "INSERT INTO client_profiles (user_id, business_name, business_type, business_phone, business_address)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                    business_name    = ?,
                    business_type    = ?,
                    business_phone   = ?,
                    business_address = ?";
                    
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "issssssss", 
                $user_id, $business_name, $business_type, $business_phone, $business_address,
                $business_name, $business_type, $business_phone, $business_address
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration complete! Your business profile has been submitted for review. Once the administrator approves your account, you will be able to log in.";
            } else {
                $error = "Something went wrong saving your business details. Please try again.";
                $show_client_step2 = true;
                $step2_user_id = $user_id;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role     = $_POST['role'];

    $check_query = "SELECT id FROM users WHERE email = ?";
    if ($check_stmt = mysqli_prepare($conn, $check_query)) {
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt); 
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email already exists!";
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);
            
            $insert_query = "INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, 'pending')";
            if ($insert_stmt = mysqli_prepare($conn, $insert_query)) {
                mysqli_stmt_bind_param($insert_stmt, "ssss", $fullname, $email, $password, $role);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $new_user_id = mysqli_insert_id($conn);
                    $step2_user_id = $new_user_id;

                    if ($role === 'student') {
                        $show_student_step2 = true;
                    } else {
                        $show_client_step2 = true;
                    }
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}
?>

<style>
.step-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 2rem;
}
.step-bubble {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 2px solid var(--primary, #7c3aed);
    color: var(--primary, #7c3aed);
    background: transparent;
    position: relative;
    z-index: 1;
}
.step-bubble.active {
    background: var(--primary, #7c3aed);
    color: #fff;
    box-shadow: 0 0 14px rgba(124,58,237,0.5);
}
.step-bubble.done {
    background: var(--primary, #7c3aed);
    color: #fff;
    opacity: 0.6;
}
.step-line {
    flex: 1;
    height: 2px;
    background: var(--primary, #7c3aed);
    opacity: 0.3;
    max-width: 80px;
}
.step-label {
    font-size: 0.72rem;
    color: var(--text-muted, #aaa);
    margin-top: 0.3rem;
    text-align: center;
}
.step-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.section-divider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 1.5rem 0 1rem;
    color: var(--text-muted, #aaa);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.section-divider::before,
.section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.1);
}

.label-optional {
    font-size: 0.7rem;
    background: rgba(124,58,237,0.2);
    color: var(--primary, #7c3aed);
    border-radius: 20px;
    padding: 1px 8px;
    margin-left: 6px;
    vertical-align: middle;
    font-weight: 500;
}

.role-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 0.5rem;
}
.role-card input[type="radio"] { display: none; }
.role-card label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    padding: 1rem 0.5rem;
    border-radius: 12px;
    border: 2px solid rgba(255,255,255,0.1);
    cursor: pointer;
    transition: all 0.25s ease;
    font-size: 0.85rem;
    color: var(--text-muted, #aaa);
}
.role-card label .role-icon { font-size: 1.6rem; }
.role-card label .role-title { font-weight: 600; color: var(--text, #fff); font-size: 0.9rem; }
.role-card input[type="radio"]:checked + label {
    border-color: var(--primary, #7c3aed);
    background: rgba(124,58,237,0.15);
    color: var(--primary, #7c3aed);
    box-shadow: 0 0 14px rgba(124,58,237,0.2);
}
.role-card label:hover {
    border-color: rgba(124,58,237,0.5);
    background: rgba(124,58,237,0.07);
}
</style>

<div class="form-container card fade-in">

    <?php if ($error): ?>
        <div style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #fca5a5;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: rgba(16,185,129,0.2); border: 1px solid #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #6ee7b7;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!$success && !isset($show_student_step2) && !isset($show_client_step2)): ?>
    <div class="step-progress">
        <div class="step-wrapper">
            <div class="step-bubble active">1</div>
            <div class="step-label">Account</div>
        </div>
        <div class="step-line"></div>
        <div class="step-wrapper">
            <div class="step-bubble">2</div>
            <div class="step-label">Details</div>
        </div>
    </div>

    <h2 style="margin-bottom: 0.5rem; font-size: 1.9rem;">Create Account</h2>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Join the UniLance community today.</p>

    <form method="POST" action="register.php" id="step1-form">
        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="fullname" required placeholder="John Doe" id="fullname" value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">
        </div>
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@university.edu" id="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••" id="password">
        </div>

        <div class="input-group">
            <label>I am a:</label>
            <div class="role-cards">
                <div class="role-card">
                    <input type="radio" name="role" id="role_student" value="student" checked>
                    <label for="role_student">
                        <span class="role-icon">🎓</span>
                        <span class="role-title">Student</span>
                        <span>Freelancer</span>
                    </label>
                </div>
                <div class="role-card">
                    <input type="radio" name="role" id="role_client" value="client">
                    <label for="role_client">
                        <span class="role-icon">💼</span>
                        <span class="role-title">Client</span>
                        <span>Hiring</span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" name="register" id="btn-next" class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 1.5rem;">
            Continue &rarr;
        </button>
    </form>
    
    <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
        Already have an account? <a href="login.php" style="color: var(--primary);">Login</a>
    </p>
    <?php endif; ?>


    <?php if (isset($show_student_step2)): ?>
    <div class="step-progress">
        <div class="step-wrapper">
            <div class="step-bubble done">✓</div>
            <div class="step-label">Account</div>
        </div>
        <div class="step-line" style="opacity:0.7;"></div>
        <div class="step-wrapper">
            <div class="step-bubble active">2</div>
            <div class="step-label">University</div>
        </div>
    </div>

    <h2 style="margin-bottom: 0.5rem; font-size: 1.9rem;">University Details</h2>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Tell us about your academic background.</p>

    <form method="POST" action="register.php" id="step2-student-form">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($step2_user_id); ?>">

        <div class="section-divider">🏫 Academic Information</div>

        <div class="input-group">
            <label for="university_name">University Name</label>
            <select name="university_name" id="university_name" required>
                <option value="">Select University</option>
                <option value="University of Colombo">University of Colombo</option>
                <option value="University of Kelaniya">University of Kelaniya</option>
                <option value="University of Peradeniya">University of Peradeniya</option>
                <option value="University of Sri Jayewardenepura">University of Sri Jayewardenepura</option>
                <option value="University of Moratuwa">University of Moratuwa</option>
                <option value="University of Ruhuna">University of Ruhuna</option>
                <option value="University of Jaffna">University of Jaffna</option>
                <option value="Eastern University, Sri Lanka">Eastern University, Sri Lanka</option>
                <option value="Wayamba University of Sri Lanka">Wayamba University of Sri Lanka</option>
                <option value="Sabaragamuwa University of Sri Lanka">Sabaragamuwa University of Sri Lanka</option>
                <option value="Uva Wellassa University">Uva Wellassa University</option>
                <option value="South Eastern University of Sri Lanka">South Eastern University of Sri Lanka</option>
                <option value="University of Visual and Performing Arts">University of Visual and Performing Arts</option>
                <option value="NSBM Green University Town">NSBM Green University Town</option>
                <option value="SLIIT (Sri Lanka Institute of Information Technology)">SLIIT (Sri Lanka Institute of Information Technology)</option>
                <option value="Kotelawala Defence University">Kotelawala Defence University</option>
            </select>
        </div>

        <div class="input-group">
            <label for="faculty">Faculty</label>
            <select name="faculty" id="faculty" required>
                <option value="">Select Faculty</option>
                <option value="Faculty of Science">Faculty of Science</option>
                <option value="Faculty of Computing">Faculty of Computing</option>
                <option value="Faculty of Management and Finance">Faculty of Management and Finance</option>
                <option value="Faculty of Engineering">Faculty of Engineering</option>
                <option value="Faculty of Arts">Faculty of Arts</option>
            </select>
        </div>

        <div class="input-group">
            <label for="department">Department</label>
            <select name="department" id="department" required>
                <option value="">Select Department</option>
            </select>
        </div>

        <div class="section-divider"> Club Affiliations <span class="label-optional">Optional</span></div>

        <div class="input-group">
            <label for="club_affiliations">
                Club / Society Memberships <span class="label-optional">Optional</span>
            </label>
            <input type="text" name="club_affiliations" id="club_affiliations"
                   placeholder="e.g. Rotaract Club, IEEE Student Branch, Drama Society">
            <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.3rem; display:block;">
                Separate multiple clubs with commas.
            </small>
        </div>

        <button type="submit" name="register_student_details" id="btn-finish-student"
                class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 1.5rem;">
            ✓ Submit For Approval
        </button>
    </form>

    <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
        Already have an account? <a href="login.php" style="color: var(--primary);">Login</a>
    </p>
    <?php endif; ?>


    <?php if (isset($show_client_step2)): ?>
    <div class="step-progress">
        <div class="step-wrapper">
            <div class="step-bubble done">✓</div>
            <div class="step-label">Account</div>
        </div>
        <div class="step-line" style="opacity:0.7;"></div>
        <div class="step-wrapper">
            <div class="step-bubble active">2</div>
            <div class="step-label">Business</div>
        </div>
    </div>

    <h2 style="margin-bottom: 0.5rem; font-size: 1.9rem;">Business Details</h2>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Tell us about your organization or enterprise.</p>

    <form method="POST" action="register.php" id="step2-client-form">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($step2_user_id); ?>">

        <div class="section-divider">💼 Company Information</div>

        <div class="input-group">
            <label for="business_name">Business / Enterprise Name</label>
            <input type="text" name="business_name" id="business_name" required placeholder="e.g. TechCorp Solutions">
        </div>

        <div class="input-group">
            <label for="business_type">Industry Type</label>
            <input type="text" name="business_type" id="business_type" required placeholder="e.g. Software, E-Commerce, Retail">
        </div>

        <div class="input-group">
            <label for="business_phone">Contact Phone Number</label>
            <input type="text" name="business_phone" id="business_phone" required placeholder="e.g. +94771234567" maxlength="10" inputmode="numeric">
        </div>

        <div class="input-group">
            <label for="business_address">Office Address <span class="label-optional">Optional</span></label>
            <input type="text" name="business_address" id="business_address" placeholder="e.g. Galle Road, Colombo 03">
        </div>

        <button type="submit" name="register_client_details" id="btn-finish-client"
                class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 1.5rem;">
            ✓ Submit For Approval
        </button>
    </form>

    <p style="margin-top: 2rem; text-align: center; color: var(--text-muted);">
        Already have an account? <a href="login.php" style="color: var(--primary);">Login</a>
    </p>
    <?php endif; ?>

</div>

<script>
// Dynamically update the button label based on role selection on Step 1
(function () {
    const radios = document.querySelectorAll('input[name="role"]');
    const btn    = document.getElementById('btn-next');
    if (!btn) return;

    radios.forEach(r => r.addEventListener('change', function () {
        btn.innerHTML = this.value === 'student' ? 'Continue &rarr;' : 'Continue &rarr;'; 
    }));
})();

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
    const business_phone = document.getElementById("business_phone");
    const clientForm =  document.getElementById("step2-client-form");

    if (clientForm && phoneInput) {
        clientForm.addEventListener("submit", function (event) {
            const phoneValue = phoneInput.value.trim();
            
            const srilankaPhoneRegex = /^07[012345678]\d{7}$/;

            if (!srilankaPhoneRegex.test(phoneValue)) {
                event.preventDefault(); 
                
                alert("Please enter a valid 10-digit Sri Lankan phone number starting with a valid prefix (Dialog, Mobitel, Hutch, Airtel).");
                
                phoneInput.focus();
                return false;
            }
        });

        phoneInput.addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    if (facultySelect && departmentSelect) {
        facultySelect.addEventListener("change", function () {
            const selectedFaculty = this.value;

            departmentSelect.innerHTML = '<option value="">Select Department</option>';

            if (selectedFaculty && departmentsByFaculty[selectedFaculty]) {
                departmentsByFaculty[selectedFaculty].forEach(function (dept) {
                    const option = document.createElement("option");
                    option.value = dept;
                    option.textContent = dept;
                    departmentSelect.appendChild(option);
                });
            } else if (!selectedFaculty) {
                departmentSelect.innerHTML = '<option value="">Select Faculty First</option>';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>