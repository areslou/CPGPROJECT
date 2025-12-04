<?php
// admin_scheduled_emails.php

// 1. SET TIMEZONE & LIMITS
date_default_timezone_set('Asia/Manila');
set_time_limit(0); // Prevent timeout if sending many overdue emails

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
    // Check for emails that are PENDING and are DUE (Scheduled Time <= Now)
    // This covers both "Exact Time" and "Overdue"
    $stmt = $conn->prepare("SELECT * FROM ScheduledEmails WHERE status = 'pending' AND scheduled_at <= ?");
    $stmt->execute([$current_time]);
    $due_emails = $stmt->fetchAll();

    if (count($due_emails) > 0) {
        // Initialize Mailer Once
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'societyscholars3@gmail.com';
        $mail->Password   = 'wznztwaofzqwrwqf'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom($mail->Username, 'LSS Admin');
        $mail->isHTML(true);

        foreach ($due_emails as $task) {
            $mail->Subject = $task['subject'];

            // 1. Fetch Recipients
            $sql = "SELECT Email, FirstName FROM StudentDetails WHERE 1=1";
            $params = [];
            if ($task['target_scholarship'] != 'all') {
                $sql .= " AND Scholarship LIKE ?";
                $params[] = '%' . $task['target_scholarship'] . '%';
            }
            if ($task['target_status'] != 'all') {
                $sql .= " AND Status = ?";
                $params[] = $task['target_status'];
            }

            $recipStmt = $conn->prepare($sql);
            $recipStmt->execute($params);
            $recipients = $recipStmt->fetchAll();
            
            $sent_count = 0;

            // 2. Send Loop
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

            // 3. Update Status to SENT
            $updateTask = $conn->prepare("UPDATE ScheduledEmails SET status = 'sent' WHERE id = ?");
            $updateTask->execute([$task['id']]);

            // 4. Update Visual Logs (If table exists)
            try {
                $updateLog = $conn->prepare("UPDATE EmailLogs SET status = 'sent', sent_at = NOW(), recipient_count = ? WHERE subject = ? AND scheduled_at = ?");
                $updateLog->execute([$sent_count, $task['subject'], $task['scheduled_at']]);
            } catch (Exception $e) { /* Ignore if log table sync fails */ }

            $processed_count++;
        }
    }
} catch (Exception $e) {
    // Silent fail to avoid breaking UI
}

// ============================================================================
// PAGE UI LOGIC
// ============================================================================

// Handle Deletion
if (isset($_POST['delete_scheduled'])) {
    $id = $_POST['email_id'];
    $conn->prepare("DELETE FROM ScheduledEmails WHERE id = ?")->execute([$id]);
}

// Fetch All Schedules for Display
$stmt = $conn->query("SELECT * FROM ScheduledEmails ORDER BY scheduled_at DESC");
$scheduled = $stmt->fetchAll();

// Counts
$pending_count = 0;
foreach ($scheduled as $e) { if($e['status'] == 'pending') $pending_count++; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Emails - LSS</title>
    <meta http-equiv="refresh" content="60">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; display: flex; height: 100vh; margin: 0; background-image: url('Main%20Sub%20Page%20Background.gif'); }
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .menu-item { padding: 15px 25px; color: #fcf9f4; text-decoration: none; display: block; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left: 4px solid #7FE5B8; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { background: #fcf9f4; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .card { background: #fcf9f4; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #008259; color: white; }
        
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-sent { background: #28a745; color: #fff; }
        .badge-failed { background: #dc3545; color: #fff; }
        
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
        .btn-secondary { background: #6c757d; padding: 8px 15px; border-radius: 5px; color: #fcf9f4; text-decoration: none; display: inline-block; font-size: 14px; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header"><h2>Admin Portal</h2><p>Lasallian Scholars Society</p></div>
    <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
    <a href="admin_scholars.php" class="menu-item">Scholars Database</a>
    <a href="admin_email_blast.php" class="menu-item">Email Blast</a>
    <a href="admin_scheduled_emails.php" class="menu-item active">
        Scheduled Emails
        <?php if($pending_count > 0): ?>
            <span style="background:#ffc107; color:#000; padding:2px 6px; border-radius:10px; font-size:11px; margin-left:5px; font-weight:bold;"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php" class="menu-item" style="margin-top: 20px;">Logout</a>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h1 style="color:black; margin:0;">📅 Scheduled Emails</h1>
        <a href="admin_email_blast.php" class="btn-secondary">← Back to Email Blast</a>
    </div>

    <?php if($processed_count > 0): ?>
        <div class="alert">✅ Successfully sent <?php echo $processed_count; ?> pending email(s) that were due.</div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0;">Email Schedule Queue</h3>
        <?php if (count($scheduled) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Scheduled Time</th>
                        <th>Subject</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scheduled as $email): ?>
                        <tr>
                            <td>
                                <?php 
                                $time_val = strtotime($email['scheduled_at']);
                                $is_past = $time_val <= time();
                                $time_str = date("M j, Y g:i A", $time_val);
                                
                                if ($is_past && $email['status'] == 'pending') {
                                    echo "<strong style='color: #dc3545;'>⚠️ Due Now ($time_str)</strong>";
                                } else {
                                    echo $time_str;
                                }
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($email['subject']); ?></strong></td>
                            <td style="font-size: 12px;">
                                <div><?php echo htmlspecialchars($email['target_scholarship']); ?></div>
                                <div style="color: #666;"><?php echo htmlspecialchars($email['target_status']); ?></div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $email['status']; ?>">
                                    <?php echo strtoupper($email['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($email['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this scheduled email?');">
                                        <input type="hidden" name="email_id" value="<?php echo $email['id']; ?>">
                                        <button type="submit" name="delete_scheduled" class="btn-delete">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; color:#999; padding:20px;">No scheduled emails found.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>