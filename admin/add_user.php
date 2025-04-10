<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, Password, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$full_name, $email, $phone, $password, $role]);

    header('Location: manage_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хэрэглэгч нэмэх | Админ самбар</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --danger: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .add-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        .add-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .add-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .add-header i {
            font-size: 1.3rem;
        }
        
        .add-form {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            padding-left: 40px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .select-role {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
            padding-left: 15px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: var(--gray);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
            margin-top: 15px;
            width: 100%;
        }
        
        .btn-secondary:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .form-footer {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }
        
        .back-link {
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 15px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .toggle-icon {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
            color: var(--gray);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 576px) {
            .add-container {
                max-width: 100%;
            }
            
            .add-header h2 {
                font-size: 1.3rem;
            }
            
            .add-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="add-container">
        <div class="add-header">
            <h2><i class="fas fa-user-plus"></i> Шинэ хэрэглэгч нэмэх</h2>
        </div>
        
        <form class="add-form" method="POST">
            <div class="form-group">
                <label for="full_name"><i class="fas fa-user"></i> Бүтэн нэр</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Имэйл хаяг</label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Утас</label>
                <i class="fas fa-phone input-icon"></i>
                <input type="phone" id="phone" name="phone" class="form-control" required>
            </div>
            
            <div class="form-group password-toggle">
                <label for="password"><i class="fas fa-lock"></i> Нууц үг</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" class="form-control" required>
                <i class="fas fa-eye toggle-icon" onclick="togglePassword('password', this)"></i>
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Хэрэглэгчийн үүрэг</label>
                <select id="role" name="role" class="form-control select-role" required>
                    <option value="admin">Админ</option>
                    <option value="teacher">Багш</option>
                    <option value="student">Сурагч</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Хэрэглэгч нэмэх
            </button>
            
            <div class="form-footer">
                <a href="manage_users.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Хэрэглэгчийг удирдах руу буцах
                </a>
            </div>
        </form>
    </div>

    <script>
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    </script>
</body>
</html>