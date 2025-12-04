<?php
require_once 'auth_check.php';
requireStudent();
require_once 'config.php';

$student_id = $_SESSION['student_number'];
$message = "";

// --- HANDLE UPDATE CONTACT INFO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    $new_contact = trim($_POST['contact_number']);
    
    try {
        $stmt = $conn->prepare("UPDATE StudentDetails SET ContactNumber = ? WHERE StudentNumber = ?");
        $stmt->execute([$new_contact, $student_id]);
        $message = "✅ Contact number updated successfully!";
    } catch (PDOException $e) {
        $message = "❌ Error updating record.";
    }
}

// --- HANDLE PROFILE PICTURE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
            $upload_dir = 'uploads/';
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                try {
                    $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = ?, ImageUploadDate = NOW(), ImageFileSize = ?, ImageMimeType = ? WHERE StudentNumber = ?");
                    $stmt->execute([$new_file_name, $file_size, $file_type, $student_id]);
                    $message = "✅ Profile picture uploaded successfully!";
                } catch (PDOException $e) {
                    $message = "❌ Error updating profile picture in database: " . $e->getMessage();
                    // Optional: remove the uploaded file if database update fails
                    unlink($dest_path);
                }
            } else {
                $message = "❌ Error moving uploaded file.";
            }
        } else {
            $message = "❌ Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.";
        }
    } else {
        $message = "❌ No file uploaded or an upload error occurred.";
    }

    }
}

// --- HANDLE PROFILE PICTURE REMOVAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    // Fetch the current profile picture path from the database
    $stmt = $conn->prepare("SELECT ProfilePicture FROM StudentDetails WHERE StudentNumber = ?");
    $stmt->execute([$student_id]);
    $current_profile_picture = $stmt->fetchColumn();

    if ($current_profile_picture) {
        $file_path_to_delete = 'uploads/' . $current_profile_picture;
        if (file_exists($file_path_to_delete)) {
            if (unlink($file_path_to_delete)) {
                try {
                    $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = NULL, ImageUploadDate = NULL, ImageFileSize = NULL, ImageMimeType = NULL WHERE StudentNumber = ?");
                    $stmt->execute([$student_id]);
                    $message = "✅ Profile picture removed successfully!";
                } catch (PDOException $e) {
                    $message = "❌ Error updating database after removing picture: " . $e->getMessage();
                }
            } else {
                $message = "❌ Error deleting physical file.";
            }
        } else {
            // File not found, but clear database entry anyway
            try {
                $stmt = $conn->prepare("UPDATE StudentDetails SET ProfilePicture = NULL, ImageUploadDate = NULL, ImageFileSize = NULL, ImageMimeType = NULL WHERE StudentNumber = ?");
                $stmt->execute([$student_id]);
                $message = "✅ Profile picture entry cleared (file not found).";
            } catch (PDOException $e) {
                $message = "❌ Error clearing database entry for picture: " . $e->getMessage();
            }
        }
    } else {
        $message = "⚠️ No profile picture to remove.";
    }
}

// --- FETCH LATEST DATA ---
$stmt = $conn->prepare("SELECT * FROM StudentDetails WHERE StudentNumber = ?");
$stmt->execute([$student_id]);
$me = $stmt->fetch();

if (!$me) {
    echo "Student record not found. Please contact Admin.";
    exit();
}

