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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #008259; 
            --primary-dark: #006B4A; 
            --primary-light: #e6f2ed;
            --text: #1a1a1a; 
            --text-secondary: #5f5f5f;
            --danger: #e60023; /* Pinterest red for delete */
            --bg-card: rgba(255, 255, 255, 0.95);
            --card-radius: 32px; /* Extreme rounding like Pinterest */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f0f2f5; 
            color: var(--text); 
            /* KEEPING THE GIF BACKGROUND */
            background-image: url('Main%20Sub%20Page%20Background.gif'); 
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
        }
        
        /* Minimalist Pinterest-style Navbar */
        .navbar { 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky;
            top: 0;
            z-index: 100;
            background: transparent; /* Let the GIF show through */
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-family: 'Poppins', sans-serif;
        }
        .brand-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary);
            background: rgba(255,255,255,0.8);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255,255,255,0.8);
            padding: 6px 8px 6px 16px;
            border-radius: 30px;
        }
        .user-name { font-weight: 600; font-size: 0.9rem; }
        .logout-btn { 
            background: var(--primary); 
            color: white; 
            text-decoration: none; 
            padding: 0.6rem 1.2rem; 
            border-radius: 24px; 
            font-size: 0.85rem; 
            font-weight: 600;
            transition: transform 0.2s;
        }
        .logout-btn:hover { transform: scale(1.05); }
        
        /* Main Content - Masonry-like Grid Layout */
        .main-content {
            max-width: 1200px;
            margin: 1rem auto 4rem;
            padding: 0 1.5rem;
        }

        /* Hero Section with Big Typography */
        .hero-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-top: 2rem;
        }
        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1a1a1a;
            text-shadow: 2px 2px 4px rgba(255,255,255,0.8);
        }
        .highlight-text {
            color: var(--primary);
        }

        /* Grid for Cards */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            align-items: start; /* Important for masonry feel */
        }

        /* Pinterest-style Cards */
        .card { 
            background: var(--bg-card); 
            border-radius: var(--card-radius); 
            box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
            overflow: hidden; 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(5px); /* Subtle glass effect */
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
        }
        .card-body { padding: 2rem; }

        /* Profile Card Specifics */
        .profile-card { text-align: center; }
        .profile-avatar { 
            width: 120px; 
            height: 120px; 
            background: var(--primary-light); 
            border-radius: 50%; 
            margin: 0 auto 1rem; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 3.5rem; 
            color: var(--primary); 
            overflow: hidden; 
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .student-name { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.5rem; margin-bottom: 0.25rem; }
        .student-id { color: var(--text-secondary); font-weight: 500; margin-bottom: 1rem; }
        
        .status-badge { 
            padding: 0.5rem 1rem; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .status-ACTIVE { background: #d4edda; color: #155724; }
        
        /* Section Headers */
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        /* Form Styles */
        .form-label { display: block; font-size: 0.9rem; font-weight: 600; color: var(--text); margin-bottom: 0.5rem; margin-top: 1rem; }
        .form-input { 
            width: 100%;
            padding: 0.8rem 1rem; 
            border: 2px solid #eee; 
            border-radius: 16px; 
            font-size: 0.95rem; 
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .form-input:focus { outline: none; border-color: var(--primary); }
        .input-group { display: flex; gap: 10px; align-items: center; }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            transition: background-color 0.2s, transform 0.2s;
            width: 100%;
        }
        .btn:hover { transform: scale(1.02); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #e2e2e2; color: var(--text); }
        .btn-secondary:hover { background: #d1d1d1; }
        .btn-delete { background: transparent; color: var(--danger); border: 2px solid var(--danger); margin-top: 10px; }
        .btn-delete:hover { background: var(--danger); color: white; }

        /* Static Details */
        .detail-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        .detail-label { color: var(--text-secondary); font-weight: 500; }
        .detail-value { font-weight: 600; }

        /* Requirements Table */
        .task-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .task-row { background: #f8f9fa; border-radius: 12px; }
        .task-table td { padding: 1rem; }
        .task-table td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; font-weight: 600; }
        .task-table td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; text-align: right; }

        /* Alerts & Toggles */
        .alert { padding: 1rem; border-radius: 16px; margin-bottom: 1rem; text-align: center; font-weight: 500; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-error { background: #f8d7da; color: #842029; }
        .avatar-edit-area, .personal-details-editor, .scholarship-edit-area { display: none; }

        @media (max-width: 768px) { 
            .hero-title { font-size: 2.5rem; }
            .navbar { padding: 1rem; }
            .brand-text { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-logo">LSS</div>
        <span class="brand-text">Student Portal</span>
    </div>
    <div class="user-menu">
        <span class="user-name">Hi, <?= htmlspecialchars($student['FirstName']) ?></span>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</nav>

<div class="main-content">
    <header class="hero-header">
        <h1 class="hero-title">
            Welcome back,<br>
            <span class="highlight-text"><?= htmlspecialchars($student['FirstName']) ?>!</span>
        </h1>
    </header>

    <div class="card-grid">
        <div class="card profile-card">
            <div class="card-body">
                <div class="profile-avatar">
                    <?php if (!empty($student['ProfilePicture'])): ?>
                        <img src="uploads/<?= htmlspecialchars($student['ProfilePicture']) ?>?t=<?= time() ?>">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <h2 class="student-name"><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?></h2>
                <p class="student-id"><?= htmlspecialchars($student['StudentNumber']) ?></p>
                <span class="status-badge status-<?= str_replace(' ', '-', $student['Status']) ?>">
                    <?= htmlspecialchars($student['Status']) ?>
                </span>

                <button id="editProfileBtn" class="btn btn-primary">Edit Profile</button>

                <div class="static-details" style="margin-top: 2rem; text-align: left;">
                    <h3 class="section-title" style="text-align: left; font-size: 1rem;">Personal Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Degree</span>
                        <span class="detail-value"><?= htmlspecialchars($student['DegreeProgram']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value" style="font-size: 0.9rem;"><?= htmlspecialchars($student['Email']) ?></span>
                    </div>
                    <div class="detail-row" style="border-bottom: none;">
                        <span class="detail-label">Contact</span>
                        <span class="detail-value"><?= htmlspecialchars($student['ContactNumber']) ?></span>
                    </div>
                </div>

                <div class="avatar-edit-area" style="margin-top: 2rem; text-align: left;">
                    <h3 class="section-title">Update Picture</h3>
                    <?php if ($message && (isset($_POST['update_picture']) || isset($_POST['delete_picture']))): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    <form action="student_dashboard.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" class="form-input" accept=".jpg" style="margin-bottom: 1rem;">
                        <button type="submit" name="update_picture" class="btn btn-primary">Upload New</button>
                    </form>
                    <?php if (!empty($student['ProfilePicture'])): ?>
                        <form action="student_dashboard.php" method="post">
                            <button type="submit" name="delete_picture" class="btn btn-delete" onclick="return confirm('Remove current profile picture?');">Remove Picture</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="personal-details-editor" style="margin-top: 2rem; text-align: left;">
                    <h3 class="section-title">Edit Details</h3>
                    <?php if($message && isset($_POST['degree'])): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <label class="form-label">Degree Program</label>
                        <input type="text" name="degree" class="form-input" value="<?= htmlspecialchars($student['DegreeProgram']) ?>">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($student['Email']) ?>">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-input" value="<?= htmlspecialchars($student['ContactNumber']) ?>" style="margin-bottom: 1.5rem;">
                        <input type="hidden" name="scholarship" value="<?= htmlspecialchars($student['Scholarship']) ?>">
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="text-align: center;">
                <h3 class="section-title">🎓 Scholarship</h3>
                <h2 style="color: var(--primary); font-family: 'Poppins', sans-serif; font-size: 1.8rem; margin: 1.5rem 0;">
                    <?= htmlspecialchars($student['Scholarship']) ?>
                </h2>
                
                <div class="scholarship-edit-area" style="text-align: left;">
                    <?php if($message && isset($_POST['scholarship'])): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <label class="form-label">Edit Scholarship Name</label>
                        <input type="text" name="scholarship" class="form-input" value="<?= htmlspecialchars($student['Scholarship']) ?>" style="margin-bottom: 1.5rem;">
                        <input type="hidden" name="degree" value="<?= htmlspecialchars($student['DegreeProgram']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($student['Email']) ?>">
                        <input type="hidden" name="contact_number" value="<?= htmlspecialchars($student['ContactNumber']) ?>">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Scholarship</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card requirement-tracker-section">
            <div class="card-body">
                <h3 class="section-title">📋 Requirements Tracker</h3>
                <table class="task-table">
                    <?php foreach($requirements as $task): ?>
                    <tr class="task-row">
                        <td><?= $task['title'] ?><br><span style="font-size: 0.85rem; color: var(--text-secondary);">Due: <?= $task['date'] ?></span></td>
                        <td><span class="status-badge status-<?= str_replace(' ', '-', $task['status']) ?>" style="margin-bottom: 0;"><?= $task['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <div style="margin-top: 2rem;">
                    <h3 class="section-title" style="font-size: 1rem;">Submit Proof</h3>
                    <?php if($message && isset($_FILES['requirement'])): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <label class="form-label">Upload PDF</label>
                        <div class="input-group">
                            <input type="file" name="requirement" class="form-input" accept=".pdf">
                            <button type="submit" name="update_requirement" class="btn btn-primary" style="width: auto; padding-left: 2rem; padding-right: 2rem;">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> </div> <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editProfileBtn = document.getElementById('editProfileBtn');
        
        // Sections to toggle
        const avatarEditArea = document.querySelector('.avatar-edit-area');
        const personalDetailsEditor = document.querySelector('.personal-details-editor');
        const scholarshipEditArea = document.querySelector('.scholarship-edit-area');
        const staticDetails = document.querySelector('.static-details');

        function toggleSections() {
            const isEditing = editProfileBtn.textContent !== 'Edit Profile';
            
            const showForEdit = isEditing ? 'none' : 'block';
            const showForView = isEditing ? 'block' : 'none';
            
            // Toggle visibility
            avatarEditArea.style.display = showForEdit;
            personalDetailsEditor.style.display = showForEdit;
            scholarshipEditArea.style.display = showForEdit;
            staticDetails.style.display = showForView;
            
            // Update button state
            if (!isEditing) {
                editProfileBtn.textContent = 'Cancel Editing';
                editProfileBtn.classList.remove('btn-primary');
                editProfileBtn.classList.add('btn-secondary');
            } else {
                editProfileBtn.textContent = 'Edit Profile';
                editProfileBtn.classList.remove('btn-secondary');
                editProfileBtn.classList.add('btn-primary');
            }
        }

        if (editProfileBtn) editProfileBtn.addEventListener('click', toggleSections);

        // Auto-open forms if a message related to editing is present
        <?php if ($message && !isset($_FILES['requirement'])): ?>
            toggleSections();
        <?php endif; ?>
    });
</script>

</body>
</html>