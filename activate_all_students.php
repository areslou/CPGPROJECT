<?php
require_once 'config.php';

// Activate all students with default password pattern: FirstName LastName + StudentNumber
$students = $conn->query("SELECT StudentNumber, FirstName, LastName FROM StudentDetails WHERE password_hash IS NULL")->fetchAll();

foreach ($students as $student) {
    $defaultPassword = $student['FirstName'] . ' ' . $student['LastName'] . $student['StudentNumber'];
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE StudentDetails SET password_hash = ? WHERE StudentNumber = ?");
    $stmt->execute([$hashedPassword, $student['StudentNumber']]);
    
    echo "Activated: {$student['FirstName']} {$student['LastName']} - Password: $defaultPassword<br>";
}

echo "<br>All students activated!";