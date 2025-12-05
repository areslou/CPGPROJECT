<?php
// user_profile.php
require_once 'auth_check.php';
requireStudent();
require_once 'config.php';

$student_id = $_SESSION['student_number'];
$message = "";
$message_type = ""; 

// --- HANDLE CONTACT UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    $new_contact = trim($_POST['contact_number']);
    try {
        $stmt = $conn->prepare("UPDATE StudentDetails SET ContactNumber = ? WHERE StudentNumber = ?");
        $stmt->execute([$new_contact, $student_id]);
        $message = "✅ Contact updated successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "❌ Error updating contact.";
        $message_type = "error";
    }
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $message = "❌ New passwords do not match.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM StudentDetails WHERE StudentNumber = ?");
        $stmt->execute([$student_id]);
        $user_data = $stmt->fetch();

        if ($user_data && password_verify($current_pass, $user_data['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE StudentDetails SET password_hash = ? WHERE StudentNumber = ?");
            $upd->execute([$new_hash, $student_id]);
            $message = "✅ Password changed successfully!";
            $message_type = "success";
        } else {
            $message = "❌ Current password is incorrect.";
            $message_type = "error";
        }
    }
}

// --- HANDLE PROFILE PICTURE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $new_name = uniqid('profile_', true) . '.' . $file_ext;
            if (!file_exists('uploads')) mkdir('uploads', 0777, true);
            
            if (move_uploaded_file($file_tmp, 'uploads/' . $new_name)) {
                $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = ? WHERE StudentNumber = ?");
                $stmt->execute([$new_name, $student_id]);
                $message = "✅ Picture uploaded successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Upload failed.";
                $message_type = "error";
            }
        } else {
            $message = "❌ Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.";
            $message_type = "error";
        }
    }
}

// --- HANDLE PROFILE PICTURE REMOVAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    try {
        $stmt = $conn->prepare("SELECT ProfilePicture FROM StudentDetails WHERE StudentNumber = ?");
        $stmt->execute([$student_id]);
        $current_pic = $stmt->fetchColumn();
        
        if ($current_pic && file_exists('uploads/' . $current_pic)) {
            unlink('uploads/' . $current_pic);
        }
        
        $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = NULL WHERE StudentNumber = ?");
        $stmt->execute([$student_id]);
        $message = "✅ Profile picture removed.";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "❌ Error removing picture.";
        $message_type = "error";
    }
}

// --- HANDLE TERM 1 GPA SUBMISSION WITH SCREENSHOT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_gpa'])) {
    $term1_gpa = trim($_POST['term1_gpa']);
    
    if (is_numeric($term1_gpa) && $term1_gpa >= 0 && $term1_gpa <= 4.0) {
        $gpa_screenshot = null;
        
        // Handle screenshot upload
        if (isset($_FILES['gpa_screenshot']) && $_FILES['gpa_screenshot']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['gpa_screenshot']['name'];
            $file_tmp = $_FILES['gpa_screenshot']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                $new_name = 'gpa_' . $student_id . '_' . time() . '.' . $file_ext;
                if (!file_exists('uploads/grades')) mkdir('uploads/grades', 0777, true);
                
                if (move_uploaded_file($file_tmp, 'uploads/grades/' . $new_name)) {
                    $gpa_screenshot = $new_name;
                }
            }
        }
        
        try {
            if ($gpa_screenshot) {
                $stmt = $conn->prepare("UPDATE StudentDetails SET Term1GPA = ?, GPAScreenshot = ?, GPASubmissionDate = NOW() WHERE StudentNumber = ?");
                $stmt->execute([$term1_gpa, $gpa_screenshot, $student_id]);
            } else {
                $stmt = $conn->prepare("UPDATE StudentDetails SET Term1GPA = ?, GPASubmissionDate = NOW() WHERE StudentNumber = ?");
                $stmt->execute([$term1_gpa, $student_id]);
            }
            $message = "✅ Term 1 GPA submitted successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "❌ Error submitting GPA.";
            $message_type = "error";
        }
    } else {
        $message = "❌ Please enter a valid GPA (0.00 - 4.00).";
        $message_type = "error";
    }
}

