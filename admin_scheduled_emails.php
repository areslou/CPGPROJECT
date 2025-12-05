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
$sent_count = 0;
$failed_count = 0;
$overdue_count = 0;

foreach ($scheduled as $email) {
    if ($email['status'] == 'pending') {
        $pending_count++;
        // Check if overdue
        $scheduled_time = strtotime($email['scheduled_at']);
        $current_time = time();
        
        // Debug: Log the comparison
        error_log("Checking email ID {$email['id']}: scheduled_at={$email['scheduled_at']}, scheduled_timestamp={$scheduled_time}, current_timestamp={$current_time}, is_overdue=" . ($scheduled_time <= $current_time ? 'YES' : 'NO'));
        
        if ($scheduled_time <= $current_time) {
            $overdue_count++;
        }
    }
    if ($email['status'] == 'sent') $sent_count++;
    if ($email['status'] == 'failed') $failed_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Emails - LSS</title>
    <!-- Removed auto-refresh for better performance -->
    <style>
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
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .menu-item { padding: 15px 25px; color: #fcf9f4; text-decoration: none; display: block; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left: 4px solid #7FE5B8; }
        .main-content {
            flex: 1;
            max-width: 1600px; /* Adjust as needed */
            margin: 0 auto;
            padding: 40px;
            overflow-y: auto;
            position: relative;
            z-index: 1; /* To keep content above body background */
            background: #000000;
        }

        /* Sticky Navigation Bar */
        .top-bar-sticky {
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

        .top-bar-sticky.scrolled {
            top: 0;
        }

        .top-bar-content-sticky {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        .brand-sticky {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
        }

        .brand-text-sticky h2 {
            font-size: 22px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }

        .brand-text-sticky p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
        }

        .nav-menu-sticky {
            display: flex;
            gap: 0;
        }

        .nav-menu-sticky a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 24px 22px;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-menu-sticky a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-menu-sticky a.active {
            background: rgba(255, 255, 255, 0.15);
            border-bottom-color: #7FE5B8;
            color: white;
        }

        .nav-menu-sticky a.logout {
            background: rgba(0, 0, 0, 0.2);
            margin-left: 15px;
            padding: 12px 24px;
            border-radius: 4px;
            align-self: center;
        }

        .nav-menu-sticky a.logout:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        .card { background: #fcf9f4; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #008259; color: white; }
        
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-sent { background: #28a745; color: #fff; }
        .badge-failed { background: #dc3545; color: #fff; }
        
        .btn-delete { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 13px;
        }
        .btn-delete:hover { background: #c82333; }
        
        .btn-secondary { 
            background: #6c757d; 
            padding: 8px 15px; 
            border-radius: 5px; 
            color: #fcf9f4; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 14px;
        }
        
        .btn-send-now {
            background: #ffc107;
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-send-now:hover {
            background: #ffb700;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-send-now:active {
            transform: translateY(0);
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .auto-refresh-notice {
            background: #e7f3ff;
            color: #004085;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
            font-size: 13px;
        }
        
        .pending-badge { 
            background: #ffc107; 
            color: #000; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: bold; 
            margin-left: 8px; 
        }

        /* Hero Image Section (added for consistency) */
        .hero-image-section {
            width: 100%;
            height: 75vh;
            min-height: 550px;
            background: url('design_lss.png') center center / cover no-repeat;
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
            background: linear-gradient(to top, transparent, rgba(0, 0, 0, 0.7)), url('Main%20Sub%20Page%20Background.gif') center center / cover;
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
        
        /* Sticky Navigation Bar (added for consistency) */
        .top-bar-sticky { /* Renamed to avoid conflict with existing .top-bar */
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
            padding: 0;
            box-shadow: 0 4px 15px rgba(0, 86, 63, 0.15);
            position: fixed;
            top: -100px; /* Hidden initially */
            left: 0;
            right: 0;
            z-index: 1000;
            transition: top 0.3s ease-in-out;
        }
        
        .top-bar-sticky.scrolled {
            top: 0;
        }
        
        .top-bar-content-sticky { /* Renamed */
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        
        .brand-sticky { /* Renamed */
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
        }
        
        .brand-text-sticky h2 { /* Renamed */
            font-size: 22px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        
        .brand-text-sticky p { /* Renamed */
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
        }
        
        .nav-menu-sticky { /* Renamed */
            display: flex;
            gap: 0;
        }
        
        .nav-menu-sticky a { /* Renamed */
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 24px 22px;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-menu-sticky a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-menu-sticky a.active {
            background: rgba(255, 255, 255, 0.15);
            border-bottom-color: #7FE5B8;
            color: white;
        }
        
        .nav-menu-sticky a.logout {
            background: rgba(0, 0, 0, 0.2);
            margin-left: 15px;
            padding: 12px 24px;
            border-radius: 4px;
            align-self: center;
        }
        
        .nav-menu-sticky a.logout:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>

<!-- Hero Image Section -->
<div class="hero-image-section">
    <div class="hero-overlay"></div>
    <div class="hero-content-wrapper">
        <h1>Scheduled Emails</h1>
        <p>Your future communications, precisely timed</p>
    </div>
</div>

<!-- Sticky Navigation Bar -->
<div class="top-bar-sticky" id="navbar">
    <div class="top-bar-content-sticky">
        <div class="brand-sticky">
            <div class="brand-text-sticky">
                <h2>LSS Admin Portal</h2>
                <p>Lasallian Scholars Society</p>
            </div>
        </div>
        <nav class="nav-menu-sticky">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_scholars.php">Scholars Database</a>
            <a href="admin_email_blast.php">Email Blast</a>
            <a href="admin_scheduled_emails.php" class="active">Scheduled Emails</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </div>
</div>

<main class="main-content">
    
    <div class="back-button-section">
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>


    <div class="auto-refresh-notice">
        ⚡ Page auto-checks every 30 seconds. Overdue emails are sent automatically!
        <?php if ($overdue_count > 0): ?>
            <strong style="color: #dc3545;">⏰ Auto-sending <?php echo $overdue_count; ?> overdue email(s) now...</strong>
        <?php endif; ?>
        <br>
        <small>Current Server Time: <?php echo date('Y-m-d h:i:s A'); ?> | 
        Pending: <?php echo $pending_count; ?> | 
        Overdue: <?php echo $overdue_count; ?></small>
    </div>

    <?php if(!empty($send_log)): ?>
        <div class="card" style="background: #f8f9fa; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">📋 Send Log</h3>
            <?php echo $send_log; ?>
        </div>
    <?php endif; ?>

    <!-- Send Pending Emails Button -->
    <?php if ($pending_count > 0): ?>
    <div class="card" style="background: <?php echo $overdue_count > 0 ? '#fff3cd' : '#e7f3ff'; ?>; border-left: 4px solid <?php echo $overdue_count > 0 ? '#ffc107' : '#0056b3'; ?>; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <?php if ($overdue_count > 0): ?>
                    <h3 style="margin: 0 0 5px 0; color: #856404;">⚠️ <?php echo $overdue_count; ?> Overdue Email(s)</h3>
                    <p style="margin: 0; color: #856404;">These emails are past their scheduled time and will be sent automatically in 2 seconds...</p>
                <?php else: ?>
                    <h3 style="margin: 0 0 5px 0; color: #004085;">📅 <?php echo $pending_count; ?> Pending Email(s)</h3>
                    <p style="margin: 0; color: #004085;">Scheduled for future. Will send automatically when time arrives.</p>
                <?php endif; ?>
            </div>
            <form method="POST" style="margin: 0;" onsubmit="this.querySelector('button').classList.add('loading'); this.querySelector('button').innerHTML = '⏳ Sending...';">
                <button type="submit" name="trigger_send" class="btn-send-now">
                    📧 Send Now (Manual)
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-number" style="color: #ffc107;"><?php echo $pending_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sent</div>
            <div class="stat-number" style="color: #28a745;"><?php echo $sent_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-number" style="color: #dc3545;"><?php echo $failed_count; ?></div>
        </div>
    </div>

    <!-- Scheduled Emails Table -->
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
// Auto-check for pending emails every 30 seconds (in background)
let isChecking = false;
let autoSendAttempted = false;

function autoSendNow() {
    if (autoSendAttempted) {
        console.log('Auto-send already attempted on this page load');
        return;
    }
    
    autoSendAttempted = true;
    console.log('Auto-sending overdue emails...');
    
    // Auto-submit the form to trigger sending
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'trigger_send';
    input.value = '1';
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Check immediately on page load if there are overdue emails
window.addEventListener('DOMContentLoaded', function() {
    const overdueCount = <?php echo $overdue_count; ?>;
    const pendingCount = <?php echo $pending_count; ?>;
    
    console.log('=== Scheduled Emails Page Loaded ===');
    console.log('Pending Count:', pendingCount);
    console.log('Overdue Count:', overdueCount);
    console.log('Server Time: <?php echo date("Y-m-d H:i:s"); ?>');
    
    if (overdueCount > 0) {
        console.log('Found ' + overdueCount + ' overdue email(s). Auto-sending in 2 seconds...');
        // Wait 2 seconds then auto-send
        setTimeout(autoSendNow, 2000);
    } else if (pendingCount > 0) {
        console.log('Emails are scheduled but not yet due. Will check again in 30 seconds.');
    } else {
        console.log('No pending emails.');
    }
});

// Also check every 30 seconds
setInterval(function() {
    const overdueCount = <?php echo $overdue_count; ?>;
    
    // Only reload to check if we haven't shown a log yet
    const hasLog = document.querySelector('.card h3') && document.querySelector('.card h3').textContent.includes('Send Log');
    
    if (!hasLog && overdueCount > 0) {
        console.log('30-second check: Found overdue emails, reloading...');
        location.reload();
    } else if (!hasLog) {
        console.log('30-second check: No overdue emails yet.');
    }
}, 30000); // 30 seconds
</script>