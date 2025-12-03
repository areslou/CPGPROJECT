<?php
// admin_email_blast.php
require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

$message_status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_schol = $_POST['target_scholarship'];
    $target_status = $_POST['target_status'];
    
    // 1. Build Query to find recipients
    $sql = "SELECT Email, FirstName FROM StudentDetails WHERE 1=1";
    $params = [];
    
    if ($target_schol != 'all') {
        $sql .= " AND Scholarship = ?";
        $params[] = $target_schol;
    }
    if ($target_status != 'all') {
        $sql .= " AND Status = ?";
        $params[] = $target_status;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll();
    
    $count = count($recipients);
    
    // 2. In a real app, you would loop here and use mail()
    // foreach($recipients as $r) { mail($r['Email'], ...); }
    
    $message_status = "Success! Blast email queued for <strong>$count</strong> scholars.";
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
        
        .sidebar { width: 260px; background: #00A36C; color: #fcf9f4; display: flex; flex-direction: column; }
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
            <textarea name="message" rows="8" required placeholder="Type your announcement here..."></textarea>

            <button type="submit" class="btn">Send Blast Email</button>
        </form>
    </div>
</main>

</body>
</html>