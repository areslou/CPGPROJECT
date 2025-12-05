<?php
// admin_email_blast.php

// 1. SET TIMEZONE & LIMITS
date_default_timezone_set('Asia/Manila'); 
set_time_limit(0); 

require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

// 2. SETUP PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// ============================================================================
// AUTOMATIC PROCESSOR (Runs silently on page load)
// ============================================================================
$processed_count = 0;
$current_time = date('Y-m-d H:i:s');

try {
    // Check for emails that are PENDING and are DUE
    $stmt = $conn->prepare("SELECT * FROM ScheduledEmails WHERE status = 'pending' AND scheduled_at <= ?");
    $stmt->execute([$current_time]);
    $due_emails = $stmt->fetchAll();

    if (count($due_emails) > 0) {
        foreach ($due_emails as $task) {
            // Prevent duplicate sending if multiple admins load page
            $check = $conn->prepare("SELECT status FROM ScheduledEmails WHERE id = ?");
            $check->execute([$task['id']]);
            if ($check->fetchColumn() !== 'pending') continue;

<<<<<<< HEAD
    // --- IF SCHEDULING IS ENABLED ---
    if ($schedule_email && !empty($schedule_time)) {
        try {
            // Convert the datetime-local format to MySQL DATETIME format
            $mysql_schedule_time = date('Y-m-d H:i:s', strtotime($schedule_time));

            $stmt = $conn->prepare(
                "INSERT INTO ScheduledEmails (subject, body, target_scholarship, target_status, scheduled_at, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$subject_line, $body_content, $target_schol, $target_status, $mysql_schedule_time]);
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
=======
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
            $mail = new PHPMailer(true);
            $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
            $mail->Username = 'societyscholars3@gmail.com'; $mail->Password = 'wznztwaofzqwrwqf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
            $mail->setFrom($mail->Username, 'LSS Admin'); $mail->isHTML(true);
            $mail->Subject = $task['subject'];

            // Fetch Recipients
            $sql = "SELECT Email, FirstName FROM StudentDetails WHERE 1=1";
            $params = [];
            if ($task['target_scholarship'] != 'all') { $sql .= " AND Scholarship LIKE ?"; $params[] = '%' . $task['target_scholarship'] . '%'; }
            if ($task['target_status'] != 'all') { $sql .= " AND Status = ?"; $params[] = $task['target_status']; }

            $recipStmt = $conn->prepare($sql);
            $recipStmt->execute($params);
            $recipients = $recipStmt->fetchAll();
            $sent_count = 0;

            foreach ($recipients as $student) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($student['Email'], $student['FirstName']);
                    $mail->Body = str_replace('{name}', $student['FirstName'], $task['body']);
                    $mail->send();
                    $sent_count++;
                } catch (Exception $e) {
                    $mail->getSMTPInstance()->reset();
                }
            }

            // Update Status to SENT
            $conn->prepare("UPDATE ScheduledEmails SET status = 'sent' WHERE id = ?")->execute([$task['id']]);
            
            // Sync Log
            $conn->prepare("UPDATE EmailLogs SET status = 'sent', sent_at = NOW(), recipient_count = ? WHERE subject = ? AND scheduled_at = ?")
                 ->execute([$sent_count, $task['subject'], $task['scheduled_at']]);

            $processed_count++;
        }
    }
} catch (Exception $e) { /* Silent Fail */ }

// ============================================================================
// PAGE UI LOGIC
// ============================================================================
$message_status = "";
if ($processed_count > 0) {
    $message_status = "<span style='color:green;'>⚡ Automatically processed $processed_count scheduled email(s).</span>";
}

// 1. CLEAR HISTORY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    $conn->query("TRUNCATE TABLE EmailLogs");
    $message_status = "<span style='color:green;'>✅ Email activity log has been cleared.</span>";
}

// 2. CANCEL SCHEDULE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_email_id'])) {
    $id = $_POST['cancel_email_id'];
    $fetch = $conn->prepare("SELECT * FROM EmailLogs WHERE id = ?");
    $fetch->execute([$id]);
    $log = $fetch->fetch();

    if ($log && $log['status'] == 'pending') {
        $conn->prepare("UPDATE EmailLogs SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        $conn->prepare("UPDATE ScheduledEmails SET status = 'cancelled' WHERE subject = ? AND scheduled_at = ?")->execute([$log['subject'], $log['scheduled_at']]);
        $message_status = "<span style='color:orange;'>🚫 The scheduled email has been cancelled.</span>";
    }
}

