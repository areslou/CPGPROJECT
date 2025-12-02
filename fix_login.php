<?php
// fix_login.php
require_once 'config.php';

try {
    echo "<h2>Fixing User Accounts...</h2>";

    // 1. Clear the table of broken accounts
    $conn->exec("DELETE FROM users");
    echo "✅ Old/Broken accounts removed.<br>";

    // 2. Create Admin Account (admin123)
    $admin_email = 'admin@lss.org';
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT); // Generates a REAL hash
    
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$admin_email, $admin_pass]);
    echo "✅ Admin fixed: <strong>admin@lss.org</strong> / <strong>admin123</strong><br>";

    // 3. Create Student Account (student123)
    $stud_email = 'larry_lanada@dlsu.edu.ph';
    $stud_pass = password_hash('student123', PASSWORD_DEFAULT); // Generates a REAL hash
    $stud_id = 12278440; // Larry's ID from your CSV

    $stmt = $conn->prepare("INSERT INTO users (email, password, role, student_number) VALUES (?, ?, 'student', ?)");
    $stmt->execute([$stud_email, $stud_pass, $stud_id]);
    echo "✅ Student fixed: <strong>larry_lanada@dlsu.edu.ph</strong> / <strong>student123</strong><br>";

    echo "<hr><h3 style='color:green'>SUCCESS!</h3> <a href='login.php'>Click here to Login</a>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>