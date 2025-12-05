<?php
// admin_email_blast.php

// 1. SET TIMEZONE & LIMITS
date_default_timezone_set('Asia/Manila'); 
set_time_limit(30);

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
// AUTOMATIC PROCESSOR - Runs on page load OR via AJAX
// ============================================================================
$message_status = "";
$current_time = date('Y-m-d H:i:s');

// Check if this is an AJAX request for background processing
$is_ajax = isset($_GET['ajax_process']) && $_GET['ajax_process'] == '1';

// Auto-process due emails (silently in background if AJAX)
try {
    $stmt = $conn->prepare("SELECT * FROM ScheduledEmails WHERE status = 'pending' AND scheduled_at <= ? LIMIT 1");
    $stmt->execute([$current_time]);
    $due_emails = $stmt->fetchAll();

    if (count($due_emails) > 0) {
        foreach ($due_emails as $task) {
            // Prevent duplicate sending with row locking
            $check = $conn->prepare("SELECT status FROM ScheduledEmails WHERE id = ? FOR UPDATE");
            $check->execute([$task['id']]);
            $currentStatus = $check->fetchColumn();
            
            if ($currentStatus !== 'pending') {
                if (!$is_ajax) {
                    $message_status = "<span style='color:orange;'>⚠️ Email already being processed.</span>";
                }
                continue;
            }

            // Mark as processing immediately
            $conn->prepare("UPDATE ScheduledEmails SET status = 'processing' WHERE id = ?")->execute([$task['id']]);
            $conn->prepare("UPDATE EmailLogs SET status = 'processing' WHERE subject = ? AND scheduled_at = ?")
                 ->execute([$task['subject'], $task['scheduled_at']]);

            $mail = new PHPMailer(true);
            $mail->isSMTP(); 
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'societyscholars3@gmail.com'; 
            $mail->Password = 'wznztwaofzqwrwqf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port = 587;
            $mail->Timeout = 15;
            $mail->setFrom($mail->Username, 'LSS Admin'); 
            $mail->isHTML(true);
            $mail->Subject = $task['subject'];

            // Fetch Recipients
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
            $failed_count = 0;

            foreach ($recipients as $student) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($student['Email'], $student['FirstName']);
                    $mail->Body = str_replace('{name}', $student['FirstName'], $task['body']);
                    $mail->send();
                    $sent_count++;
                } catch (Exception $e) {
                    $failed_count++;
                    $mail->getSMTPInstance()->reset();
                }
            }

            // Update final status
            $conn->prepare("UPDATE ScheduledEmails SET status = 'sent' WHERE id = ?")->execute([$task['id']]);
            $conn->prepare("UPDATE EmailLogs SET status = 'sent', sent_at = NOW(), recipient_count = ? WHERE subject = ? AND scheduled_at = ?")
                 ->execute([$sent_count, $task['subject'], $task['scheduled_at']]);

            if (!$is_ajax) {
                if ($failed_count > 0) {
                    $message_status = "<span style='color:orange;'>⚠️ Sent to $sent_count recipients. $failed_count failed.</span>";
                } else {
                    $message_status = "<span style='color:green;'>✅ Successfully sent to $sent_count recipients!</span>";
                }
            }
        }
    }
} catch (Exception $e) {
    if (!$is_ajax) {
        $message_status = "<span style='color:red;'>❌ Error: " . $e->getMessage() . "</span>";
    }
}

// If AJAX request, return JSON and exit
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'processed']);
    exit;
}

// ============================================================================
// PAGE UI LOGIC
// ============================================================================

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

