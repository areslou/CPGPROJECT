<?php
// setup_passwords.php
require_once 'config.php';

echo "<h1>⚙️ System Setup: Generating Student Passwords</h1>";
echo "<p>Rule: Password = FirstName + StudentNumber (e.g., 'Adrian Mateo12278440')</p><hr>";

try {
    // 1. Get all students
    $stmt = $conn->query("SELECT StudentNumber, FirstName, LastName FROM StudentDetails");
    $students = $stmt->fetchAll();

    $count = 0;

    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr><th>Student</th><th>Generated Password (Copy this to login)</th><th>Status</th></tr>";

    foreach ($students as $student) {
        $s_num = $student['StudentNumber'];
        $f_name = $student['FirstName'];
        $l_name = $student['LastName'];

        // --- THE PASSWORD LOGIC ---
        // Format: FirstName + StudentNumber (Exact formatting including spaces in First Name)
        // Example: "Adrian Mateo" + "12278440" = "Adrian Mateo12278440"
        $plain_password = $f_name . $s_num;

        // Securely hash it
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        // Update the database
        $update = $conn->prepare("UPDATE StudentDetails SET password_hash = ? WHERE StudentNumber = ?");
        $update->execute([$hashed_password, $s_num]);

        echo "<tr>";
        echo "<td>{$l_name}, {$f_name}</td>";
        echo "<td style='font-family:monospace; color:blue;'>{$plain_password}</td>";
        echo "<td style='color:green;'>✅ Updated</td>";
        echo "</tr>";
        
        $count++;
    }
    echo "</table>";

    echo "<br><h3>✅ Success! Updated $count student passwords.</h3>";
    echo "<p style='color:red;'><strong>IMPORTANT:</strong> Please delete this file (setup_passwords.php) after use for security.</p>";
    echo "<a href='login.php'><button style='padding:10px 20px; cursor:pointer;'>Go to Login Page</button></a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>