// 3. SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_blast'])) {
    $schol = $_POST['target_scholarship'];
    $status = $_POST['target_status'];
    $subj = $_POST['subject'];
    $body = $_POST['message'];
    $is_sched = isset($_POST['schedule_email']);
    $raw_time = $_POST['schedule_time'] ?? null;
    $time = ($is_sched && $raw_time) ? date('Y-m-d H:i:s', strtotime($raw_time)) : null;
    $target_desc = ($schol == 'all' ? 'All Scholarships' : $schol) . " - " . ($status == 'all' ? 'All Statuses' : $status);

    // Count Recipients
    $countSql = "SELECT COUNT(*) FROM StudentDetails WHERE 1=1";
    $p = [];
    if ($schol != 'all') { $countSql .= " AND Scholarship LIKE ?"; $p[] = "%$schol%"; }
    if ($status != 'all') { $countSql .= " AND Status = ?"; $p[] = $status; }
    $stmtC = $conn->prepare($countSql);
    $stmtC->execute($p);
    $est = $stmtC->fetchColumn();

    if ($is_sched && !empty($time)) {
        // Schedule
        $conn->prepare("INSERT INTO EmailLogs (subject, body, target_group, status, recipient_count, scheduled_at) VALUES (?, ?, ?, 'pending', ?, ?)")->execute([$subj, $body, $target_desc, $est, $time]);
        $conn->prepare("INSERT INTO ScheduledEmails (subject, body, target_scholarship, target_status, scheduled_at, status) VALUES (?, ?, ?, ?, ?, 'pending')")->execute([$subj, $body, $schol, $status, $time]);
        $message_status = "<span style='color:green;'>✅ Email Scheduled for " . date("M j, g:i A", strtotime($time)) . ". Will send to ~$est recipients.</span>";
    } else {
        // Immediate Send (Using Processor Logic)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
            $mail->Username = 'societyscholars3@gmail.com'; $mail->Password = 'wznztwaofzqwrwqf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
            $mail->setFrom($mail->Username, 'LSS Admin'); $mail->isHTML(true); $mail->Subject = $subj;

            $sql = "SELECT Email, FirstName FROM StudentDetails WHERE 1=1";
            $p = [];
            if ($schol != 'all') { $sql .= " AND Scholarship LIKE ?"; $p[] = "%$schol%"; }
            if ($status != 'all') { $sql .= " AND Status = ?"; $p[] = $status; }
            $recipStmt = $conn->prepare($sql);
            $recipStmt->execute($p);
            $recipients = $recipStmt->fetchAll();
            $sent = 0;

            foreach ($recipients as $st) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($st['Email'], $st['FirstName']);
                    $mail->Body = str_replace('{name}', $st['FirstName'], $body);
                    $mail->send();
                    $sent++;
                } catch (Exception $e) { $mail->getSMTPInstance()->reset(); }
            }
            
            $finalSt = $sent > 0 ? 'sent' : 'failed';
            $conn->prepare("INSERT INTO EmailLogs (subject, body, target_group, status, recipient_count, sent_at) VALUES (?, ?, ?, ?, ?, NOW())")->execute([$subj, $body, $target_desc, $finalSt, $sent]);
            $message_status = "<span style='color:green;'>✅ Sent immediately to $sent recipients.</span>";
        } catch (Exception $e) {
            $message_status = "<span style='color:red;'>Mailer Error: " . $e->getMessage() . "</span>";
        }
    }
}

