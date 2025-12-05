<?php
// student_dashboard.php
require_once 'auth_check.php';
requireStudent();
require_once 'config.php';

$student_number = $_SESSION['student_number'];
$message = "";
$message_type = "";

// --- 1. HANDLE PROFILE PICTURE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture']) && !empty($_FILES['profile_picture']['name'])) {
    $target_dir = "uploads/";
    $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = $student_number . '_' . time() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Allow only JPG
    if($imageFileType != "jpg") { 
        $message = "❌ Sorry, only JPG files are allowed.";
        $message_type = "error";
    } else {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            try {
                // Delete old image
                $stmt = $conn->prepare("SELECT ProfilePicture FROM StudentDetails WHERE StudentNumber = ?");
                $stmt->execute([$student_number]);
                $old_pic = $stmt->fetchColumn();
                if($old_pic && file_exists("uploads/" . $old_pic)) {
                    unlink("uploads/" . $old_pic);
                }

                // Update Database
                $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = ? WHERE StudentNumber = ?");
                $stmt->execute([$new_filename, $student_number]); 
                $message = "✅ Profile picture updated successfully.";
                $message_type = "success";
            } catch (PDOException $e) {
                $message = "❌ Database Error: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "❌ Error uploading file.";
            $message_type = "error";
        }
    }
}

// --- 2. HANDLE PROFILE PICTURE DELETION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture'])) {
    try {
        $stmt = $conn->prepare("SELECT ProfilePicture FROM StudentDetails WHERE StudentNumber = ?");
        $stmt->execute([$student_number]);
        $current_pic = $stmt->fetchColumn();

        if ($current_pic && file_exists("uploads/" . $current_pic)) {
            unlink("uploads/" . $current_pic);
        }

        $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = NULL WHERE StudentNumber = ?");
        $stmt->execute([$student_number]);

        $message = "🗑️ Profile picture removed. Default avatar restored.";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "❌ Error removing picture: " . $e->getMessage();
        $message_type = "error";
    }
}

// --- 3. HANDLE PROFILE INFO UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_contact = trim($_POST['contact_number']);
    $new_email   = trim($_POST['email']);
    $new_degree  = trim($_POST['degree']);
    $new_scholarship = trim($_POST['scholarship']); 

    try {
        $stmt = $conn->prepare("UPDATE StudentDetails SET ContactNumber=?, Email=?, DegreeProgram=?, Scholarship=? WHERE StudentNumber=?");
        $stmt->execute([$new_contact, $new_email, $new_degree, $new_scholarship, $student_number]);
        $message = "✅ Profile updated successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "❌ Error updating record.";
        $message_type = "error";
    }
}

// --- 4. HANDLE REQUIREMENT SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['requirement'])) {
    $target_dir = "uploads/";
    $fileType = strtolower(pathinfo($_FILES["requirement"]["name"], PATHINFO_EXTENSION));
    $new_filename = $student_number . '_requirement_' . time() . '.' . $fileType;
    $target_file = $target_dir . $new_filename;

    if($fileType != "pdf") {
        $message = "❌ Only PDF files are allowed.";
        $message_type = "error";
    } else {
        if(move_uploaded_file($_FILES["requirement"]["tmp_name"], $target_file)) {
            try {
                $stmt = $conn->prepare("UPDATE StudentDetails SET SubmissionProof = ? WHERE StudentNumber = ?");
                $stmt->execute([$new_filename, $student_number]);
                $message = "✅ Requirement submitted successfully.";
                $message_type = "success";
            } catch(PDOException $e) {
                $message = "❌ Database Error: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "❌ Error uploading PDF.";
            $message_type = "error";
        }
    }
}

