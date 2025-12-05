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
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #000000;
            color: #2d3436;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Hero Image Section */
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
            background: #000000;
            background-image: url('Main%20Sub%20Page%20Background.gif');
            background-size: cover;
            background-attachment: fixed;
            position: relative;
            z-index: 1;
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
        
        /* Welcome Banner */
        .welcome-banner {
            background: rgba(255, 255, 255, 0.95);
            padding: 35px 40px;
            border-radius: 8px;
            margin-bottom: 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-left: 6px solid #00A36C;
        }
        
        .welcome-banner h2 { 
            margin: 0 0 10px 0; 
            color: #00563F; 
            font-size: 32px;
            font-weight: 700;
        }
        
        .welcome-banner p { 
            color: #636e72; 
            margin: 0;
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 25px; 
            margin-bottom: 40px; 
        }
        
        .stat-card { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .stat-number { 
            font-size: 42px; 
            font-weight: 800; 
            color: #2d3436; 
            margin-bottom: 8px; 
        }
        
        .stat-label { 
            color: #636e72; 
            font-size: 13px; 
            text-transform: uppercase; 
            font-weight: 600; 
            letter-spacing: 1px; 
        }
        
        /* Color Accents */
        .border-green { border-top: 5px solid #00A36C; }
        .border-blue { border-top: 5px solid #3498db; }
        .border-orange { border-top: 5px solid #f39c12; }
        .border-purple { border-top: 5px solid #9b59b6; }

        /* Section Title */
        .section-title {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #00A36C;
            display: inline-block;
        }

        /* Action Cards */
        .actions-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 25px; 
        }
        
        .action-card { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 35px; 
            border-radius: 8px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
            text-align: center;
            transition: transform 0.2s;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .action-card h3 { 
            color: #00563F; 
            margin-bottom: 12px;
            font-size: 22px;
            font-weight: 700;
        }
        
        .action-card p { 
            color: #636e72; 
            margin-bottom: 25px; 
            font-size: 15px; 
            line-height: 1.6;
            min-height: 48px;
        }
        
        .btn-action { 
            display: inline-block; 
            padding: 13px 32px; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: 600; 
            transition: all 0.2s;
            font-size: 15px;
        }
        
        .btn-green { 
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
        }
        
        .btn-green:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 86, 63, 0.3);
        }
        
        .btn-dark { 
            background: linear-gradient(135deg, #2d3436 0%, #636e72 100%);
        }
        
        .btn-dark:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 52, 54, 0.3);
        }
    </style>
</head>
<body>

<!-- Hero Image Section -->
<div class="hero-image-section">
    <div class="hero-overlay"></div>
    <div class="hero-content-wrapper">
        <h1>Admin Dashboard</h1>
        <p>Reimagine brilliance through data-driven leadership</p>
    </div>
</div>

<!-- Sticky Navigation Bar -->
<div class="top-bar" id="navbar">
    <div class="top-bar-content">
        <div class="brand">
            <div class="brand-text">
                <h2>LSS Admin Portal</h2>
                <p>Lasallian Scholars Society</p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="admin_scholars.php">Scholars Database</a>
            <a href="admin_email_blast.php">Email Blast</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </div>
</div>

<main class="main-content">
    
    <div class="welcome-banner">
        <h2>Welcome, Admin!</h2>
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
    <h3 class="section-title">Quick Actions</h3>
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
</script>

</body>
</html>