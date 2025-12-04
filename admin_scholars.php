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
    <title>Scholars Management - LSS</title>
    <style>
        html { font-size: 62.5%; } /* 1rem = 10px */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; height: 100vh; font-size: 1.6rem; background-image: url('Main%20Sub%20Page%20Background.gif'); }
        
        /* SIDEBAR */
        .sidebar { width: 26rem; background: #008259; color: #fcf9f4; display: flex; flex-direction: column; }
        .sidebar-header { padding: 2.5rem 2rem; background: #006B4A; }
        .sidebar-menu { padding: 2rem 0; flex: 1; }
        .menu-item { padding: 1.5rem 2.5rem; color: #fcf9f4; text-decoration: none; display: block; transition: 0.3s; border-left: 0.4rem solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: #7FE5B8; }
        
        .main-content { flex: 1; padding: 3rem; overflow-y: auto; }
        .top-bar { background: #fcf9f4; padding: 2rem 3rem; border-radius: 1rem; margin-bottom: 3rem; box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .content-card { background: #fcf9f4; padding: 3rem; border-radius: 1rem; box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.05); }
        
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        input, select { padding: 1rem; border: 0.1rem solid #ddd; border-radius: 0.5rem; }
        .btn { padding: 1rem 2rem; border: none; border-radius: 0.5rem; cursor: pointer; color: #fcf9f4; font-weight: 600; text-decoration: none; display: inline-block;}
        .btn-primary { background: #00A36C; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; font-size: 1.2rem; padding: 0.5rem 1rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th { text-align: left; padding: 1.5rem; background: #f8f9fa; color: #00A36C; border-bottom: 0.2rem solid #ddd; }
        td { padding: 1.5rem; border-bottom: 0.1rem solid #eee; font-size: 1.4rem; }
        .badge { padding: 0.5rem 1rem; border-radius: 1.5rem; font-size: 1.1rem; font-weight: bold; }
        .status-ACTIVE { background: #d4edda; color: #155724; }
        .status-ON-LEAVE { background: #fff3cd; color: #856404; }
        
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            align-items: center; 
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content { 
            background: #fcf9f4; 
            padding: 1.5rem; 
            width: 90%; 
            max-width: 60rem; 
            border-radius: 1rem; 
        }
        #modalTitle {
            margin-bottom: 2rem;
            color: #00A36C;
            font-size: 2.4rem;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 1.3rem; font-weight: bold; color: #555; }
        .form-group input, .form-group select { width: 100%; font-size: 1.6rem; padding: 1rem; border: 0.1rem solid #ddd; border-radius: 0.5rem; }
        .full-width { grid-column: span 2; }
        .modal-buttons {
            margin-top: 2rem;
            text-align: right;
        }
        
        /* VALIDATION STYLES */
        .error-message { 
            color: #e74c3c; 
            font-size: 1.1rem; 
            margin-top: 0.5rem; 
            display: none; 
        }
        .error-message.show { display: block; }
        .input-error { border-color: #e74c3c !important; }
        .hint { 
            font-size: 1.1rem; 
            color: #666; 
            margin-top: 0.3rem; 
            font-style: italic; 
        }
        
        .scholarship-badge { 
            display: inline-block; 
            padding: 0.4rem 0.8rem; 
            font-size: 1.1rem; 
            margin: 0.2rem; 
            border-radius: 0.4rem; 
            background: #e9ecef; 
            color: #495057; 
            border: 1px solid #dee2e6;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 0.1rem solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 0.1rem solid #f5c6cb;
        }
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
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    if($_GET['msg'] == 'added') echo '✓ Scholar added successfully!';
                    if($_GET['msg'] == 'updated') echo '✓ Scholar updated successfully!';
                    if($_GET['msg'] == 'deleted') echo '✓ Scholar deleted successfully!';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Validation Errors:</strong><br>
                <?php foreach($errors as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form class="filter-bar" method="GET">
            <input type="text" name="search" placeholder="Search Name or ID..." value="<?php echo $_GET['search'] ?? ''; ?>">
            
            <select name="scholarship" style="max-width: 250px;">
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
                    <td>
                        <?php 
                            $scholarships = explode(' & ', $row['Scholarship']);
                            foreach ($scholarships as $scholarship) {
                                if (!empty($scholarship)) {
                                    echo '<span class="scholarship-badge">' . htmlspecialchars(trim($scholarship)) . '</span>';
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
        <h2 id="modalTitle">Add Scholar</h2>
        <form method="POST" id="scholarForm">
            <input type="hidden" name="is_edit" id="is_edit" value="0">
            <div class="form-grid">
                <div class="form-group">
                    <label>ID Number *</label>
                    <input type="text" name="StudentNumber" id="StudentNumber" maxlength="8" required>
                    <div class="hint">Must be exactly 8 digits and unique</div>
                    <div class="error-message" id="error_id">ID Number must be exactly 8 digits</div>
                    <div class="error-message" id="error_duplicate">This ID Number already exists</div>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="LastName" id="LastName" required>
                    <div class="hint">Will be auto-converted to UPPERCASE</div>
                </div>
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="FirstName" id="FirstName" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="MiddleName" id="MiddleName">
                </div>
                <div class="form-group">
                    <label>Degree Program *</label>
                    <input type="text" name="DegreeProgram" id="DegreeProgram" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="ContactNumber" id="ContactNumber" maxlength="11">
                    <div class="hint">Must be exactly 11 digits (e.g., 09171234567)</div>
                    <div class="error-message" id="error_contact">Contact Number must be exactly 11 digits</div>
                </div>
                <div class="form-group full-width">
                    <label>DLSU Email *</label>
                    <input type="email" name="Email" id="Email" required>
                    <div class="hint">Format: username@dlsu.edu.ph</div>
                    <div class="error-message" id="error_email">Email must be in format: username@dlsu.edu.ph</div>
                </div>
                <div class="form-group full-width">
                    <label>Scholarship(s)</label>
                    <div class="hint">Hold Ctrl (or Cmd on Mac) to select multiple</div>
                    <select name="Scholarship[]" id="ScholarshipSelect" class="form-control" multiple size="8">
                        <?php foreach($scholarship_options as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="Status" id="Status" required>
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="ON LEAVE">ON LEAVE</option>
                        <option value="ALUMNI">ALUMNI</option>
                    </select>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="save_scholar" class="btn btn-primary">Save Scholar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store existing ID numbers from database
const existingIds = <?php echo json_encode($existing_ids); ?>;
let originalIdNumber = null; // Store original ID when editing

// Get form elements
const form = document.getElementById('scholarForm');
const idNumber = document.getElementById('StudentNumber');
const lastName = document.getElementById('LastName');
const contactNumber = document.getElementById('ContactNumber');
const dlsuEmail = document.getElementById('Email');
const scholarshipSelect = document.getElementById('ScholarshipSelect');

// ID NUMBER VALIDATION - Only allow digits, exactly 8, and check for duplicates
idNumber.addEventListener('input', function(e) {
    // Remove non-digits
    this.value = this.value.replace(/\D/g, '');
    
    const errorMsgLength = document.getElementById('error_id');
    const errorMsgDuplicate = document.getElementById('error_duplicate');
    const isEdit = document.getElementById('is_edit').value === '1';
    
    // Reset error states
    errorMsgLength.classList.remove('show');
    errorMsgDuplicate.classList.remove('show');
    this.classList.remove('input-error');
    
    // Validate length
    if (this.value.length > 0 && this.value.length !== 8) {
        errorMsgLength.classList.add('show');
        this.classList.add('input-error');
    } 
    // Check for duplicate (only when adding new, not editing)
    else if (this.value.length === 8 && !isEdit && existingIds.includes(this.value)) {
        errorMsgDuplicate.classList.add('show');
        this.classList.add('input-error');
    }
});

// LAST NAME - Auto convert to UPPERCASE
lastName.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});

// CONTACT NUMBER VALIDATION - Only allow digits, exactly 11
contactNumber.addEventListener('input', function(e) {
    // Remove non-digits
    this.value = this.value.replace(/\D/g, '');
    
    // Validate length
    const errorMsg = document.getElementById('error_contact');
    if (this.value.length > 0 && this.value.length !== 11) {
        errorMsg.classList.add('show');
        this.classList.add('input-error');
    } else {
        errorMsg.classList.remove('show');
        this.classList.remove('input-error');
    }
});

// DLSU EMAIL VALIDATION
dlsuEmail.addEventListener('blur', function(e) {
    const emailPattern = /^[a-zA-Z0-9._-]+@dlsu\.edu\.ph$/;
    const errorMsg = document.getElementById('error_email');
    
    if (this.value && !emailPattern.test(this.value)) {
        errorMsg.classList.add('show');
        this.classList.add('input-error');
    } else {
        errorMsg.classList.remove('show');
        this.classList.remove('input-error');
    }
});

// FORM SUBMISSION VALIDATION
form.addEventListener('submit', function(e) {
    let isValid = true;
    const isEdit = document.getElementById('is_edit').value === '1';
    
    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

    // Validate ID Number length
    if (idNumber.value.length !== 8) {
        document.getElementById('error_id').classList.add('show');
        idNumber.classList.add('input-error');
        isValid = false;
    }
    
    // Check for duplicate ID (only when adding new)
    if (!isEdit && existingIds.includes(idNumber.value)) {
        document.getElementById('error_duplicate').classList.add('show');
        idNumber.classList.add('input-error');
        isValid = false;
    }

    // Validate Contact Number (if provided)
    if (contactNumber.value && contactNumber.value.length !== 11) {
        document.getElementById('error_contact').classList.add('show');
        contactNumber.classList.add('input-error');
        isValid = false;
    }

    // Validate DLSU Email
    const emailPattern = /^[a-zA-Z0-9._-]+@dlsu\.edu\.ph$/;
    if (!emailPattern.test(dlsuEmail.value)) {
        document.getElementById('error_email').classList.add('show');
        dlsuEmail.classList.add('input-error');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        alert('Please correct the errors in the form before submitting.');
        
        // Scroll to first error
        const firstError = document.querySelector('.input-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Fix scholarship dropdown - ensure it can be changed
scholarshipSelect.addEventListener('change', function() {
    this.disabled = false;
});

function adjustModalScale() {
    const modalContent = document.querySelector('#scholarModal .modal-content');
    if (!modalContent) return;

    modalContent.style.transform = ''; // Reset transform
    const modalHeight = modalContent.offsetHeight;
    const windowHeight = window.innerHeight;

    if (modalHeight > windowHeight) {
        const scale = windowHeight / modalHeight;
        modalContent.style.transform = `scale(${scale})`;
    }
}

window.addEventListener('resize', adjustModalScale);

function openModal(mode) {
    document.getElementById('scholarModal').classList.add('active');
    if(mode === 'add') {
        document.getElementById('modalTitle').innerText = "Add New Scholar";
        document.getElementById('is_edit').value = "0";
        form.reset();
        document.getElementById('StudentNumber').readOnly = false;
        originalIdNumber = null;
        
        // Clear all error messages
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }
    adjustModalScale();
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
    originalIdNumber = data.StudentNumber; // Store original ID
    
    document.getElementById('LastName').value = data.LastName.toUpperCase();
    document.getElementById('FirstName').value = data.FirstName;
    document.getElementById('MiddleName').value = data.MiddleName;
    document.getElementById('DegreeProgram').value = data.DegreeProgram;
    document.getElementById('Email').value = data.Email;
    
    // --- HANDLE MULTI-SELECT SCHOLARSHIP ---
    const scholarshipSelect = document.getElementById('ScholarshipSelect');
    const selectedScholarships = data.Scholarship.split(' & ').map(s => s.trim());
    
    // Reset all options
    for (let i = 0; i < scholarshipSelect.options.length; i++) {
        scholarshipSelect.options[i].selected = false;
    }
    
    // Select the correct options
    for (let i = 0; i < scholarshipSelect.options.length; i++) {
        if (selectedScholarships.includes(scholarshipSelect.options[i].value)) {
            scholarshipSelect.options[i].selected = true;
        }
    }
    // --- END HANDLE MULTI-SELECT ---
    
    document.getElementById('Status').value = data.Status;
    document.getElementById('ContactNumber').value = data.ContactNumber;
    
    // Clear all error messages when editing
    document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}
</script>

</body>
</html>