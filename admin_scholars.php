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
    $s_num = $_POST['StudentNumber'];
    $lname = $_POST['LastName'];
    $fname = $_POST['FirstName'];
    $mname = $_POST['MiddleName'];
    $degree = $_POST['DegreeProgram'];
    $email = $_POST['Email'];
    $schol = $_POST['Scholarship'];
    $status = $_POST['Status'];
    $contact = $_POST['ContactNumber'];

    $check = $conn->prepare("SELECT StudentNumber FROM StudentDetails WHERE StudentNumber = ?");
    $check->execute([$s_num]);
    
    if ($check->rowCount() > 0 && $_POST['is_edit'] == '1') {
        // Update
        $sql = "UPDATE StudentDetails SET LastName=?, FirstName=?, MiddleName=?, DegreeProgram=?, Email=?, Scholarship=?, Status=?, ContactNumber=? WHERE StudentNumber=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lname, $fname, $mname, $degree, $email, $schol, $status, $contact, $s_num]);
    } else {
        // Insert
        $sql = "INSERT INTO StudentDetails (StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$s_num, $lname, $fname, $mname, $degree, $email, $schol, $status, $contact]);
    }
}

// --- FILTERING LOGIC ---
$where = [];
$params = [];

if (!empty($_GET['scholarship'])) {
    $where[] = "Scholarship = ?";
    $params[] = $_GET['scholarship'];
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

// Get unique scholarships for dropdown
$schol_list = $conn->query("SELECT DISTINCT Scholarship FROM StudentDetails WHERE Scholarship IS NOT NULL AND Scholarship != '' ORDER BY Scholarship")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scholars Management - LSS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: #00A36C; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: #006B4A; }
        .sidebar-menu { padding: 20px 0; flex: 1; }
        .menu-item { padding: 15px 25px; color: white; text-decoration: none; display: block; transition: 0.3s; border-left: 4px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #7FE5B8; }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .top-bar { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .content-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: 600; text-decoration: none; display: inline-block;}
        .btn-primary { background: #00A36C; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; font-size: 12px; padding: 5px 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #00A36C; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; }
        .status-ACTIVE { background: #d4edda; color: #155724; }
        .status-ON-LEAVE { background: #fff3cd; color: #856404; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 50px auto; padding: 30px; width: 600px; border-radius: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { width: 100%; }
        .full-width { grid-column: span 2; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Portal</h2>
        <p>Lasallian Scholars Society</p>
    </div>
    <nav class="sidebar-menu">
        <!-- LINK TO DASHBOARD -->
        <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
        <a href="admin_scholars.php" class="menu-item active">Scholars Database</a>
        <a href="admin_email_blast.php" class="menu-item">Email Blast</a>
        <a href="logout.php" class="menu-item" style="margin-top: 20px; background: #005c40;">Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h1>Scholars Database</h1>
        <!-- BACK BUTTON -->
        <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <div class="content-card">
        <form class="filter-bar" method="GET">
            <input type="text" name="search" placeholder="Search Name or ID..." value="<?php echo $_GET['search'] ?? ''; ?>">
            
            <select name="scholarship" style="max-width: 250px;">
                <option value="">All Scholarships</option>
                <?php foreach($schol_list as $s): ?>
                    <option value="<?php echo htmlspecialchars($s['Scholarship']); ?>" <?php if(($_GET['scholarship']??'') == $s['Scholarship']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($s['Scholarship']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">All Statuses</option>
                <option value="ACTIVE" <?php if(($_GET['status']??'') == 'ACTIVE') echo 'selected'; ?>>Active</option>
                <option value="ON LEAVE" <?php if(($_GET['status']??'') == 'ON LEAVE') echo 'selected'; ?>>On Leave</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="admin_scholars.php" class="btn btn-secondary">Reset</a>
            <button type="button" class="btn btn-primary" style="margin-left: auto;" onclick="openModal('add')">+ Add Scholar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Degree</th>
                    <th>Scholarship</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($scholars as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['StudentNumber']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['LastName'] . ', ' . $row['FirstName']); ?></strong><br>
                        <small style="color:#888"><?php echo htmlspecialchars($row['Email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['DegreeProgram']); ?></td>
                    <td><small><?php echo htmlspecialchars(substr($row['Scholarship'], 0, 30)) . '...'; ?></small></td>
                    <td>
                        <span class="badge status-<?php echo str_replace(' ', '-', $row['Status']); ?>">
                            <?php echo htmlspecialchars($row['Status']); ?>
                        </span>
                    </td>
                    <td>
                        <button onclick='editScholar(<?php echo json_encode($row); ?>)' class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;">Edit</button>
                        <a href="?delete=<?php echo $row['StudentNumber']; ?>" class="btn btn-danger" onclick="return confirm('Delete this scholar?')">Del</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL FORM -->
<div id="scholarModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-bottom: 20px; color: #00A36C;">Add Scholar</h2>
        <form method="POST">
            <input type="hidden" name="is_edit" id="is_edit" value="0">
            <div class="form-grid">
                <div class="form-group">
                    <label>ID Number</label>
                    <input type="number" name="StudentNumber" id="StudentNumber" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="LastName" id="LastName" required>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="FirstName" id="FirstName" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="MiddleName" id="MiddleName">
                </div>
                <div class="form-group">
                    <label>Degree Program</label>
                    <input type="text" name="DegreeProgram" id="DegreeProgram" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="ContactNumber" id="ContactNumber">
                </div>
                <div class="form-group full-width">
                    <label>DLSU Email</label>
                    <input type="email" name="Email" id="Email" required>
                </div>
                <div class="form-group full-width">
                    <label>Scholarship</label>
                    <input type="text" name="Scholarship" id="ScholarshipInput" list="schol_suggestions" required>
                    <datalist id="schol_suggestions">
                        <?php foreach($schol_list as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['Scholarship']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="Status" id="Status">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="ON LEAVE">ON LEAVE</option>
                        <option value="ALUMNI">ALUMNI</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="save_scholar" class="btn btn-primary">Save Scholar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('scholarModal').style.display = 'block';
    if(mode === 'add') {
        document.getElementById('modalTitle').innerText = "Add New Scholar";
        document.getElementById('is_edit').value = "0";
        document.querySelector('form').reset();
        document.getElementById('StudentNumber').readOnly = false;
    }
}
function closeModal() {
    document.getElementById('scholarModal').style.display = 'none';
}
function editScholar(data) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = "Edit Scholar";
    document.getElementById('is_edit').value = "1";
    document.getElementById('StudentNumber').value = data.StudentNumber;
    document.getElementById('StudentNumber').readOnly = true; 
    
    document.getElementById('LastName').value = data.LastName;
    document.getElementById('FirstName').value = data.FirstName;
    document.getElementById('MiddleName').value = data.MiddleName;
    document.getElementById('DegreeProgram').value = data.DegreeProgram;
    document.getElementById('Email').value = data.Email;
    document.getElementById('ScholarshipInput').value = data.Scholarship; 
    document.getElementById('Status').value = data.Status;
    document.getElementById('ContactNumber').value = data.ContactNumber;
}
</script>

</body>
</html>