// 3. SUBMIT FORM (SEND OR UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['send_blast']) || isset($_POST['update_blast']))) {
    $schol = $_POST['target_scholarship'];
    $status = $_POST['target_status'];
    $subj = $_POST['subject'];
    $body = $_POST['message'];
    $is_sched = isset($_POST['schedule_email']);
    $raw_time = $_POST['schedule_time'] ?? null;
    $time = ($is_sched && $raw_time) ? date('Y-m-d H:i:s', strtotime($raw_time)) : null;
    $target_desc = ($schol == 'all' ? 'All Scholarships' : $schol) . " - " . ($status == 'all' ? 'All Statuses' : $status);
    
    // Check if this is an update
    $is_update = isset($_POST['update_blast']);
    $edit_id = $_POST['edit_log_id'] ?? null;

    // Count Recipients
    try {
        $countSql = "SELECT COUNT(*) FROM StudentDetails WHERE 1=1";
        $p = [];
        if ($schol != 'all') { $countSql .= " AND Scholarship LIKE ?"; $p[] = "%$schol%"; }
        if ($status != 'all') { $countSql .= " AND Status = ?"; $p[] = $status; }
        $stmtC = $conn->prepare($countSql);
        $stmtC->execute($p);
        $est = $stmtC->fetchColumn();
    } catch (Exception $e) {
        $est = 0;
    }

    if ($is_update && $edit_id) {
        // --- UPDATE LOGIC ---
        // 1. Get original details to find the matching scheduled task
        $orig = $conn->prepare("SELECT * FROM EmailLogs WHERE id = ?");
        $orig->execute([$edit_id]);
        $original_log = $orig->fetch();

        if ($original_log && $original_log['status'] == 'pending') {
            // 2. Update the Queue (ScheduledEmails)
            // We find the record by matching the OLD subject and OLD time
            $upQ = $conn->prepare("UPDATE ScheduledEmails SET subject=?, body=?, target_scholarship=?, target_status=?, scheduled_at=? WHERE subject=? AND scheduled_at=? AND status='pending'");
            $upQ->execute([$subj, $body, $schol, $status, $time, $original_log['subject'], $original_log['scheduled_at']]);

            // 3. Update the Log (EmailLogs)
            $upL = $conn->prepare("UPDATE EmailLogs SET subject=?, body=?, target_group=?, recipient_count=?, scheduled_at=? WHERE id=?");
            $upL->execute([$subj, $body, $target_desc, $est, $time, $edit_id]);

            $message_status = "<span style='color:green;'>✏️ Email schedule updated successfully! New time: " . date("M j, g:i A", strtotime($time)) . "</span>";
        } else {
            $message_status = "<span style='color:red;'>❌ Could not update. The email may have already been sent.</span>";
        }
    } 
    else {
        // --- NEW INSERT LOGIC ---
        if ($is_sched && !empty($time)) {
            // Schedule
            $conn->prepare("INSERT INTO EmailLogs (subject, body, target_group, status, recipient_count, scheduled_at) VALUES (?, ?, ?, 'pending', ?, ?)")->execute([$subj, $body, $target_desc, $est, $time]);
            $conn->prepare("INSERT INTO ScheduledEmails (subject, body, target_scholarship, target_status, scheduled_at, status) VALUES (?, ?, ?, ?, ?, 'pending')")->execute([$subj, $body, $schol, $status, $time]);
            $message_status = "<span style='color:green;'>✅ Email Scheduled for " . date("M j, g:i A", strtotime($time)) . ". Will send to ~$est recipients.</span>";
        } else {
            // Immediate Send
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP(); 
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'societyscholars3@gmail.com'; 
                $mail->Password = 'wznztwaofzqwrwqf';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port = 587;
                $mail->Timeout = 15;
                $mail->setFrom($mail->Username, 'LSS Admin'); 
                $mail->isHTML(true); 
                $mail->Subject = $subj;

                $sql = "SELECT Email, FirstName FROM StudentDetails WHERE 1=1";
                $p = [];
                if ($schol != 'all') { $sql .= " AND Scholarship LIKE ?"; $p[] = "%$schol%"; }
                if ($status != 'all') { $sql .= " AND Status = ?"; $p[] = $status; }
                $recipStmt = $conn->prepare($sql);
                $recipStmt->execute($p);
                $recipients = $recipStmt->fetchAll();
                $sent = 0;
                $failed = 0;

                foreach ($recipients as $st) {
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($st['Email'], $st['FirstName']);
                        $mail->Body = str_replace('{name}', $st['FirstName'], $body);
                        $mail->send();
                        $sent++;
                    } catch (Exception $e) { 
                        $failed++;
                        $mail->getSMTPInstance()->reset(); 
                    }
                }
                
                $finalSt = $sent > 0 ? 'sent' : 'failed';
                $conn->prepare("INSERT INTO EmailLogs (subject, body, target_group, status, recipient_count, sent_at) VALUES (?, ?, ?, ?, ?, NOW())")->execute([$subj, $body, $target_desc, $finalSt, $sent]);
                
                if ($failed > 0) {
                    $message_status = "<span style='color:orange;'>⚠️ Sent to $sent recipients. $failed failed.</span>";
                } else {
                    $message_status = "<span style='color:green;'>✅ Sent immediately to $sent recipients.</span>";
                }
            } catch (Exception $e) {
                $message_status = "<span style='color:red;'>❌ Mailer Error: " . $e->getMessage() . "</span>";
            }
        }
    }
}