// Debugging output
error_log("ProfilePicture value in \$me: " . ($me['ProfilePicture'] ?? 'NULL'));
error_log("Current message: " . $message);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Portal - LSS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        
        /* TOP NAVIGATION */
        .navbar { background: #00A36C; color: #fcf9f4; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar h1 { margin: 0; font-size: 20px; }
        .logout-btn { color: #fcf9f4; text-decoration: none; padding: 8px 15px; border: 1px solid #fcf9f4; border-radius: 5px; font-size: 14px; transition: 0.3s; }
        .logout-btn:hover { background: #fcf9f4; color: #00A36C; }

        /* MAIN LAYOUT */
        .container { max-width: 900px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        /* CARDS */
        .card { background: #fcf9f4; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* PROFILE LEFT */
        .profile-pic { width: 100px; height: 100px; background: #e9ecef; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #00A36C; }
        .student-name { text-align: center; font-size: 20px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .student-id { text-align: center; color: #777; font-size: 14px; margin-bottom: 20px; }
        .status-badge { text-align: center; display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; width: 100%; }
        .active { background: #d4edda; color: #155724; }
        .leave { background: #fff3cd; color: #856404; }

        /* DETAILS RIGHT */
        .section-title { font-size: 16px; font-weight: bold; color: #00A36C; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-group label { display: block; font-size: 12px; color: #888; margin-bottom: 5px; font-weight: 600; }
        .info-group div { font-size: 15px; color: #333; font-weight: 500; }

        

        /* FORM */

        .edit-zone { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px dashed #ccc; }

        .form-row { display: flex; gap: 10px; }

        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }

        button.save-btn { background: #00A36C; color: #fcf9f4; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }

        button.save-btn:hover { background: #008f5d; }

        

        .alert { padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; text-align: center; }



        /* Profile picture upload styles */

        .profile-pic img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            border-radius: 50%;

        }

        .upload-form {

            text-align: center;

            margin-top: 15px;

        }

        .upload-button {

            background: #00A36C;

            color: #fcf9f4;

            padding: 8px 15px;

            border-radius: 5px;

            cursor: pointer;

            display: inline-block;

            margin-bottom: 10px;

            font-size: 14px;

            font-weight: bold;

        }

                .upload-button:hover {

                    background: #008f5d;

                }

                .remove-btn {

                    background: #dc3545; /* Red color for danger */

                    color: #fcf9f4;

                    border: none;

                    padding: 10px 20px;

                    border-radius: 5px;

                    cursor: pointer;

                    font-weight: bold;

                }

                .remove-btn:hover {

                    background: #c82333;

                }    </style>
</head>
<body>

    <div class="navbar">
        <h1>LSS Scholar Portal</h1>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>

    <div class="container">
        <div class="card">
            <div class="profile-pic">
                <?php if (!empty($me['ProfilePicture'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($me['ProfilePicture']); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    👤
                <?php endif; ?>
            </div>
            <form action="user_profile.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;">
                <label for="profile_picture" class="upload-button">Upload Picture</label>
                <button type="submit" name="upload_picture" class="save-btn" style="width: 100%; margin-top: 10px;">Save Picture</button>
                <?php if (!empty($me['ProfilePicture'])): ?>
                    <button type="submit" name="remove_picture" class="remove-btn" style="width: 100%; margin-top: 5px;">Remove Picture</button>
                <?php endif; ?>
            </form>
            <div class="student-name"><?php echo htmlspecialchars($me['FirstName'] . ' ' . $me['LastName']); ?></div>
            <div class="student-id">ID: <?php echo htmlspecialchars($me['StudentNumber']); ?></div>
            
            <div class="status-badge <?php echo ($me['Status'] == 'ACTIVE') ? 'active' : 'leave'; ?>">
                <?php echo htmlspecialchars($me['Status']); ?>
            </div>
        </div>

        <div class="card">
            <?php if($message): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="section-title">Academic Information</div>
            <div class="info-grid">
                <div class="info-group">
                    <label>Degree Program</label>
                    <div><?php echo htmlspecialchars($me['DegreeProgram']); ?></div>
                </div>
                <div class="info-group">
                    <label>Scholarship</label>
                    <div><?php echo htmlspecialchars($me['Scholarship']); ?></div>
                </div>
                <div class="info-group">
                    <label>DLSU Email</label>
                    <div><?php echo htmlspecialchars($me['Email']); ?></div>
                </div>
            </div>

            <div class="section-title">Contact Information</div>
            <div class="edit-zone">
                <form method="POST">
                    <div class="info-group" style="margin-bottom: 10px;">
                        <label>Mobile Number (Editable)</label>
                    </div>
                    <div class="form-row">
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($me['ContactNumber']); ?>" placeholder="09xxxxxxxxx">
                        <button type="submit" name="update_contact" class="save-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>