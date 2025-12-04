<?php
// admin_scheduled_emails.php

require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

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