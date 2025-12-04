<?php
// student_profile.php
session_start();
require_once 'config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$student_number = $_SESSION['student_number'];

// Get student details from StudentDetails table
try {
    $stmt = $conn->prepare("SELECT * FROM StudentDetails WHERE StudentNumber = ?");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();
    
    if (!$student) {
        die("Student details not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LSS System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            background-image: url('Main%20Sub%20Page%20Background.gif');
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fcf9f4;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 24px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .nav-links a {
            color: #fcf9f4;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .profile-card {
            background: #fcf9f4;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fcf9f4;
            font-size: 48px;
            font-weight: bold;
        }
        .profile-name {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }
        .profile-id {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .info-item {
            padding: 15px 0;
        }
        .info-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .scholarship-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fcf9f4;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .scholarship-badge .label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .scholarship-badge .value {
            font-size: 16px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-leave {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>LSS Student Portal</h1>
        <div class="nav-links">
            <a href="student_dashboard.php">Dashboard</a>
            <a href="student_profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($student['FirstName'], 0, 1)) ?>
                </div>
                <div class="profile-name">
                    <?= htmlspecialchars($student['FirstName'] . ' ' . ($student['MiddleName'] ?? '') . ' ' . $student['LastName']) ?>
                </div>
                <div class="profile-id">
                    Student Number: <?= htmlspecialchars($student['StudentNumber']) ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Last Name</div>
                    <div class="info-value"><?= htmlspecialchars($student['LastName']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">First Name</div>
                    <div class="info-value"><?= htmlspecialchars($student['FirstName']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Middle Name</div>
                    <div class="info-value"><?= htmlspecialchars($student['MiddleName'] ?? 'N/A') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Degree Program</div>
                    <div class="info-value"><?= htmlspecialchars($student['DegreeProgram']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?= htmlspecialchars($student['Email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><?= htmlspecialchars($student['ContactNumber']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge <?= $student['Status'] === 'ACTIVE' ? 'status-active' : 'status-leave' ?>">
                            <?= htmlspecialchars($student['Status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($student['Scholarship'])): ?>
            <div class="scholarship-badge">
                <div class="label">SCHOLARSHIP INFORMATION</div>
                <div class="value"><?= htmlspecialchars($student['Scholarship']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>