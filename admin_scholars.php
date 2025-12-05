<?php
// admin_scholars.php
require_once 'auth_check.php';
requireAdmin();
require_once 'config.php';

// --- HANDLE DELETION ---
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM StudentDetails WHERE StudentNumber = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: admin_scholars.php?msg=deleted");
    exit();
}

// --- HANDLE CREATE/UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scholar'])) {
    // --- HANDLE SCHOLARSHIP ARRAY ---
    $scholarship_array = isset($_POST['Scholarship']) && is_array($_POST['Scholarship']) ? $_POST['Scholarship'] : [];
    $schol = implode(' & ', $scholarship_array);
    // ---

    $s_num = trim($_POST['StudentNumber']);
    $lname = strtoupper(trim($_POST['LastName'])); // AUTO CONVERT TO UPPERCASE
    $fname = trim($_POST['FirstName']);
    $mname = trim($_POST['MiddleName']);
    $degree = trim($_POST['DegreeProgram']);
    $email = trim($_POST['Email']);
    $status = $_POST['Status'];
    $contact = trim($_POST['ContactNumber']);

    // SERVER-SIDE VALIDATION
    $errors = [];
    
    // Validate ID Number - must be exactly 8 digits
    if (!preg_match('/^\d{8}$/', $s_num)) {
        $errors[] = "ID Number must be exactly 8 digits.";
    }
    
    // Check for duplicate ID Number (only for new entries, not edits)
    if ($_POST['is_edit'] == '0') {
        $checkDuplicate = $conn->prepare("SELECT COUNT(*) FROM StudentDetails WHERE StudentNumber = ?");
        $checkDuplicate->execute([$s_num]);
        if ($checkDuplicate->fetchColumn() > 0) {
            $errors[] = "ID Number already exists in the database. Please use a different ID Number.";
        }
    }
    
    // Validate Contact Number - must be exactly 11 digits
    if (!empty($contact) && !preg_match('/^\d{11}$/', $contact)) {
        $errors[] = "Contact Number must be exactly 11 digits.";
    }
    
    // Validate DLSU Email format
    if (!preg_match('/^[a-zA-Z0-9._-]+@dlsu\.edu\.ph$/', $email)) {
        $errors[] = "Email must be in format: username@dlsu.edu.ph";
    }
    
    if (empty($errors)) {
        $check = $conn->prepare("SELECT StudentNumber FROM StudentDetails WHERE StudentNumber = ?");
        $check->execute([$s_num]);
        
        if ($check->rowCount() > 0 && $_POST['is_edit'] == '1') {
            // Update
            $sql = "UPDATE StudentDetails SET LastName=?, FirstName=?, MiddleName=?, DegreeProgram=?, Email=?, Scholarship=?, Status=?, ContactNumber=? WHERE StudentNumber=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$lname, $fname, $mname, $degree, $email, $schol, $status, $contact, $s_num]);
            header("Location: admin_scholars.php?msg=updated");
            exit();
        } else {
            // Insert
            $sql = "INSERT INTO StudentDetails (StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber) VALUES (?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$s_num, $lname, $fname, $mname, $degree, $email, $schol, $status, $contact]);
            header("Location: admin_scholars.php?msg=added");
            exit();
        }
    }
}

// --- FILTERING LOGIC ---
$where = [];
$params = [];