$history = $conn->query("SELECT * FROM EmailLogs ORDER BY id DESC LIMIT 10")->fetchAll();
$scholarship_options = ['St. La Salle Financial Assistance Grant', 'STAR Scholarship', 'Vaugirard Scholarship Program', 'Archer Achiever Scholarship', 'Br. Andrew Gonzalez Academic Scholarship', 'Brother President Scholarship Program', 'Animo Grant', 'Laguna 500', 'Lifeline Assistance for Neighbors In-need Scholarship', 'DOST-SEI Merit Scholarship', 'DOST-SEI RA 7687 Scholarship', 'Rizal Provincial Government Scholarship', 'OWWA Scholarship', 'PWD Discount', 'Aboitiz Scholarship', 'Gokongwei Next Gen Scholarship for Excellence', 'Alvarez Foundation Scholarship', 'UT Foundation Inc.', 'Php Scholarship'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Blast - LSS</title>
    <meta http-equiv="refresh" content="60">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; display: flex; height: 100vh; margin: 0; background-image: url('Main%20Sub%20Page%20Background.gif'); }
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .menu-item { padding: 15px 25px; color: #fcf9f4; text-decoration: none; display: block; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left: 4px solid #7FE5B8; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .card { background: #fcf9f4; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input[type="text"], input[type="datetime-local"], select, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .checkbox-row { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .checkbox-row input[type="checkbox"] { width: auto; margin: 0; transform: scale(1.2); }
        .btn { background: #00A36C; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-sent { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; opacity: 0.8; }
        .btn-cancel { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-clear { background: #6c757d; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header"><h2>Admin Portal</h2><p>Lasallian Scholars Society</p></div>
    <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
    <a href="admin_scholars.php" class="menu-item">Scholars Database</a>
    <a href="admin_email_blast.php" class="menu-item active">Email Blast</a>
<<<<<<< HEAD
    <a href="admin_scheduled_emails.php" class="menu-item">Scheduled Emails</a>
    <a href="logout.php" class="menu-item" style="margin-top: 20px; background: #005c40;">Logout</a>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h1 style="color:black; margin:0;">📢 Email Blast</h1>
        <div>
            <a href="admin_scheduled_emails.php" class="btn-secondary" style="margin-right: 10px;">📅 View Scheduled</a>
            <a href="admin_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
        </div>
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

=======
    <a href="logout.php" class="menu-item" style="margin-top: 20px;">Logout</a>
</aside>

<main class="main-content">
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
    <div class="card">
        <h3>Compose New Message</h3>
        <?php if($message_status) echo "<div style='padding:15px; background:#e8f5e9; margin-bottom:15px;'>$message_status</div>"; ?>
        <form method="POST">
            <input type="hidden" name="send_blast" value="1">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <label>Target Group</label>
                    <select name="target_scholarship">
                        <option value="all">All Scholarships</option>
                        <?php foreach($scholarship_options as $s) echo "<option value='$s'>$s</option>"; ?>
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
            <label>Subject</label>
            <input type="text" name="subject" required>
            <label>Message</label>
            <textarea name="message" rows="5" required></textarea>
            
            <div class="checkbox-row">
                <input type="checkbox" id="schedule_check" name="schedule_email" value="1"> 
                <label for="schedule_check">Schedule for later?</label>
            </div>

            <div id="time_div" style="display:none; margin-bottom:15px;">
                <label>Select Time</label>
                <input type="datetime-local" name="schedule_time">
            </div>
            
            <button type="submit" class="btn">Send / Schedule</button>
        </form>
    </div>

    <div class="card">
        <div class="header-flex">
            <h3>Recent Email Activity</h3>
            <?php if(count($history) > 0): ?>
            <form method="POST" onsubmit="return confirm('Clear logs?');">
                <button type="submit" name="clear_history" class="btn-clear">Clear History</button>
            </form>
            <?php endif; ?>
        </div>
        <table>
            <thead><tr><th>Subject</th><th>Scheduled/Sent</th><th>Recipient/s</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($history as $h): ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['subject']); ?></td>
                    <td><?php echo date("M j, g:i a", strtotime($h['status']=='pending' ? $h['scheduled_at'] : $h['sent_at'])); ?></td>
                    <td style="text-align:center;"><?php echo $h['recipient_count']; ?></td>
                    <td>
                        <?php if ($h['status'] == 'cancelled'): ?>
                            <span class="status-badge status-cancelled">Removed</span>
                        <?php else: ?>
                            <span class="status-badge status-<?php echo $h['status']; ?>"><?php echo strtoupper($h['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($h['status'] == 'pending'): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this email?');">
                            <input type="hidden" name="cancel_email_id" value="<?php echo $h['id']; ?>">
                            <button type="submit" class="btn-cancel">Cancel</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    document.getElementById('schedule_check').addEventListener('change', function() {
        document.getElementById('time_div').style.display = this.checked ? 'block' : 'none';
    });
</script>
</body>
</html>