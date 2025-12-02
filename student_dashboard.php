<?php
// student_dashboard.php
require_once 'auth_check.php';
requireStudent();
require_once 'config.php';

$student_number = $_SESSION['student_number'];
$message = "";
$message_type = "";

// --- 1. HANDLE CONTACT NUMBER UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    $new_contact = trim($_POST['contact_number']);
    
    // Basic validation
    if (!empty($new_contact)) {
        try {
            $stmt = $conn->prepare("UPDATE StudentDetails SET ContactNumber = ? WHERE StudentNumber = ?");
            $stmt->execute([$new_contact, $student_number]);
            $message = "✅ Contact information updated successfully.";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "❌ Error updating record: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "⚠️ Contact number cannot be empty.";
        $message_type = "error";
    }
}

// --- 2. FETCH STUDENT DETAILS ---
try {
    $stmt = $conn->prepare("SELECT * FROM StudentDetails WHERE StudentNumber = ?");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();

    if (!$student) {
        die("Student record not found. Please contact the administrator.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// --- 3. MOCK REQUIREMENTS DATA (Task Tracker) ---
// In a real app, this would come from a 'requirements' table. 
// For this project, we visualize it based on your features list.
$requirements = [
    ['title' => 'Term 1 GPA Submission', 'date' => 'Dec 20, 2025', 'status' => 'Pending'],
    ['title' => 'Scholarship Renewal Form', 'date' => 'Jan 15, 2026', 'status' => 'Not Started'],
    ['title' => 'Annual Consultation', 'date' => 'Nov 30, 2025', 'status' => 'Completed']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Lasallian Scholars Society</title>
    <style>
        /* LASALLIAN GREEN THEME */
        :root {
            --primary: #00A36C;
            --primary-dark: #006B4A;
            --accent: #f8f9fa;
            --text: #333;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: var(--text); }
        
        /* NAVBAR */
        .navbar {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.5px; }
        .user-info { font-size: 0.9rem; display: flex; align-items: center; gap: 15px; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: 0.2s;
            font-size: 0.85rem;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }

        /* LAYOUT */
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 2fr; /* Left Sidebar, Right Content */
            gap: 1.5rem;
        }

        /* CARDS */
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { padding: 1.25rem; border-bottom: 1px solid #eee; background: white; }
        .card-header h3 { font-size: 1.1rem; color: var(--primary-dark); margin: 0; }
        .card-body { padding: 1.5rem; }

        /* PROFILE SECTION (LEFT) */
        .profile-card { text-align: center; }
        .profile-avatar {
            width: 90px;
            height: 90px;
            background: #e9ecef;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary);
        }
        .student-name { font-size: 1.2rem; font-weight: 700; color: #2c3e50; margin-bottom: 0.25rem; }
        .student-id { font-size: 0.9rem; color: #7f8c8d; margin-bottom: 1rem; }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-ACTIVE { background: #d4edda; color: #155724; }
        .status-ON-LEAVE { background: #fff3cd; color: #856404; }

        /* DETAILS LIST */
        .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f0f0f0; padding: 0.75rem 0; font-size: 0.9rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; font-weight: 600; }
        .detail-value { color: #333; text-align: right; }

        /* EDIT FORM */
        .edit-form { background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px dashed #ced4da; margin-top: 1rem; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 0.5rem; text-align: left; }
        .input-group { display: flex; gap: 10px; }
        .form-input { flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--primary-dark); }

        /* TASK TRACKER TABLE */
        .task-table { width: 100%; border-collapse: collapse; }
        .task-table th { text-align: left; padding: 1rem; background: #f8f9fa; color: #666; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #eee; }
        .task-table td { padding: 1rem; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        .task-status { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Completed { background: #d4edda; color: #155724; }
        .status-Not-Started { background: #f8d7da; color: #721c24; }

        /* ALERT */
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; text-align: center; }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }
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
    
    <!-- LEFT COLUMN: PROFILE CARD -->
    <aside>
        <div class="card profile-card">
            <div class="card-body">
                <div class="profile-avatar">🎓</div>
                <div class="student-name">
                    <?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>
                </div>
                <div class="student-id"><?= htmlspecialchars($student['StudentNumber']) ?></div>
                
                <span class="status-badge status-<?= str_replace(' ', '-', $student['Status']) ?>">
                    <?= htmlspecialchars($student['Status']) ?>
                </span>

                <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #eee;">

                <div class="detail-row">
                    <span class="detail-label">Degree</span>
                    <span class="detail-value"><?= htmlspecialchars($student['DegreeProgram']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value" style="font-size: 0.8rem;"><?= htmlspecialchars($student['Email']) ?></span>
                </div>
                
                <!-- EDITABLE CONTACT -->
                <div class="edit-form">
                    <?php if($message): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <label class="form-label">Contact Number (Editable)</label>
                        <div class="input-group">
                            <input type="text" name="contact_number" class="form-input" 
                                   value="<?= htmlspecialchars($student['ContactNumber']) ?>" 
                                   placeholder="09xxxxxxxxx">
                            <button type="submit" name="update_contact" class="btn-submit">Save</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </aside>

    <!-- RIGHT COLUMN: DETAILS & TASKS -->
    <main>
        <!-- SCHOLARSHIP INFO CARD -->
        <div class="card">
            <div class="card-header">
                <h3>🎓 Scholarship Details</h3>
            </div>
            <div class="card-body">
                <h2 style="color: var(--primary); margin-bottom: 0.5rem;"><?= htmlspecialchars($student['Scholarship']) ?></h2>
                <p style="color: #666; font-size: 0.95rem;">
                    You are currently listed as an <strong><?= strtolower(htmlspecialchars($student['Status'])) ?></strong> scholar. 
                    Please ensure all requirements are submitted before the deadlines below to maintain your scholarship status.
                </p>
            </div>
        </div>

        <!-- TASK TRACKER (REQUIREMENTS) -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Requirements Tracker</h3>
            </div>
            <table class="task-table">
                <thead>
                    <tr>
                        <th>Requirement / Task</th>
                        <th>Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requirements as $task): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= $task['title'] ?></td>
                        <td><?= $task['date'] ?></td>
                        <td>
                            <span class="task-status status-<?= str_replace(' ', '-', $task['status']) ?>">
                                <?= $task['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</div>

</body>
</html>