if (!empty($_GET['scholarship'])) {
    $where[] = "Scholarship LIKE ?";
    $params[] = '%' . $_GET['scholarship'] . '%';
}
if (!empty($_GET['status'])) {
    $where[] = "Status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['search'])) {
    $where[] = "(LastName LIKE ? OR FirstName LIKE ? OR StudentNumber LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$sql = "SELECT * FROM StudentDetails";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY LastName ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$scholars = $stmt->fetchAll();

// Get all existing ID Numbers for client-side validation
$existing_ids_stmt = $conn->query("SELECT StudentNumber FROM StudentDetails");
$existing_ids = $existing_ids_stmt->fetchAll(PDO::FETCH_COLUMN);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholars Database | LSS Admin</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #000000; /* Dashboard black */
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
            transition: opacity 1s ease;
        }
        
        .hero-image-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 300px;
            /* Fade to BLACK (Dashboard style) */
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
        
        .brand-text h1 {
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
        
        /* Main container */
        .wrapper {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
            z-index: 3;
        }
        
        /* Back to dashboard button */
        .back-button-section {
            margin-bottom: 30px;
            text-align: right;
        }
        
        .back-button-section a {
            color: #636e72;
            text-decoration: none;
            padding: 12px 24px;
            border: 2px solid #dfe6e9;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-block;
            background: white;
        }
        
        .back-button-section a:hover {
            border-color: #00563F;
            color: #00563F;
            background: rgba(0, 86, 63, 0.02);
        }
        
        /* Alert messages */
        .alert {
            background: white;
            padding: 18px 24px;
            margin-bottom: 30px;
            border-radius: 6px;
            border-left: 5px solid;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            font-size: 15px;
        }
        
        .alert.success {
            border-left-color: #00b894;
            background: #f0fdf9;
            color: #00563F;
        }
        
        .alert.error {
            border-left-color: #d63031;
            background: #fff5f5;
            color: #d63031;
        }
        
        .alert strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .alert ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .wrapper::before {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             width: 100%;
             height: 200px;
             background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8), transparent);
             z-index: -1;
         }

        /* Main panel */
        .main-panel {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            /* Added Green Border for Dashboard consistency */
            border-top: 5px solid #00A36C; 
        }
        
        /* Filters and controls */
        .controls-area {
            background: #f8f9fa;
            padding: 30px 35px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .control-row {
            display: flex;
            gap: 15px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .control-row:last-child {
            margin-bottom: 0;
        }
        
        .search-input {
            flex: 1;
            min-width: 280px;
            max-width: 450px;
        }
        
        .search-input input {
            width: 100%;
            padding: 13px 18px;
            border: 2px solid #dfe6e9;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.2s;
            background: white;
        }
        
        .search-input input:focus {
            outline: none;
            border-color: #00563F;
            box-shadow: 0 0 0 3px rgba(0, 86, 63, 0.08);
        }
        
        .filter-selects {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .filter-selects select {
            padding: 12px 16px;
            border: 2px solid #dfe6e9;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            min-width: 200px;
            transition: all 0.2s;
        }
        
        .filter-selects select:focus {
            outline: none;
            border-color: #00563F;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #00563F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #004530;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 86, 63, 0.2);
        }
        
        .btn-secondary {
            background: #636e72;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #2d3436;
        }
        
        .btn-success {
            background: #00b894;
            color: white;
        }
        
        .btn-success:hover {
            background: #00a383;
        }
        
        .btn-danger {
            background: #d63031;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0262a;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .btn-add {
            margin-left: auto;
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
            color: white;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 86, 63, 0.3);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
        }
        
        th {
            text-align: left;
            padding: 18px 20px;
            font-size: 12px;
            font-weight: 700;
            color: #2d3436;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #e9ecef;
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid #f1f3f5;
            font-size: 15px;
            color: #2d3436;
        }
        
        tbody tr {
            transition: all 0.15s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .scholar-info {
            font-weight: 700;
            color: #00563F;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .scholar-meta {
            font-size: 13px;
            color: #868e96;
        }
        
        /* Badges */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-ACTIVE {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
        }
        
        .status-ON-LEAVE {
            background: linear-gradient(135deg, #fdcb6e 0%, #ffeaa7 100%);
            color: #2d3436;
        }
        
        .status-ALUMNI {
            background: linear-gradient(135deg, #74b9ff 0%, #a29bfe 100%);
            color: white;
        }
        
        .scholarship-pill {
            display: inline-block;
            padding: 5px 12px;
            font-size: 11px;
            margin: 3px 4px 3px 0;
            border-radius: 5px;
            background: rgba(0, 86, 63, 0.08);
            color: #00563F;
            border: 1px solid rgba(0, 86, 63, 0.15);
            font-weight: 500;
        }
        
        .action-group {
            display: flex;
            gap: 10px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-container {
            background: white;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #00563F 0%, #006B4A 100%);
            padding: 28px 35px;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        
        .modal-header h3 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }
        
        .modal-content {
            padding: 35px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 5px;
        }
        
        .form-group.span-2 {
            grid-column: span 2;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #2d3436;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #dfe6e9;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00563F;
            box-shadow: 0 0 0 3px rgba(0, 86, 63, 0.08);
        }
        
        .input-hint {
            font-size: 12px;
            color: #868e96;
            margin-top: 6px;
        }
        
        .input-error {
            color: #d63031;
            font-size: 12px;
            margin-top: 6px;
            display: none;
        }
        
        .input-error.show {
            display: block;
        }
        
        .error-input {
            border-color: #d63031 !important;
        }
        
        .modal-footer {
            padding: 25px 35px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
    </style>
</head>
<body>

<div class="hero-image-section">
    <div class="hero-overlay"></div>
    <div class="hero-content-wrapper">
        <h1>Scholars Database</h1>
        <p>Managing brilliance, one scholar at a time</p>
    </div>
</div>

<div class="top-bar" id="navbar">
    <div class="top-bar-content">
        <div class="brand">
            <div class="brand-text">
                <h1>LSS Admin Portal</h1>
                <p>Lasallian Scholars Society</p>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_scholars.php" class="active">Scholars Database</a>
            <a href="admin_email_blast.php">Email Blast</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </div>
</div>

<div class="wrapper">
    <div class="back-button-section">
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert success">
            <?php 
                if($_GET['msg'] == 'added') echo '✓ Scholar successfully added to the database.';
                if($_GET['msg'] == 'updated') echo '✓ Scholar information has been updated.';
                if($_GET['msg'] == 'deleted') echo '✓ Scholar has been removed from the database.';
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($errors) && !empty($errors)): ?>
        <div class="alert error">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="main-panel">
        <div class="controls-area">
            <form method="GET">
                <div class="control-row">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Search by name or ID number..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="filter-selects">
                        <select name="scholarship">
                            <option value="">All Scholarships</option>
                            <?php foreach($scholarship_options as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php if(($_GET['scholarship']??'') == $s) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="ACTIVE" <?php if(($_GET['status']??'') == 'ACTIVE') echo 'selected'; ?>>Active</option>
                            <option value="ON LEAVE" <?php if(($_GET['status']??'') == 'ON LEAVE') echo 'selected'; ?>>On Leave</option>
                        </select>
                    </div>
                </div>
                
                <div class="control-row">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="admin_scholars.php" class="btn btn-secondary">Clear All</a>
                    <button type="button" class="btn btn-add" onclick="openModal('add')">+ Add New Scholar</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Scholar Information</th>
                        <th>Degree Program</th>
                        <th>Scholarships</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($scholars as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['StudentNumber']); ?></td>
                        <td>
                            <div class="scholar-info"><?php echo htmlspecialchars($row['LastName'] . ', ' . $row['FirstName']); ?></div>
                            <div class="scholar-meta"><?php echo htmlspecialchars($row['Email']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['DegreeProgram']); ?></td>
                        <td>
                            <?php 
                                $scholarships = explode(' & ', $row['Scholarship']);
                                foreach ($scholarships as $scholarship) {
                                    if (!empty($scholarship)) {
                                        echo '<span class="scholarship-pill">' . htmlspecialchars(trim($scholarship)) . '</span>';
                                    }
                                }
                            ?>
                        </td>
                        <td>
                            <span class="badge status-<?php echo str_replace(' ', '-', $row['Status']); ?>">
                                <?php echo htmlspecialchars($row['Status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-group">
                                <button onclick='editScholar(<?php echo json_encode($row); ?>)' class="btn btn-secondary btn-small">Edit</button>
                                <a href="?delete=<?php echo $row['StudentNumber']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this scholar?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="scholarModal" class="modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Scholar</h3>
        </div>
        <form method="POST" id="scholarForm">
            <input type="hidden" name="is_edit" id="is_edit" value="0">
            <div class="modal-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label>ID Number *</label>
                        <input type="text" name="StudentNumber" id="StudentNumber" maxlength="8" required>
                        <div class="input-hint">Must be exactly 8 digits</div>
                        <div class="input-error" id="error_id">ID Number must be exactly 8 digits</div>
                        <div class="input-error" id="error_duplicate">This ID Number already exists</div>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="LastName" id="LastName" required>
                        <div class="input-hint">Auto-converts to uppercase</div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="FirstName" id="FirstName" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="MiddleName" id="MiddleName">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Degree Program *</label>
                        <input type="text" name="DegreeProgram" id="DegreeProgram" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="ContactNumber" id="ContactNumber" maxlength="11">
                        <div class="input-hint">11 digits (e.g., 09171234567)</div>
                        <div class="input-error" id="error_contact">Must be exactly 11 digits</div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>DLSU Email Address *</label>
                        <input type="email" name="Email" id="Email" required>
                        <div class="input-hint">Format: username@dlsu.edu.ph</div>
                        <div class="input-error" id="error_email">Must be in format username@dlsu.edu.ph</div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>Scholarship(s)</label>
                        <select name="Scholarship[]" id="ScholarshipSelect" multiple size="8">
                            <?php foreach($scholarship_options as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-hint">Hold Ctrl (Cmd on Mac) to select multiple</div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Student Status *</label>
                        <select name="Status" id="Status" required>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="ON LEAVE">ON LEAVE</option>
                            <option value="ALUMNI">ALUMNI</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="save_scholar" class="btn btn-success">Save Scholar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store existing ID numbers from database
const existingIds = <?php echo json_encode($existing_ids); ?>;
let originalIdNumber = null;

// Get form elements
const form = document.getElementById('scholarForm');
const idNumber = document.getElementById('StudentNumber');
const lastName = document.getElementById('LastName');
const contactNumber = document.getElementById('ContactNumber');
const dlsuEmail = document.getElementById('Email');
const scholarshipSelect = document.getElementById('ScholarshipSelect');

// ID NUMBER VALIDATION
idNumber.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    
    const errorMsgLength = document.getElementById('error_id');
    const errorMsgDuplicate = document.getElementById('error_duplicate');
    const isEdit = document.getElementById('is_edit').value === '1';
    
    errorMsgLength.classList.remove('show');
    errorMsgDuplicate.classList.remove('show');
    this.classList.remove('error-input');
    
    if (this.value.length > 0 && this.value.length !== 8) {
        errorMsgLength.classList.add('show');
        this.classList.add('error-input');
    } 
    else if (this.value.length === 8 && !isEdit && existingIds.includes(this.value)) {
        errorMsgDuplicate.classList.add('show');
        this.classList.add('error-input');
    }
});

// LAST NAME - Auto convert to UPPERCASE
lastName.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});

// CONTACT NUMBER VALIDATION
contactNumber.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    
    const errorMsg = document.getElementById('error_contact');
    if (this.value.length > 0 && this.value.length !== 11) {
        errorMsg.classList.add('show');
        this.classList.add('error-input');
    } else {
        errorMsg.classList.remove('show');
        this.classList.remove('error-input');
    }
});

// DLSU EMAIL VALIDATION
dlsuEmail.addEventListener('blur', function(e) {
    const emailPattern = /^[a-zA-Z0-9._-]+@dlsu\.edu\.ph$/;
    const errorMsg = document.getElementById('error_email');
    
    if (this.value && !emailPattern.test(this.value)) {
        errorMsg.classList.add('show');
        this.classList.add('error-input');
    } else {
        errorMsg.classList.remove('show');
        this.classList.remove('error-input');
    }
});

// FORM SUBMISSION VALIDATION
form.addEventListener('submit', function(e) {
    let isValid = true;
    const isEdit = document.getElementById('is_edit').value === '1';
    
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.error-input').forEach(el => el.classList.remove('error-input'));

    if (idNumber.value.length !== 8) {
        document.getElementById('error_id').classList.add('show');
        idNumber.classList.add('error-input');
        isValid = false;
    }
    
    if (!isEdit && existingIds.includes(idNumber.value)) {
        document.getElementById('error_duplicate').classList.add('show');
        idNumber.classList.add('error-input');
        isValid = false;
    }

    if (contactNumber.value && contactNumber.value.length !== 11) {
        document.getElementById('error_contact').classList.add('show');
        contactNumber.classList.add('error-input');
        isValid = false;
    }

    const emailPattern = /^[a-zA-Z0-9._-]+@dlsu\.edu\.ph$/;
    if (!emailPattern.test(dlsuEmail.value)) {
        document.getElementById('error_email').classList.add('show');
        dlsuEmail.classList.add('error-input');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        alert('Please correct the errors before submitting.');
        
        const firstError = document.querySelector('.error-input');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

scholarshipSelect.addEventListener('change', function() {
    this.disabled = false;
});

function openModal(mode) {
    document.getElementById('scholarModal').classList.add('active');
    if(mode === 'add') {
        document.getElementById('modalTitle').innerText = "Add New Scholar";
        document.getElementById('is_edit').value = "0";
        form.reset();
        document.getElementById('StudentNumber').readOnly = false;
        originalIdNumber = null;
        
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('.error-input').forEach(el => el.classList.remove('error-input'));
    }
}

function closeModal() {
    document.getElementById('scholarModal').classList.remove('active');
}

function editScholar(data) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = "Edit Scholar";
    document.getElementById('is_edit').value = "1";
    document.getElementById('StudentNumber').value = data.StudentNumber;
    document.getElementById('StudentNumber').readOnly = true;
    originalIdNumber = data.StudentNumber;
    
    document.getElementById('LastName').value = data.LastName.toUpperCase();
    document.getElementById('FirstName').value = data.FirstName;
    document.getElementById('MiddleName').value = data.MiddleName;
    document.getElementById('DegreeProgram').value = data.DegreeProgram;
    document.getElementById('Email').value = data.Email;
    
    const scholarshipSelect = document.getElementById('ScholarshipSelect');
    const selectedScholarships = data.Scholarship.split(' & ').map(s => s.trim());
    
    for (let i = 0; i < scholarshipSelect.options.length; i++) {
        scholarshipSelect.options[i].selected = false;
    }
    
    for (let i = 0; i < scholarshipSelect.options.length; i++) {
        if (selectedScholarships.includes(scholarshipSelect.options[i].value)) {
            scholarshipSelect.options[i].selected = true;
        }
    }
    
    document.getElementById('Status').value = data.Status;
    document.getElementById('ContactNumber').value = data.ContactNumber;
    
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.error-input').forEach(el => el.classList.remove('error-input'));
}

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