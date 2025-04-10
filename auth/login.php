<?php
session_start();
require '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from the database
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password and check user role
    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on user role
        if ($user['role'] === 'admin') {
            header('Location: ../admin/admin_dashboard.php');
        } elseif ($user['role'] === 'teacher') {
            header('Location: ../teacher/teacher_dashboard.php');
        } elseif ($user['role'] === 'student') {
            header('Location: ../student/student_dashboard.php');
        } else {
            echo "Invalid role.";
        }
        exit();
    } else {
        echo "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нэвтрэх | Premium дизайн</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --primary-light: #4895ef;
            --secondary: #f72585;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #adb5bd;
            --success: #4ade80;
            --error: #f87171;
            --border-radius: 16px;
            --shadow-sm: 0 2px 12px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 15px 50px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e6ecff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
            overflow: hidden;
            position: relative;
        }

        .background-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(76, 201, 240, 0.1) 100%);
            filter: blur(30px);
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
        }

        .shape-2 {
            width: 600px;
            height: 600px;
            bottom: -150px;
            right: -150px;
            animation: float 10s ease-in-out infinite reverse;
            animation-delay: 1s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(5deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(67, 97, 238, 0.2);
        }

        .card-header {
            padding: 40px 40px 30px;
            text-align: center;
            position: relative;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 32px;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            color: var(--gray);
            font-size: 15px;
            font-weight: 500;
        }

        .card-body {
            padding: 30px 40px 40px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
            text-align: left;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid rgba(173, 181, 189, 0.3);
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
            background-color: rgba(248, 249, 250, 0.5);
            color: var(--dark);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
            padding-left: 55px;
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            margin-right: 12px;
            font-size: 18px;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3) 0%,
                rgba(255, 255, 255, 0) 60%
            );
            transform: translateY(100%);
            transition: var(--transition);
        }

        .btn:hover::after {
            transform: translateY(0);
        }

        .register-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 25px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 15px;
            padding: 8px 16px;
            border-radius: var(--border-radius);
        }

        .register-link:hover {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            transform: translateX(-5px);
        }

        .register-link i {
            margin-right: 10px;
            transition: var(--transition);
        }

        .register-link:hover i {
            transform: translateX(-3px);
        }

        /* Анимаци */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card {
            animation: fadeInUp 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        /* Responsive дизайн */
        @media (max-width: 768px) {
            .glass-card {
                max-width: 100%;
            }
            
            .card-header, .card-body {
                padding: 30px;
            }
            
            h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .card-header, .card-body {
                padding: 25px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .form-control {
                padding: 14px 16px 14px 45px;
            }
            
            .input-icon {
                left: 15px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    
    <div class="glass-card">
        <div class="card-header">
            <h1>Нэвтрэх</h1>
            <p class="card-subtitle">Системд нэвтрэхдээ өөрийн мэдээллээ оруулна уу</p>
        </div>
        
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label for="email">Имэйл хаяг</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Нууц үг</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Нэвтрэх
                </button>
            </form>
            
            <a href="register.php" class="register-link">
                <i class="fas fa-user-plus"></i> Бүртгүүлэх
            </a>
        </div>
    </div>

    <script>
        // Form control фокус хийхэд icon өөрчлөгдөх
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            const icon = input.previousElementSibling;
            
            input.addEventListener('focus', () => {
                icon.style.color = 'var(--primary)';
                icon.style.transform = 'translateY(-50%) scale(1.2)';
            });
            
            input.addEventListener('blur', () => {
                icon.style.color = 'var(--gray)';
                icon.style.transform = 'translateY(-50%) scale(1)';
            });
        });
    </script>
</body>
</html>