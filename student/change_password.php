<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require '../database/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        // Get current password hash from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$new_password_hash, $_SESSION['user_id']]);
            
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #2c3e50;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }

        h2 {
            color: #34495e;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 2px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            cursor: pointer;
            color: #7f8c8d;
        }

        /* Button Styles */
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fde8e8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert-success {
            background-color: #e8f8f0;
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }

        /* Back Link */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Password Strength Meter */
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: width 0.3s ease, background 0.3s ease;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-key"></i> Change Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="passwordForm">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required oninput="checkPasswordStrength()">
                <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                <div class="password-strength">
                    <div class="password-strength-fill" id="passwordStrength"></div>
                </div>
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                    Password must be at least 8 characters long
                </small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
            </div>
            
            <button type="submit" class="btn">Change Password</button>
        </form>
        
        <a href="student_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[$@#&!]+/)) strength += 1;
            
            // Update strength meter
            const width = (strength / 5) * 100;
            strengthBar.style.width = width + '%';
            
            // Update color based on strength
            if (strength <= 2) {
                strengthBar.style.background = '#e74c3c'; // Weak
            } else if (strength <= 4) {
                strengthBar.style.background = '#f39c12'; // Medium
            } else {
                strengthBar.style.background = '#2ecc71'; // Strong
            }
        }
        
        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
            }
        });
    </script>
</body>
</html>