// --- HANDLE PROOF OF REQUIREMENTS SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_requirements'])) {
    if (isset($_FILES['requirements_file']) && $_FILES['requirements_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['requirements_file']['name'];
        $file_tmp = $_FILES['requirements_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_name = 'requirements_' . $student_id . '_' . time() . '.' . $file_ext;
            if (!file_exists('uploads/requirements')) mkdir('uploads/requirements', 0777, true);
            
            if (move_uploaded_file($file_tmp, 'uploads/requirements/' . $new_name)) {
                $stmt = $conn->prepare("UPDATE StudentDetails SET RequirementsFile = ?, RequirementsSubmissionDate = NOW() WHERE StudentNumber = ?");
                $stmt->execute([$new_name, $student_id]);
                $message = "✅ Requirements uploaded successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Upload failed.";
                $message_type = "error";
            }
        } else {
            $message = "❌ Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX, ZIP.";
            $message_type = "error";
        }
    } else {
        $message = "❌ Please select a file to upload.";
        $message_type = "error";
    }
}

// Fetch Student Data
$stmt = $conn->prepare("SELECT * FROM StudentDetails WHERE StudentNumber = ?");
$stmt->execute([$student_id]);
$me = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portal - LSS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('Main%20Sub%20Page%20Background.gif') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.35);
            z-index: 0;
        }

        .navbar { 
            background: #00A36C;
            color: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 10;
        }
        
        .navbar h1 { 
            font-size: 22px; 
            font-weight: 600;
        }
        
        .logout-btn { 
            color: white; 
            text-decoration: none; 
            background: #008259;
            padding: 8px 18px; 
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .logout-btn:hover { 
            background: #006B4A;
        }

        .container { 
            max-width: 1100px; 
            margin: 30px auto; 
            padding: 0 20px; 
            display: grid; 
            grid-template-columns: 320px 1fr; 
            gap: 25px;
            position: relative;
            z-index: 1;
        }

        .card { 
            background: white;
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .profile-pic { 
            width: 130px; 
            height: 130px; 
            background: #e9ecef;
            border-radius: 50%; 
            margin: 0 auto 20px; 
            overflow: hidden; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 55px;
            border: 4px solid #00A36C;
        }
        
        .profile-pic img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        .student-name { 
            text-align: center; 
            font-size: 20px; 
            font-weight: 600; 
            color: #333;
            margin-bottom: 5px;
        }
        
        .student-id { 
            text-align: center; 
            color: #777; 
            font-size: 13px; 
            margin-bottom: 20px;
        }
        
        .section-title { 
            font-size: 16px; 
            font-weight: 600; 
            color: #00684A;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px; 
            margin-bottom: 20px;
        }
        
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 18px; 
            margin-bottom: 20px; 
        }
        
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #00A36C;
        }

        .info-item label { 
            display: block; 
            font-size: 11px; 
            color: #666; 
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .info-item div { 
            font-size: 14px; 
            color: #333; 
            font-weight: 500;
        }

        .form-section { 
            background: #f8f9fa;
            padding: 20px; 
            border-radius: 6px; 
            border: 1px solid #dee2e6;
            margin-bottom: 20px; 
        }
        
        .input-group { 
            margin-bottom: 12px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }
        
        input[type="text"], 
        input[type="number"],
        input[type="password"], 
        input[type="file"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ced4da; 
            border-radius: 4px;
            font-size: 14px;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="password"]:focus {
            border-color: #00A36C;
            outline: none;
        }
        
        .btn { 
            background: #00A36C;
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 4px; 
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
        }

        .btn:hover {
            background: #008f5d;
        }

        .btn-block {
            width: 100%;
            margin-top: 10px;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert { 
            padding: 12px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            font-size: 14px;
        }
        
        .alert-success { 
            background: #d4edda; 
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error { 
            background: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .file-uploaded {
            background: #d1ecf1;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 13px;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .file-uploaded a {
            color: #004085;
            text-decoration: none;
            font-weight: 600;
        }

        .file-uploaded a:hover {
            text-decoration: underline;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 968px) {
            .container {
                grid-template-columns: 1fr;
            }

            .info-grid, .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h1>LSS Scholar Portal</h1>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>

    <div class="container">
        <div>
            <div class="card">
                <div class="profile-pic">
                    <?php if (!empty($me['ProfilePicture'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($me['ProfilePicture']); ?>" alt="Profile">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <div class="student-name"><?php echo htmlspecialchars($me['FirstName'] . ' ' . $me['LastName']); ?></div>
                <div class="student-id"><?php echo htmlspecialchars($me['StudentNumber']); ?></div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="profile_picture" accept="image/*" required style="margin-bottom:10px;">
                    <button type="submit" name="upload_picture" class="btn btn-block">Upload Photo</button>
                </form>

                <?php if (!empty($me['ProfilePicture'])): ?>
                <form method="POST" onsubmit="return confirm('Remove profile picture?');">
                    <button type="submit" name="remove_picture" class="btn btn-danger btn-block" style="margin-top:8px;">Remove Photo</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <?php if($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="section-title">Academic Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Degree Program</label>
                        <div><?php echo htmlspecialchars($me['DegreeProgram']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Scholarship</label>
                        <div><?php echo htmlspecialchars($me['Scholarship']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <div><?php echo htmlspecialchars($me['Email']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <div style="<?php echo $me['Status'] == 'ACTIVE' ? 'color: #28a745;' : 'color: #ffc107;'; ?>">
                            <?php echo htmlspecialchars($me['Status']); ?>
                        </div>
                    </div>
                </div>

                <div class="section-title">Term 1 GPA Submission</div>
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="input-group">
                            <label>GPA (0.00 - 4.00)</label>
                            <input type="number" 
                                   name="term1_gpa" 
                                   step="0.01" 
                                   min="0" 
                                   max="4.0" 
                                   placeholder="e.g. 3.75"
                                   value="<?php echo !empty($me['Term1GPA']) ? htmlspecialchars($me['Term1GPA']) : ''; ?>"
                                   required>
                        </div>
                        <div class="input-group">
                            <label>Upload Screenshot of Grades (MLS)</label>
                            <input type="file" 
                                   name="gpa_screenshot" 
                                   accept="image/*,.pdf"
                                   <?php echo empty($me['Term1GPA']) ? 'required' : ''; ?>>
                            <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">
                                Accepted: JPG, PNG, PDF
                            </small>
                        </div>
                        <button type="submit" name="submit_gpa" class="btn btn-block">Submit GPA</button>
                        
                        <?php if (!empty($me['Term1GPA'])): ?>
                        <div class="file-uploaded">
                            Current GPA: <strong><?php echo htmlspecialchars($me['Term1GPA']); ?></strong>
                            <?php if (!empty($me['GPAScreenshot'])): ?>
                                <br>Screenshot: <a href="uploads/grades/<?php echo htmlspecialchars($me['GPAScreenshot']); ?>" target="_blank">View File</a>
                            <?php endif; ?>
                            <?php if (!empty($me['GPASubmissionDate'])): ?>
                                <br>Submitted: <?php echo date('M j, Y g:i A', strtotime($me['GPASubmissionDate'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="section-title">Proof of Requirements</div>
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="input-group">
                            <label>Upload Requirements Document</label>
                            <input type="file" 
                                   name="requirements_file" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.zip"
                                   required>
                            <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">
                                PDF, JPG, PNG, DOC, DOCX, ZIP (Max 10MB)
                            </small>
                        </div>
                        <button type="submit" name="submit_requirements" class="btn btn-block">Upload</button>
                        
                        <?php if (!empty($me['RequirementsFile'])): ?>
                        <div class="file-uploaded">
                            Uploaded: <a href="uploads/requirements/<?php echo htmlspecialchars($me['RequirementsFile']); ?>" target="_blank">
                                <?php echo htmlspecialchars($me['RequirementsFile']); ?>
                            </a>
                            <?php if (!empty($me['RequirementsSubmissionDate'])): ?>
                                <br>Submitted: <?php echo date('M j, Y g:i A', strtotime($me['RequirementsSubmissionDate'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="section-title">Update Contact</div>
                <div class="form-section">
                    <form method="POST">
                        <div class="input-group">
                            <label>Mobile Number</label>
                            <input type="text" 
                                   name="contact_number" 
                                   value="<?php echo htmlspecialchars($me['ContactNumber']); ?>" 
                                   placeholder="09xxxxxxxxx"
                                   pattern="[0-9]{11}"
                                   title="11-digit mobile number">
                        </div>
                        <button type="submit" name="update_contact" class="btn btn-block">Update</button>
                    </form>
                </div>

                <div class="section-title">Change Password</div>
                <div class="form-section">
                    <form method="POST">
                        <div class="input-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="grid-2">
                            <div class="input-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="8">
                            </div>
                            <div class="input-group">
                                <label>Confirm New</label>
                                <input type="password" name="confirm_password" required minlength="8">
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-block">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>