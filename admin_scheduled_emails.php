<?php
// admin_scheduled_emails.php
<<<<<<< HEAD
=======

// 1. SET TIMEZONE & LIMITS
date_default_timezone_set('Asia/Manila');
set_time_limit(0); // Prevent timeout if sending many overdue emails
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3

require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

<<<<<<< HEAD
// ============================================================================
// REGULAR PAGE LOGIC CONTINUES BELOW
=======
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
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
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
<<<<<<< HEAD
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
=======
foreach ($scheduled as $e) { if($e['status'] == 'pending') $pending_count++; }
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Emails - LSS</title>
<<<<<<< HEAD
    <!-- Removed auto-refresh for better performance -->
=======
    <meta http-equiv="refresh" content="60">
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
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
        
<<<<<<< HEAD
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
=======
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
        .btn-secondary { background: #6c757d; padding: 8px 15px; border-radius: 5px; color: #fcf9f4; text-decoration: none; display: inline-block; font-size: 14px; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; background: #d4edda; color: #155724; }
>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
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

<<<<<<< HEAD
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

    <?php echo $message_status; ?>
    
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
=======
    <?php if($processed_count > 0): ?>
        <div class="alert">✅ Successfully sent <?php echo $processed_count; ?> pending email(s) that were due.</div>
    <?php endif; ?>

>>>>>>> 22a0acbee080d9869e29abc17cb639ddbc2893e3
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