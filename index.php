<?php
session_start();

// Хэрэв хэрэглэгч аль хэдийн нэвтэрсэн бол үүргийн дагуу чиглүүлнэ
if (isset($_SESSION['user_id'])) {
    $redirect_page = match ($_SESSION['role']) {
        'admin' => '../admin/dashboard.php',
        'teacher' => '../teacher/dashboard.php',
        'student' => '../student/dashboard.php',
        default => '../auth/login.php',
    };
    header("Location: $redirect_page");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шалгалтын систем</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --transition: all 0.3s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 900px;
            background: rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }

        .btn {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
            background: var(--accent);
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: white;
            color: var(--primary);
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem;
            }
            h1 {
                font-size: 2rem;
            }
            p {
                font-size: 1rem;
            }
            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Онлайн шалгалтын систем</h1>
        <p>Шинэлэг, ухаалаг онлайн шалгалтын системд нэгдээрэй. Шалгалт өгөх, асуултууд удирдах, хялбар хяналт тавих боломжтой.</p>

        <div class="btn-group">
            <a href="auth/login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Нэвтрэх</a>
            <a href="auth/register.php" class="btn"><i class="fas fa-user-plus"></i> Бүртгүүлэх</a>
        </div>
    </div>

</body>
</html>
