<?php
// admin_email_blast.php

// Set a consistent timezone
date_default_timezone_set('UTC');

// --------------------------------------------------------------------------
// 1. SETUP PHPMAILER (Manual Method)
// --------------------------------------------------------------------------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load the PHPMailer files from the folder you created
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// --------------------------------------------------------------------------
// 2. STANDARD PAGE SETUP
// --------------------------------------------------------------------------
require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

// --- LOGGING SETUP ---
$log_file = __DIR__ . '/admin_blast.log';
if (file_exists($log_file)) {
    unlink($log_file); // Clear log on each run for easier debugging
}
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

// Increase timeout limit to 5 minutes (sending emails takes time)
set_time_limit(300); 

$message_status = "";
$debug_log = ""; // Variable to store specific error messages

// --------------------------------------------------------------------------
// 3. HANDLE FORM SUBMISSION
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_schol = $_POST['target_scholarship'];
    $target_status = $_POST['target_status'];
    $subject_line = $_POST['subject'];
    $body_content = $_POST['message'];
    $schedule_email = isset($_POST['schedule_email']);
    $schedule_time = $_POST['schedule_time'] ?? null;

    write_log("---" . " FORM SUBMITTED ---");
    write_log("Target Scholarship: " . $target_schol);
    write_log("Target Status: " . $target_status);

    // --- IF SCHEDULING IS ENABLED ---
    if ($schedule_email && !empty($schedule_time)) {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO ScheduledEmails (subject, body, target_scholarship, target_status, scheduled_at, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$subject_line, $body_content, $target_schol, $target_status, $schedule_time]);
            $message_status = "<span style='color:green;'>✅ Success! Your email has been scheduled for " . date("F j, Y, g:i a", strtotime($schedule_time)) . ".</span>";
        } catch (PDOException $e) {
            $message_status = "<span style='color:red;'>❌ Database Error: Could not schedule the email. " . $e->getMessage() . "</span>";
        }
    } else {
        // --- ELSE, SEND IMMEDIATELY (EXISTING LOGIC) ---
        // A. Build Query to find recipients
        $sql = "SELECT Email, FirstName, LastName FROM StudentDetails WHERE 1=1";
        $params = [];
        
        if ($target_schol != 'all') {
            $sql .= " AND Scholarship LIKE ?";
            $params[] = '%' . $target_schol . '%';
        }
        if ($target_status != 'all') {
            $sql .= " AND Status = ?";
            $params[] = $target_status;
        }

        write_log("SQL Query: " . $sql);
        write_log("Parameters: " . print_r($params, true));
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $recipients = $stmt->fetchAll();
        
        write_log("Recipients Found: " . count($recipients));

        $count = count($recipients);
        $sent_count = 0;
        
        // B. Start Email Sending Process
        if ($count > 0) {
            $mail = new PHPMailer(true);

            try {
                // --- SERVER SETTINGS (GMAIL) ---
                $mail->isSMTP();                                            
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                
                // =================================================================
                // 🛑 YOU MUST EDIT THESE TWO LINES FOR IT TO WORK 🛑
                // =================================================================
                $mail->Username   = 'societyscholars3@gmail.com';         // <--- PUT YOUR REAL GMAIL HERE
                $mail->Password   = 'wznztwaofzqwrwqf';          // <--- PUT YOUR 16-CHAR APP PASSWORD HERE
                // =================================================================
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                $mail->Port       = 587;                                    

                // --- SENDER INFO ---
                $mail->setFrom($mail->Username, 'LSS Admin'); // Uses your email as the "From" address

                // --- CONTENT SETTINGS ---
                $mail->isHTML(true);                                  
                $mail->Subject = $subject_line;

                // --- LOOP THROUGH STUDENTS ---
                foreach($recipients as $student) {
                    try {
                        // 1. Add Recipient
                        $mail->addAddress($student['Email'], $student['FirstName']); 

                        // 2. Personalize Message (Replace {name} with actual First Name)
                        $personalized_body = str_replace('{name}', $student['FirstName'], $body_content);
                        $mail->Body = $personalized_body;

                        // 3. Send
                        $mail->send();
                        $sent_count++;

                        // 4. CLEAR ADDRESS (Critical for Loops!)
                        $mail->clearAddresses();
                        
                    } catch (Exception $e) {
                        // If one email fails, log the error and continue to the next
                        $debug_log .= "Failed to send to " . $student['Email'] . ": " . $mail->ErrorInfo . "<br>";
                        $mail->getSMTPInstance()->reset(); // Reset connection to try next one
                        $mail->clearAddresses();
                        continue; 
                    }
                }
                
                if ($sent_count == 0) {
                    $message_status = "<span style='color:red;'>Failed. 0 emails sent. See debug log below.</span>";
                } else {
                    $message_status = "<span style='color:green;'>Success! Blast email sent to <strong>$sent_count</strong> out of <strong>$count</strong> scholars.</span>";
                }

            } catch (Exception $e) {
                $message_status = "<span style='color:red;'>Critical Mailer Error: {" . $mail->ErrorInfo . "}</span>";
            }
        } else {
            $message_status = "<span style='color:orange;'>No scholars found matching those filters.</span>";
        }
    }
}

