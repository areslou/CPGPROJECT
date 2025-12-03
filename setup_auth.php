<?php
// setup_auth.php
//hello
require_once 'config.php';

try {
    // 1. Create Users Table in db1
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'student') NOT NULL,
        student_number INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_number) REFERENCES StudentDetails(StudentNumber) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    echo "✅ Users table created in db1.<br>";

    // 2. Create Admin Account
    // Credentials: admin@lss.org / admin123
    $admin_email = 'admin@lss.org';
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT IGNORE INTO users (email, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$admin_email, $admin_pass]);
    echo "✅ Admin account created (Email: <strong>admin@lss.org</strong> | Pass: <strong>admin123</strong>)<br>";

    // 3. Create Sample Student Accounts (Linking to students from StudentDetails table)
    
    // Student 1: Larry Lañada - Credentials: larry_lanada@dlsu.edu.ph / student123
    $stmt = $conn->prepare("INSERT IGNORE INTO users (email, password, role, student_number) VALUES (?, ?, 'student', ?)");
    $stmt->execute(['larry_lanada@dlsu.edu.ph', password_hash('student123', PASSWORD_DEFAULT), 12278440]);
    echo "✅ Student account created: <strong>larry_lanada@dlsu.edu.ph</strong> / student123<br>";

    // Student 2: Nathan Timothy Polancos - Credentials: nathan_polancos@dlsu.edu.ph / student123
    $stmt->execute(['nathan_polancos@dlsu.edu.ph', password_hash('student123', PASSWORD_DEFAULT), 12333115]);
    echo "✅ Student account created: <strong>nathan_polancos@dlsu.edu.ph</strong> / student123<br>";

    // Student 3: Kurt Luis Villasoto - Credentials: kurt_villasoto@dlsu.edu.ph / student123
    $stmt->execute(['kurt_villasoto@dlsu.edu.ph', password_hash('student123', PASSWORD_DEFAULT), 12320757]);
    echo "✅ Student account created: <strong>kurt_villasoto@dlsu.edu.ph</strong> / student123<br>";

    echo "<br>🎉 <strong>Setup Complete!</strong><br>";
    echo "✅ Database: db1<br>";
    echo "✅ Tables: users, StudentDetails<br>";
    echo "✅ Sample accounts created<br><br>";
    echo "You can now:<br>";
    echo "1. Delete this file (setup_auth.php)<br>";
    echo "2. Go to <a href='login.php'><strong>login.php</strong></a> to start using the system<br>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>