// --- 5. FETCH STUDENT DETAILS ---
try {
    $stmt = $conn->prepare("SELECT * FROM StudentDetails WHERE StudentNumber = ?");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();
    if (!$student) die("Student record not found.");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// --- 6. MOCK DATA ---
$requirements = [['title' => 'Term 1 GPA Submission', 'date' => 'Dec 20, 2025', 'status' => 'Missing']];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Lasallian Scholars Society</title>
    <style>
        :root { --primary: #008259; --primary-dark: #006B4A; --text: #333; --danger: #dc3545; --danger-hover: #bb2d3b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: var(--text); background-image: url('Main%20Sub%20Page%20Background.gif'); }
        
        .navbar { background: var(--primary); color: #fcf9f4; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .logout-btn { background: rgba(255,255,255,0.2); color: #fcf9f4; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.85rem; }
        
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; }
        .card { background: #fcf9f4; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.5rem; }
        .card-body { padding: 1.5rem; }
        .card-header { padding: 1.25rem; border-bottom: 1px solid #eee; background: #fcf9f4; }
        .card-header h3 { font-size: 1.1rem; color: var(--primary-dark); margin: 0; }

        .profile-avatar { width: 90px; height: 90px; background: #e9ecef; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary); overflow: hidden; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .status-badge { padding: 0.35rem 1rem; border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .status-badge.status-ACTIVE { background: #d4edda; color: #155724; }
        .status-badge.status-Missing { background: #ffdddd; color: #842029; }
        
        /* HIDDEN SECTIONS: Edit forms and static details toggle visibility */
        .avatar-edit-area, .personal-details-editor, .scholarship-edit-area { display: none; }
        /* static-details is visible by default, so no display:none here */

        .form-label { display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 0.5rem; text-align: left; }
        .input-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-input { flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
        
        /* Static Details Styling */
        .detail-header { text-align: left; font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; }
        .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f0f0f0; padding: 0.75rem 0; font-size: 0.9rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; font-weight: 600; }
        .detail-value { color: #333; text-align: right; font-weight: 500;}

        .btn-submit { background: var(--primary); color: #fcf9f4; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-delete { background: var(--danger); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 10px; }
        .btn-delete:hover { background: var(--danger-hover); }

        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-error { background: #f8d7da; color: #842029; }

        .task-table { width: 100%; border-collapse: collapse; }
        .task-table th, .task-table td { padding: 1rem; border-bottom: 1px solid #eee; text-align: left; }
        .task-table th { background: #f8f9fa; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>LSS Student Portal</h1>
    <div class="user-info">
        <span>Hello, <?= htmlspecialchars($student['FirstName']) ?></span>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</nav>

<div class="container">
    <aside>
        <div class="card profile-card" style="text-align: center;">
            <div class="card-body">
                <div class="profile-avatar">
                    <?php if (!empty($student['ProfilePicture'])): ?>
                        <img src="uploads/<?= htmlspecialchars($student['ProfilePicture']) ?>?t=<?= time() ?>">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                
                <div class="student-name"><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></div>
                <div class="student-id"><?= htmlspecialchars($student['StudentNumber']) ?></div>
                
                <button id="editProfileBtn" class="btn-submit" style="margin-top: 1rem;">Edit Profile</button>
                <br><br>
                
                <span class="status-badge status-<?= str_replace(' ', '-', $student['Status']) ?>">
                    <?= htmlspecialchars($student['Status']) ?>
                </span>

                <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #eee;">

                <div class="avatar-edit-area">
                    <?php if ($message && (isset($_POST['update_picture']) || isset($_POST['delete_picture']))): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <form action="student_dashboard.php" method="post" enctype="multipart/form-data">
                        <label class="form-label">Change Profile Picture</label>
                        <div class="input-group">
                            <input type="file" name="profile_picture" class="form-input" accept=".jpg">
                            <button type="submit" name="update_picture" class="btn-submit">Upload</button>
                        </div>
                    </form>

                    <?php if (!empty($student['ProfilePicture'])): ?>
                        <form action="student_dashboard.php" method="post" style="margin-top: 5px;">
                            <button type="submit" name="delete_picture" class="btn-delete" onclick="return confirm('Remove current profile picture?');">
                                Remove Profile Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="static-details">
                    <div class="detail-header">👤 Personal Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Degree</span>
                        <span class="detail-value"><?= htmlspecialchars($student['DegreeProgram']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value" style="font-size: 0.8rem;"><?= htmlspecialchars($student['Email']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact</span>
                        <span class="detail-value"><?= htmlspecialchars($student['ContactNumber']) ?></span>
                    </div>
                </div>

            </div>
        </div>
    </aside>

    <main>
        <div class="card">
            <div class="card-header"><h3>🎓 Scholarship Details</h3></div>
            <div class="card-body">
                <h2 style="color: var(--primary);"><?= htmlspecialchars($student['Scholarship']) ?></h2>
            </div>
            <div class="scholarship-edit-area" style="padding: 1.5rem; border-top: 1px solid #eee;">
                <?php if($message && isset($_POST['scholarship'])): ?>
                    <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label class="form-label">Scholarship Name</label>
                    <input type="text" name="scholarship" class="form-input" value="<?= htmlspecialchars($student['Scholarship']) ?>">
                    
                    <input type="hidden" name="degree" value="<?= htmlspecialchars($student['DegreeProgram']) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($student['Email']) ?>">
                    <input type="hidden" name="contact_number" value="<?= htmlspecialchars($student['ContactNumber']) ?>">
                    
                    <button type="submit" name="update_profile" class="btn-submit" style="margin-top:10px;">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card personal-details-editor">
            <div class="card-header"><h3>✏️ Edit Personal Details</h3></div>
            <div class="card-body">
                <?php if($message && isset($_POST['degree'])): ?>
                    <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <label class="form-label">Degree Program</label>
                    <input type="text" name="degree" class="form-input" value="<?= htmlspecialchars($student['DegreeProgram']) ?>">
                    
                    <label class="form-label" style="margin-top:10px;">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($student['Email']) ?>">
                    
                    <label class="form-label" style="margin-top:10px;">Contact Number</label>
                    <input type="text" name="contact_number" class="form-input" value="<?= htmlspecialchars($student['ContactNumber']) ?>">
                    
                    <input type="hidden" name="scholarship" value="<?= htmlspecialchars($student['Scholarship']) ?>">
                    
                    <button type="submit" name="update_profile" class="btn-submit" style="margin-top:15px;">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card requirement-tracker-section">
            <div class="card-header"><h3>📋 Requirements Tracker</h3></div>
            <table class="task-table">
                <tr><th>Task</th><th>Deadline</th><th>Status</th></tr>
                <?php foreach($requirements as $task): ?>
                <tr>
                    <td><?= $task['title'] ?></td>
                    <td><?= $task['date'] ?></td>
                    <td><span class="status-badge status-<?= str_replace(' ', '-', $task['status']) ?>"><?= $task['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <label class="form-label">Submit Proof (PDF)</label>
                    <div class="input-group">
                        <input type="file" name="requirement" class="form-input" accept=".pdf">
                        <button type="submit" name="update_requirement" class="btn-submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editProfileBtn = document.getElementById('editProfileBtn');
        
        // Sections
        const avatarEditArea = document.querySelector('.avatar-edit-area');
        const personalDetailsEditor = document.querySelector('.personal-details-editor');
        const scholarshipEditArea = document.querySelector('.scholarship-edit-area');
        
        const requirementTracker = document.querySelector('.requirement-tracker-section');
        const staticDetails = document.querySelector('.static-details'); // LEFT SIDEBAR DETAILS

        function toggleSections() {
            const isEditing = editProfileBtn.textContent === 'Edit Profile';
            
            // Logic:
            // IF EDITING: Show Edit Forms (Right), Show Avatar Upload (Left). HIDE Static Details (Left), HIDE Tracker (Right).
            // IF NOT EDITING: Show Static Details (Left), Show Tracker (Right). HIDE Edit Forms (Right), HIDE Avatar Upload (Left).
            
            const showForEdit = isEditing ? 'block' : 'none';
            const showForView = isEditing ? 'none' : 'block';
            
            // Forms (Edit Mode)
            avatarEditArea.style.display = showForEdit;
            personalDetailsEditor.style.display = showForEdit;
            scholarshipEditArea.style.display = showForEdit;
            
            // Static Displays (View Mode)
            staticDetails.style.display = showForView;
            requirementTracker.style.display = showForView;
            
            // Update button text
            editProfileBtn.textContent = isEditing ? 'Hide Edit Forms' : 'Edit Profile';
        }

        if (editProfileBtn) editProfileBtn.addEventListener('click', toggleSections);

        // Auto-open forms if a message is present (so user sees success/error)
        <?php if ($message): ?>
            toggleSections();
        <?php endif; ?>

        // Add Cancel functionality to forms
        [personalDetailsEditor, scholarshipEditArea].forEach(container => {
            const form = container.querySelector('form');
            if (form && !form.querySelector('.btn-cancel')) {
                const cancelButton = document.createElement('button');
                cancelButton.type = 'button';
                cancelButton.textContent = 'Cancel';
                cancelButton.className = 'btn-submit';
                cancelButton.style.background = '#6c757d';
                cancelButton.style.marginLeft = '10px';
                
                cancelButton.addEventListener('click', toggleSections);
                
                const submitBtn = form.querySelector('button[type="submit"]');
                if(submitBtn) submitBtn.parentNode.insertBefore(cancelButton, submitBtn.nextSibling);
            }
        });
    });
</script>

</body>
</html>