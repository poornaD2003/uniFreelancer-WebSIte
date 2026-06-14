<?php
include 'includes/db.php';
include 'includes/header.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_job'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $student_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO gigs (student_id, title, description, price, category) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$student_id, $title, $description, $price, $category])) {
        $success = "Gig posted successfully! <a href='jobs.php' style='color: inherit; text-decoration: underline;'>View Gigs</a>";
    } else {
        $error = "Failed to post job. Please try again.";
    }
}
?>

<div class="form-container card fade-in">
    <h2 style="margin-bottom: 0.5rem; font-size: 2rem;">Post a New Gig</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Find the best student talent for your project.</p>

    <?php if($error): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239,68,68,0.5); padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #ff8a80;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div style="background: rgba(0, 230, 118, 0.12); border: 1px solid rgba(0,230,118,0.35); padding: 1rem; border-radius: 12px; margin-bottom: 1rem; color: #00e676;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="post-job.php">
        <div class="input-group">
            <label>Job Title</label>
            <input type="text" name="title" required placeholder="e.g. Design a Modern Landing Page">
        </div>
        <div class="input-group">
            <label>Category</label>
            <select name="category" required>
                <option value="Development">Development</option>
                <option value="Design">Design</option>
                <option value="Writing">Writing</option>
                <option value="Tutoring">Tutoring</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="input-group">
            <label>Budget ($)</label>
            <input type="number" name="budget" required placeholder="50.00">
        </div>
        <div class="input-group">
            <label>Description</label>
            <textarea name="description" rows="5" required placeholder="Describe the project requirements..."></textarea>
        </div>
        <button type="submit" name="post_job" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Post Project</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
