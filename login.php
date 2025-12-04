<?php
// login.php (With Debugging)
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // DEBUGGING: Print what we found
            if (!$user) {
                $error = "Debug: User email not found in database.";
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['student_number'] = $user['student_number'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: student_dashboard.php'); // Ensure this matches your file name
                    }
                    exit;
                } else {
                    $error = "Debug: Password verification failed.";
                }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">in 
    <title>Login - LSS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            /* CHANGE THIS LINE TO YOUR LOCAL FILE NAME */
            background: url('Main Page Background.gif') no-repeat center center fixed; 
            background-size: cover;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
        }

        /* Overlay to darken background for better readability */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Black overlay with 50% opacity */
            z-index: -1;
        }

        .login-container { 
            background: rgba(255, 255, 255, 0.95); /* Slightly transparent white card */
            padding: 2.5rem; 
            border-radius: 12px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); 
            width: 100%; 
            max-width: 400px; 
            text-align: center;
            backdrop-filter: blur(5px); /* Nice blur effect behind card */
        }

        h1 { color: #00A36C; margin-bottom: 0.5rem; font-size: 2rem; }
        .subtitle { color: #555; margin-bottom: 2rem; font-size: 0.95rem; }
        
        .form-group { margin-bottom: 1.25rem; text-align: left; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; font-size: 0.9rem; }
        
        input { 
            width: 100%; 
            padding: 0.85rem; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 1rem; 
            transition: border-color 0.3s;
        }
        input:focus { border-color: #00A36C; outline: none; }

        button { 
            width: 100%; 
            padding: 0.85rem; 
            background: #00A36C; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 1.1rem; 
            font-weight: bold;
            cursor: pointer; 
            transition: background 0.3s, transform 0.2s; 
            margin-top: 1rem;
        }
        button:hover { background: #008f5d; transform: translateY(-2px); }
        
        .error { 
            background: #fee2e2; 
            color: #dc2626; 
            padding: 0.75rem; 
            border-radius: 6px; 
            margin-bottom: 1.5rem; 
            font-size: 0.9rem; 
            border: 1px solid #fecaca;
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

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="name@dlsu.edu.ph">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>