try {
    $history = $conn->query("SELECT * FROM EmailLogs ORDER BY id DESC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $history = [];
}
$scholarship_options = ['St. La Salle Financial Assistance Grant', 'STAR Scholarship', 'Vaugirard Scholarship Program', 'Archer Achiever Scholarship', 'Br. Andrew Gonzalez Academic Scholarship', 'Brother President Scholarship Program', 'Animo Grant', 'Laguna 500', 'Lifeline Assistance for Neighbors In-need Scholarship', 'DOST-SEI Merit Scholarship', 'DOST-SEI RA 7687 Scholarship', 'Rizal Provincial Government Scholarship', 'OWWA Scholarship', 'PWD Discount', 'Aboitiz Scholarship', 'Gokongwei Next Gen Scholarship for Excellence', 'Alvarez Foundation Scholarship', 'UT Foundation Inc.', 'Php Scholarship'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Blast - LSS</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #000000;
            background-image: url('Main%20Sub%20Page%20Background.gif');
            background-size: cover;
            background-attachment: fixed;
            color: #2d3436;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Hero Image Section - THE GRADIENT THINGY */
        .hero-image-section {
            width: 100%;
            height: 75vh;
            min-height: 550px;
            background: url('design_lss1.png') center center / cover no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-image-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, transparent, rgba(0, 0, 0, 0.7));
            opacity: 0.3;
            z-index: 0;
        }
        
        .hero-image-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 300px;
            /* This creates the perfect fade to black */
            background: linear-gradient(to bottom, 
                rgba(0, 0, 0, 0) 0%, 
                rgba(0, 0, 0, 0.4) 25%,
                rgba(0, 0, 0, 0.7) 50%,
                rgba(0, 0, 0, 0.9) 70%,
                #000000 100%);
            z-index: 2;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.2) 100%);
            z-index: 1;
        }
        
        .hero-content-wrapper {
            position: relative;
            z-index: 3;
            text-align: center;
            color: white;
            padding: 40px;
        }
        
        .hero-content-wrapper h1 {
            font-size: 72px;
            font-weight: 900;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), 0 2px 10px rgba(0, 0, 0, 0.3);
            letter-spacing: -2px;
            margin-bottom: 15px;
        }
        
        .hero-content-wrapper p {
            font-size: 24px;
            font-weight: 300;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.4);
        }
        
        /* Sticky Navigation Bar */
        .top-bar {
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
            padding: 0;
            box-shadow: 0 4px 15px rgba(0, 86, 63, 0.15);
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: top 0.3s ease-in-out;
        }
        
        .top-bar.scrolled {
            top: 0;
        }
        
        .top-bar-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
        }
        
        .brand-text h2 {
            font-size: 22px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        
        .brand-text p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
        }
        
        .nav-menu {
            display: flex;
            gap: 0;
        }
        
        .nav-menu a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 24px 22px;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            border-bottom-color: #7FE5B8;
            color: white;
        }
        
        .nav-menu a.logout {
            background: rgba(0, 0, 0, 0.2);
            margin-left: 15px;
            padding: 12px 24px;
            border-radius: 4px;
            align-self: center;
        }
        
        .nav-menu a.logout:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        /* Main Content */
        .main-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
            z-index: 3; /* Ensure it sits above the fade */
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8), transparent);
            z-index: -1;
        }
        
        .card { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 35px; 
            border-radius: 8px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
            /* Added Border to match Admin Dashboard feel */
            border-left: 5px solid #00A36C;
        }
        
        .card h3 {
            color: #00563F;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
        }
        
        input[type="text"], input[type="datetime-local"], select, textarea { 
            width: 100%; 
            padding: 12px 16px; 
            margin-bottom: 15px; 
            border: 2px solid #dfe6e9; 
            border-radius: 6px; 
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00563F;
            box-shadow: 0 0 0 3px rgba(0, 86, 63, 0.08);
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #2d3436;
            font-size: 14px;
        }
        
        .checkbox-row { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 15px; 
        }
        
        .checkbox-row input[type="checkbox"] { 
            width: auto; 
            margin: 0; 
            transform: scale(1.3); 
        }
        
        .btn { 
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
            color: white; 
            padding: 13px 24px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            transition: all 0.2s;
            font-weight: 600;
            font-size: 15px;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 86, 63, 0.3);
        }
        
        .btn:disabled { 
            background: #ccc; 
            cursor: not-allowed;
            transform: none;
        }
        
        .status-badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-sent { 
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fdcb6e 0%, #ffeaa7 100%);
            color: #2d3436;
        }
        
        .status-cancelled { 
            background: #f8d7da; 
            color: #721c24; 
            opacity: 0.8; 
        }
        
        .status-processing { 
            background: linear-gradient(135deg, #74b9ff 0%, #a29bfe 100%);
            color: white;
        }
        
        .btn-cancel { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 12px; 
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-cancel:hover {
            background: #c82333;
        }
        
        .btn-clear { 
            background: #636e72; 
            color: white; 
            border: none; 
            padding: 8px 18px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-clear:hover {
            background: #2d3436;
        }
        
        .header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        
        th, td { 
            text-align: left; 
            padding: 14px 12px; 
            border-bottom: 1px solid #f1f3f5; 
        }
        
        th { 
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #2d3436;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .alert-box { 
            padding: 18px 24px; 
            background: #f0fdf9;
            border-left: 5px solid #00b894; 
            margin-bottom: 25px; 
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .auto-check-indicator { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            background: rgba(0,130,89,0.95); 
            color: white; 
            padding: 12px 18px; 
            border-radius: 25px; 
            font-size: 12px; 
            display: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="hero-image-section">
    <div class="hero-overlay"></div>
    <div class="hero-content-wrapper">
        <h1>Email Blast</h1>
        <p>Connect and communicate with brilliance</p>
    </div>
</div>

<div class="top-bar" id="navbar">
    <div class="top-bar-content">
        <div class="brand">
            <div class="brand-text">
                <h2>LSS Admin Portal</h2>
                <p>Lasallian Scholars Society</p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_scholars.php">Scholars Database</a>
            <a href="admin_email_blast.php" class="active">Email Blast</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </div>
</div>

<main class="main-content">

    <?php if($message_status): ?>
        <div class="alert-box"><?php echo $message_status; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 id="formTitle">📧 Compose New Message</h3>
        <form method="POST" id="emailForm">
            <input type="hidden" name="send_blast" id="action_type" value="1">
            <input type="hidden" name="edit_log_id" id="edit_log_id" value="">

            <div class="form-grid">
                <div>
                    <label>Target Group</label>
                    <select name="target_scholarship" id="target_scholarship">
                        <option value="all">All Scholarships</option>
                        <?php foreach($scholarship_options as $s) echo "<option value='$s'>$s</option>"; ?>
                    </select>
                </div>
                <div>
                    <label>Target Status</label>
                    <select name="target_status" id="target_status">
                        <option value="all">All Statuses</option>
                        <option value="ACTIVE">Active Only</option>
                        <option value="ON LEAVE">On Leave Only</option>
                    </select>
                </div>
            </div>
            <label>Subject</label>
            <input type="text" name="subject" id="subject" required placeholder="Enter email subject">
            <label>Message <small style="color:#868e96;">(Use {name} to personalize)</small></label>
            <textarea name="message" id="message" rows="6" required placeholder="Hello {name},&#10;&#10;Your message here..."></textarea>
            
            <div class="checkbox-row">
                <input type="checkbox" id="schedule_check" name="schedule_email" value="1"> 
                <label for="schedule_check" style="margin:0;">Schedule for later?</label>
            </div>

            <div id="time_div" style="display:none; margin-bottom:15px;">
                <label>Select Time</label>
                <input type="datetime-local" name="schedule_time" id="schedule_time">
            </div>
            
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn" id="submitBtn">📤 Send / Schedule Email</button>
                <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none; background:#636e72;" onclick="cancelEdit()">Cancel Edit</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="header-flex">
            <h3>Recent Email Activity</h3>
            <div>
                <?php if(count($history) > 0): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Clear all email logs?');">
                    <button type="submit" name="clear_history" class="btn-clear">🗑️ Clear History</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(count($history) == 0): ?>
            <p style="text-align:center; color:#868e96; padding:30px;">No email activity yet.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Target Group</th>
                    <th>Scheduled/Sent</th>
                    <th>Recipients</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history as $h): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($h['subject']); ?></strong></td>
                    <td style="font-size:12px; color:#636e72;"><?php echo htmlspecialchars($h['target_group']); ?></td>
                    <td><?php echo date("M j, g:i a", strtotime($h['status']=='pending' ? $h['scheduled_at'] : $h['sent_at'])); ?></td>
                    <td style="text-align:center;"><strong><?php echo $h['recipient_count']; ?></strong></td>
                    <td>
                        <?php if ($h['status'] == 'cancelled'): ?>
                            <span class="status-badge status-cancelled">Cancelled</span>
                        <?php elseif ($h['status'] == 'processing'): ?>
                            <span class="status-badge status-processing">Processing</span>
                        <?php else: ?>
                            <span class="status-badge status-<?php echo $h['status']; ?>"><?php echo strtoupper($h['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($h['status'] == 'pending'): ?>
                            <div style="display:flex; gap:5px;">
                                <?php 
                                    // Parse data for edit
                                    $rowJson = htmlspecialchars(json_encode($h), ENT_QUOTES, 'UTF-8');
                                ?>
                                <button type="button" class="btn-clear" style="background:#0984e3; padding:6px 10px;" onclick="editEmail(<?php echo $rowJson; ?>)">✏️ Edit</button>

                                <form method="POST" style="margin:0;" onsubmit="return confirm('Cancel this scheduled email?');">
                                    <input type="hidden" name="cancel_email_id" value="<?php echo $h['id']; ?>">
                                    <button type="submit" class="btn-cancel">❌ Cancel</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="color:#ccc;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

<div class="auto-check-indicator" id="autoCheckIndicator">🔄 Checking for scheduled emails...</div>

<script>
    // Sticky Navbar on Scroll
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 300) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Toggle schedule time visibility
    document.getElementById('schedule_check').addEventListener('change', function() {
        document.getElementById('time_div').style.display = this.checked ? 'block' : 'none';
    });

    // Prevent double submission
    document.getElementById('emailForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        // Don't change text if it's "Update", otherwise change to sending
        if(btn.innerText.includes('Send')) {
            btn.textContent = '⏳ Sending... Please wait';
        } else {
            btn.textContent = '⏳ Updating...';
        }
    });

    // Populate Form for Editing
    function editEmail(data) {
        window.scrollTo({ top: 0, behavior: 'smooth' });

        document.getElementById('formTitle').innerText = "✏️ Edit Scheduled Email";
        document.getElementById('submitBtn').innerText = "💾 Update Schedule";
        document.getElementById('cancelEditBtn').style.display = 'block';
        document.getElementById('submitBtn').style.background = 'linear-gradient(135deg, #0984e3 0%, #74b9ff 100%)';

        // Set Hidden Update Trigger
        document.getElementById('action_type').name = "update_blast";
        document.getElementById('edit_log_id').value = data.id;

        document.getElementById('subject').value = data.subject;
        document.getElementById('message').value = data.body;

        // Parse Target Group (Assuming format "Scholarship - Status")
        // If your format varies, you might want to fetch from ScheduledEmails instead, but this works for basic display
        let parts = data.target_group.split(' - ');
        let schol = parts[0] === 'All Scholarships' ? 'all' : parts[0];
        let stat = parts[1] === 'All Statuses' ? 'all' : parts[1];
        
        document.getElementById('target_scholarship').value = schol;
        document.getElementById('target_status').value = stat;

        // Date Handling
        document.getElementById('schedule_check').checked = true;
        document.getElementById('time_div').style.display = 'block';
        
        // Convert SQL Date to HTML Date Input
        let sqlDate = data.scheduled_at; 
        let htmlDate = sqlDate.replace(' ', 'T').substring(0, 16);
        document.getElementById('schedule_time').value = htmlDate;
    }

    function cancelEdit() {
        document.getElementById('formTitle').innerText = "📧 Compose New Message";
        document.getElementById('submitBtn').innerText = "📤 Send / Schedule Email";
        document.getElementById('cancelEditBtn').style.display = 'none';
        document.getElementById('submitBtn').style.background = ''; // Reset CSS

        document.getElementById('emailForm').reset();
        document.getElementById('time_div').style.display = 'none';
        
        document.getElementById('action_type').name = "send_blast";
        document.getElementById('edit_log_id').value = "";
    }

    // AUTOMATIC BACKGROUND CHECKER - Runs every 30 seconds
    function checkScheduledEmails() {
        const indicator = document.getElementById('autoCheckIndicator');
        indicator.style.display = 'block';
        
        fetch('?ajax_process=1')
            .then(response => response.json())
            .then(data => {
                console.log('Auto-check completed:', data);
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 2000);
            })
            .catch(error => {
                console.error('Auto-check error:', error);
                indicator.style.display = 'none';
            });
    }

    // Run check every 30 seconds
    setInterval(checkScheduledEmails, 30000);

    // Run check on page load after 5 seconds
    setTimeout(checkScheduledEmails, 5000);
</script>
</body>
</html>