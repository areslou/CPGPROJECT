<?php
// admin_dashboard.php
require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

// --- GET STATISTICS ---
try {
    // Total Scholars
    $stmt = $conn->query("SELECT COUNT(*) FROM StudentDetails");
    $total_scholars = $stmt->fetchColumn();

    // Active Scholars
    $stmt = $conn->query("SELECT COUNT(*) FROM StudentDetails WHERE Status = 'ACTIVE'");
    $active_scholars = $stmt->fetchColumn();

    // On Leave
    $stmt = $conn->query("SELECT COUNT(*) FROM StudentDetails WHERE Status = 'ON LEAVE'");
    $leave_scholars = $stmt->fetchColumn();

    // Total Scholarship Types
    $stmt = $conn->query("SELECT COUNT(DISTINCT Scholarship) FROM StudentDetails");
    $total_programs = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Fallback if table doesn't exist yet
    $total_scholars = 0;
    $active_scholars = 0;
    $leave_scholars = 0;
    $total_programs = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - LSS</title>
    <style>
        /* LASALLIAN GREEN THEME */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; height: 100vh; background-image: url('Main%20Sub%20Page%20Background.gif'); }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .sidebar-menu { padding: 20px 0; flex: 1; }
        .menu-item { padding: 15px 25px; color: #fcf9f4; text-decoration: none; display: block; transition: 0.3s; border-left: 4px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #7FE5B8; }
        .logout { margin-top: 20px; background: #005c40; }

        /* MAIN CONTENT */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        
        /* WELCOME BANNER */
        .welcome-banner {
            background: #fcf9f4;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 5px solid #00A36C;
        }
        .welcome-banner h1 { margin: 0 0 10px 0; color: #000000; font-size: 24px; }
        .welcome-banner p { color: #666; margin: 0; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fcf9f4; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: center; }
        .stat-number { font-size: 36px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .stat-label { color: #888; font-size: 13px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
        
        /* COLOR ACCENTS */
        .border-green { border-top: 4px solid #00A36C; }
        .border-blue { border-top: 4px solid #3498db; }
        .border-orange { border-top: 4px solid #f39c12; }
        .border-purple { border-top: 4px solid #9b59b6; }

        /* ACTION CARDS */
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .action-card { background: #fcf9f4; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .action-card h3 { color: #333; margin-bottom: 10px; }
        .action-card p { color: #666; margin-bottom: 20px; font-size: 14px; height: 40px; }
        .btn-action { 
            display: inline-block; 
            padding: 12px 30px; 
            color: #fcf9f4; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
            transition: 0.2s; 
        }
        .btn-green { background: #00A36C; }
        .btn-green:hover { background: #008f5d; }
        .btn-dark { background: #343a40; }
        .btn-dark:hover { background: #23272b; }

    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Portal</h2>
        <p>Lasallian Scholars Society</p>
    </div>
    <nav class="sidebar-menu">
        <a href="admin_dashboard.php" class="menu-item active">Dashboard</a>
        <a href="admin_scholars.php" class="menu-item">Scholars Database</a>
        <a href="admin_email_blast.php" class="menu-item">Email Blast</a>
        <a href="logout.php" class="menu-item logout">Logout</a>
    </nav>
</aside>

<main class="main-content">
    
    <div class="welcome-banner">
        <h1>Welcome, Admin!</h1>
        <p>Here is the current status of the Lasallian Scholars Society database.</p>
    </div>

    <!-- STATISTICS SECTION -->
    <div class="stats-grid">
        <div class="stat-card border-green">
            <div class="stat-number"><?php echo $total_scholars; ?></div>
            <div class="stat-label">Total Scholars</div>
        </div>
        <div class="stat-card border-blue">
            <div class="stat-number"><?php echo $active_scholars; ?></div>
            <div class="stat-label">Active Status</div>
        </div>
        <div class="stat-card border-orange">
            <div class="stat-number"><?php echo $leave_scholars; ?></div>
            <div class="stat-label">On Leave</div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-number"><?php echo $total_programs; ?></div>
            <div class="stat-label">Scholarship Types</div>
        </div>
    </div>

    <!-- QUICK ACTIONS SECTION -->
    <h3 style="margin-bottom: 15px; color: #fcf9f4;">Quick Actions</h3>
    <div class="actions-grid">
        <div class="action-card">
            <h3>Manage Scholars</h3>
            <p>View masterlist, add new students, edit details, or update status.</p>
            <a href="admin_scholars.php" class="btn-action btn-green">Go to Database →</a>
        </div>

        <div class="action-card">
            <h3>Email Announcements</h3>
            <p>Send blast emails to specific scholarship groups or batches.</p>
            <a href="admin_email_blast.php" class="btn-action btn-dark">Create Email Blast →</a>
        </div>
    </div>

</main>

</body>
</html>