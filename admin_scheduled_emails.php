<?php
// admin_scheduled_emails.php - WITH AUTO-TRIGGER

require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

// ============================================================================
// AUTO-TRIGGER: Automatically process scheduled emails when page loads
// ============================================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Check for pending emails that need to be sent
$auto_stmt = $conn->prepare("
    SELECT * FROM ScheduledEmails 
    WHERE status = 'pending' 
    AND scheduled_at <= NOW()
    ORDER BY scheduled_at ASC
");
$auto_stmt->execute();
$auto_emails = $auto_stmt->fetchAll();

$auto_sent_total = 0;

// Process them automatically in the background
if (count($auto_emails) > 0) {
    foreach ($auto_emails as $email_job) {
        $job_id = $email_job['id'];
        $target_schol = $email_job['target_scholarship'];
        $target_status = $email_job['target_status'];
        $subject_line = $email_job['subject'];
        $body_content = $email_job['body'];
        
        // Build Query to find recipients
        $sql = "SELECT Email, FirstName, LastName FROM StudentDetails WHERE 1=1";
        $params = [];
        
        if ($target_schol != 'all') {
            $sql .= " AND Scholarship = ?";
            $params[] = $target_schol;
        }
        if ($target_status != 'all') {
            $sql .= " AND Status = ?";
            $params[] = $target_status;
        }
        
        $stmt_recipients = $conn->prepare($sql);
        $stmt_recipients->execute($params);
        $recipients = $stmt_recipients->fetchAll();
        
        if (count($recipients) > 0) {
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'societyscholars3@gmail.com';
                $mail->Password = 'wznztwaofzqwrwqf';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom($mail->Username, 'LSS Admin');
                $mail->isHTML(true);
                $mail->Subject = $subject_line;

                // Loop through recipients
                foreach ($recipients as $student) {
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($student['Email'], $student['FirstName']);
                        $personalized_body = str_replace('{name}', $student['FirstName'], $body_content);
                        $mail->Body = $personalized_body;
                        $mail->send();
                        $auto_sent_total++;
                    } catch (Exception $e) {
                        $mail->getSMTPInstance()->reset();
                        continue;
                    }
                }
                
                // Mark as sent
                $update_stmt = $conn->prepare("UPDATE ScheduledEmails SET status = 'sent' WHERE id = ?");
                $update_stmt->execute([$job_id]);
                
            } catch (Exception $e) {
                // Mark as failed
                $update_stmt = $conn->prepare("UPDATE ScheduledEmails SET status = 'failed' WHERE id = ?");
                $update_stmt->execute([$job_id]);
            }
        } else {
            // No recipients found, mark as failed
            $update_stmt = $conn->prepare("UPDATE ScheduledEmails SET status = 'failed' WHERE id = ?");
            $update_stmt->execute([$job_id]);
        }
    }
}

// ============================================================================
// REGULAR PAGE LOGIC CONTINUES BELOW
// ============================================================================

$message_status = "";

// Show auto-send notification if emails were sent
if ($auto_sent_total > 0) {
    $message_status = "<div class='alert alert-success'>✅ Auto-sent $auto_sent_total scheduled email(s)!</div>";
}

// Handle manual deletion
if (isset($_POST['delete_scheduled'])) {
    $id = $_POST['email_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM ScheduledEmails WHERE id = ?");
        $stmt->execute([$id]);
        $message_status = "<div class='alert alert-success'>✅ Scheduled email deleted successfully.</div>";
    } catch (PDOException $e) {
        $message_status = "<div class='alert alert-error'>❌ Error deleting email: " . $e->getMessage() . "</div>";
    }
}

// Get all scheduled emails
$stmt = $conn->query("SELECT * FROM ScheduledEmails ORDER BY scheduled_at DESC");
$scheduled = $stmt->fetchAll();

// Count by status
$pending_count = 0;
$sent_count = 0;
$failed_count = 0;

foreach ($scheduled as $email) {
    if ($email['status'] == 'pending') $pending_count++;
    if ($email['status'] == 'sent') $sent_count++;
    if ($email['status'] == 'failed') $failed_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Emails - LSS</title>
    <meta http-equiv="refresh" content="60"> <!-- Auto-refresh every 60 seconds -->
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f5f5f5; 
            display: flex; 
            height: 100vh; 
            margin: 0; 
            background-image: url('Main%20Sub%20Page%20Background.gif');
            background-size: cover;
        }
        
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .menu-item { 
            padding: 15px 25px; 
            color: #fcf9f4; 
            text-decoration: none; 
            display: block; 
            border-left: 4px solid transparent; 
            transition: 0.3s; 
        }
        .menu-item:hover, .menu-item.active { 
            background: rgba(255,255,255,0.1); 
            border-left-color: #7FE5B8; 
        }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { 
            background: #fcf9f4; 
            padding: 20px 30px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .card { 
            background: #fcf9f4; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background: #008259; 
            color: white; 
            font-weight: 600;
        }
        
        tr:hover { background: #f8f9fa; }
        
        .badge { 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-size: 12px; 
            font-weight: bold; 
            display: inline-block;
        }
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
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Portal</h2>
        <p>Lasallian Scholars Society</p>
    </div>
    <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
    <a href="admin_scholars.php" class="menu-item">Scholars Database</a>
    <a href="admin_email_blast.php" class="menu-item">Email Blast</a>
    <a href="admin_scheduled_emails.php" class="menu-item active">
        Scheduled Emails
        <?php if($pending_count > 0): ?>
            <span class="pending-badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php" class="menu-item" style="margin-top: 20px; background: #005c40;">Logout</a>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h1 style="color:black; margin:0;">📅 Scheduled Emails</h1>
        <a href="admin_email_blast.php" class="btn-secondary">← Back to Email Blast</a>
    </div>

    <div class="auto-refresh-notice">
        ⚡ This page auto-checks for scheduled emails every 60 seconds and sends them automatically
    </div>

    <?php echo $message_status; ?>

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
        <h3 style="margin-top: 0;">All Scheduled Emails</h3>
        
        <?php if (count($scheduled) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Scheduled Time</th>
                        <th>Subject</th>
                        <th>Message Preview</th>
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
                                $scheduled_time = strtotime($email['scheduled_at']);
                                $is_past = $scheduled_time <= time();
                                $time_display = date("M j, Y g:i A", $scheduled_time);
                                
                                if ($is_past && $email['status'] == 'pending') {
                                    echo "<strong style='color: #dc3545;'>⚠️ " . $time_display . "</strong>";
                                } else {
                                    echo $time_display;
                                }
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($email['subject']); ?></strong></td>
                            <td>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr(strip_tags($email['body']), 0, 50)) . '...'; ?>
                                </div>
                            </td>
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
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="email_id" value="<?php echo $email['id']; ?>">
                                        <button type="submit" name="delete_scheduled" class="btn-delete" 
                                                onclick="return confirm('Are you sure you want to delete this scheduled email?')">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 13px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Scheduled Emails</h3>
                <p>You haven't scheduled any emails yet.</p>
                <a href="admin_email_blast.php" class="btn-secondary" style="margin-top: 15px;">
                    Create Scheduled Email
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>