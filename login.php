<?php
// login.php
ob_start(); // Start output buffering to prevent header errors
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            // 1. CHECK FOR ADMIN (in 'users' table)
            $stmtAdmin = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmtAdmin->execute([$email]);
            $admin = $stmtAdmin->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Admin Login Success
                session_regenerate_id(true);
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = 'admin';
                
                // Clear output buffer before redirect
                ob_end_clean();
                header('Location: admin_dashboard.php');
                exit;
            }

            // 2. CHECK FOR STUDENT (in 'StudentDetails' table)
            $stmtStudent = $conn->prepare("SELECT * FROM StudentDetails WHERE Email = ?");
            $stmtStudent->execute([$email]);
            $student = $stmtStudent->fetch();

            if ($student) {
                // IMPORTANT: Check if password_hash exists
                if (empty($student['password_hash'])) {
                    $error = "Your account has not been activated yet. Please check your email for the activation link.";
                } 
                // Check Password hash
                else if (password_verify($password, $student['password_hash'])) {
                    // Student Login Success
                    session_regenerate_id(true);
                    $_SESSION['student_number'] = $student['StudentNumber'];
                    $_SESSION['email'] = $student['Email'];
                    $_SESSION['role'] = 'student';
                    
                    // Clear output buffer before redirect
                    ob_end_clean();
                    header('Location: user_profile.php');
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Email not found in the system.";
            }

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LSS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: url('Main Page Background.gif') no-repeat center center fixed; 
            background-size: cover;
            display: flex; align-items: center; justify-content: center; 
            height: 100vh; 
        }
        body::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); z-index: -1;
        }
        .login-container { 
            background: rgba(255, 255, 255, 0.95); padding: 2.5rem; 
            border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); 
            width: 100%; max-width: 400px; text-align: center; backdrop-filter: blur(5px); 
        }
        h1 { color: #00A36C; margin-bottom: 0.5rem; font-size: 2rem; }
        .subtitle { color: #555; margin-bottom: 2rem; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.25rem; text-align: left; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; font-size: 0.9rem; }
        input { 
            width: 100%; padding: 0.85rem; border: 1px solid #ddd; 
            border-radius: 6px; font-size: 1rem; transition: border-color 0.3s;
        }
        input:focus { border-color: #00A36C; outline: none; }
        button { 
            width: 100%; padding: 0.85rem; background: #00A36C; color: white; 
            border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; 
            cursor: pointer; transition: background 0.3s, transform 0.2s; margin-top: 1rem;
        }
        button:hover { background: #008f5d; transform: translateY(-2px); }
        button:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .error { 
            background: #fee2e2; color: #dc2626; padding: 0.75rem; 
            border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #fecaca;
        }
        .success {
            background: #d4edda; color: #155724; padding: 0.75rem; 
            border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>LSS System Login</h1>
        <p class="subtitle">Welcome back! Please login to continue.</p>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="name@dlsu.edu.ph" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" id="loginBtn">Login</button>
        </form>
    </div>

    <script>
        // Prevent double submission
        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.textContent = 'Logging in...';
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>