// Get scholarships for dropdown
$scholarship_options = [
    'St. La Salle Financial Assistance Grant',
    'STAR Scholarship',
    'Vaugirard Scholarship Program',
    'Archer Achiever Scholarship',
    'Br. Andrew Gonzalez Academic Scholarship',
    'Brother President Scholarship Program',
    'Animo Grant',
    'Laguna 500',
    'Lifeline Assistance for Neighbors In-need Scholarship',
    'DOST-SEI Merit Scholarship',
    'DOST-SEI RA 7687 Scholarship',
    'Rizal Provincial Government Scholarship',
    'OWWA Scholarship',
    'PWD Discount',
    'Aboitiz Scholarship',
    'Gokongwei Next Gen Scholarship for Excellence',
    'Alvarez Foundation Scholarship',
    'UT Foundation Inc.',
    'Php Scholarship'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Blast - LSS</title>
    <style>
        /* Shared CSS */
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; display: flex; height: 100vh; margin: 0; background-image: url('Main%20Sub%20Page%20Background.gif');}
        
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .menu-item { padding: 15px 25px; color: #fcf9f4; text-decoration: none; display: block; border-left: 4px solid transparent; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #7FE5B8; }
        
        .main-content { flex: 1; padding: 30px; }
        .top-bar { background: #fcf9f4; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        
        .card { background: #fcf9f4; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input, select, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .btn { background: #00A36C; color: #fcf9f4; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn-secondary { background: #6c757d; width: auto; font-size: 14px; text-decoration: none; display: inline-block; padding: 8px 15px; border-radius: 5px; color: #fcf9f4; }
        .alert { padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; }
        
        /* New Debug Log Style */
        .debug-box { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; margin-bottom: 20px; border-radius: 5px; font-family: monospace; font-size: 13px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Portal</h2>
        <p>Lasallian Scholars Society</p>
    </div>
    <!-- FIXED SIDEBAR LINKS -->
    <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
    <a href="admin_scholars.php" class="menu-item">Scholars Database</a>
    <a href="admin_email_blast.php" class="menu-item active">Email Blast</a>
    <a href="logout.php" class="menu-item" style="margin-top: 20px; background: #005c40;">Logout</a>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h1 style="color:black; margin:0;">📢 Email Blast</h1>
        <!-- BACK BUTTON -->
        <a href="admin_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if($message_status): ?>
        <div class="alert"><?php echo $message_status; ?></div>
    <?php endif; ?>

    <!-- SHOW ERRORS HERE IF ANY -->
    <?php if($debug_log): ?>
        <div class="debug-box">
            <strong>Debug Log (Show this to developer):</strong><br>
            <?php echo $debug_log; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label>Target Group (Scholarship)</label>
                    <select name="target_scholarship">
                        <option value="all">All Scholarships</option>
                        <?php foreach($scholarship_options as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>">
                                <?php echo htmlspecialchars($s); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Target Status</label>
                    <select name="target_status">
                        <option value="all">All Statuses</option>
                        <option value="ACTIVE">Active Only</option>
                        <option value="ON LEAVE">On Leave Only</option>
                    </select>
                </div>
            </div>

            <label>Subject Line</label>
            <input type="text" name="subject" placeholder="e.g. [REMINDER] Renewal Deadline" required>

            <label>Message Body</label>
            <textarea name="message" rows="8" required placeholder="Type your announcement here... You can use {name} to insert the student's name automatically."></textarea>

            <div style="margin-bottom: 15px;">
                <label for="schedule_email_checkbox">
                    <input type="checkbox" id="schedule_email_checkbox" name="schedule_email" value="1" style="width: auto;">
                    Schedule for Later?
                </label>
            </div>

            <div id="schedule_time_div" style="display: none; margin-bottom: 15px;">
                <label>Schedule Time (Date and Time)</label>
                <input type="datetime-local" name="schedule_time">
            </div>

            <button type="submit" class="btn" id="submitBtn">Send Blast Email</button>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scheduleCheckbox = document.getElementById('schedule_email_checkbox');
        const scheduleTimeDiv = document.getElementById('schedule_time_div');
        const submitBtn = document.getElementById('submitBtn');

        scheduleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                scheduleTimeDiv.style.display = 'block';
                submitBtn.textContent = 'Schedule Email';
            } else {
                scheduleTimeDiv.style.display = 'none';
                submitBtn.textContent = 'Send Blast Email';
            }
        });
    });
</script>

</body>
</html>
