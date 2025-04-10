<?php
session_start();
require '../database/db.php';

$error = ''; // Variable to store error messages

// Fetch majors for student selection
$majors = $pdo->query('SELECT id, name FROM majors')->fetchAll();

// Fetch departments for teacher selection
$departments = $pdo->query('SELECT id, name FROM departments')->fetchAll();

// Fetch classes for student selection (we'll use this for the AJAX endpoint)
$classes = [];
if (isset($_GET['major_id'])) {
    $majorId = (int)$_GET['major_id'];
    $stmt = $pdo->prepare('SELECT id, name, year FROM classes WHERE major_id = ?');
    $stmt->execute([$majorId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($classes);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department_id = $_POST['department_id'] ?? null;
    $major_id = $_POST['major_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;

    // Validate input fields
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $error = 'Бүх талбарыг бөглөнө үү.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Имэйл хаяг буруу байна.';
    } elseif ($role === 'teacher' && empty($department_id)) {
        $error = 'Багш бол тэнхимээ сонгоно уу.';
    } elseif ($role === 'student' && empty($major_id)) {
        $error = 'Оюутан бол мэргэжилээ сонгоно уу.';
    } elseif ($role === 'student' && empty($class_id)) {
        $error = 'Оюутан бол ангиа сонгоно уу.';
    } else {
        // Check if the email already exists in the database
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $error = 'Имэйл хаяг бүртгэлтэй байна.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare data based on role
            $data = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'password' => $hashed_password,
                'role' => $role,
                'department_id' => $role === 'teacher' ? $department_id : null,
                'class_id' => $role === 'student' ? $class_id : null
            ];

            // Insert the new user into the database
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password, role, department_id, class_id) 
                                 VALUES (:full_name, :email, :phone, :password, :role, :department_id, :class_id)');
            $stmt->execute($data);

            // Redirect to the login page after successful registration
            header('Location: login.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бүртгүүлэх | Premium дизайн</title>
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
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #4ade80;
            --error: #f87171;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
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
            filter: blur(40px);
            opacity: 0.8;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation: float 12s ease-in-out infinite;
        }

        .shape-2 {
            width: 600px;
            height: 600px;
            bottom: -150px;
            right: -150px;
            animation: float 15s ease-in-out infinite reverse;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 30%;
            animation: float 10s ease-in-out infinite;
            animation-delay: 1s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(3deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(67, 97, 238, 0.2);
        }

        .card-header {
            padding: 40px 40px 25px;
            text-align: center;
            position: relative;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(76, 201, 240, 0.05) 100%);
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
            opacity: 0.8;
        }

        h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .card-body {
            padding: 30px 35px 35px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 13px;
            text-align: left;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
            transition: var(--transition);
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: var(--transition);
            background-color: white;
            color: var(--dark);
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
            padding-left: 45px;
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 35px;
        }

        .error-message {
            color: var(--error);
            font-size: 13px;
            margin-top: 6px;
            text-align: left;
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: rgba(248, 113, 113, 0.1);
            border-radius: var(--border-radius);
            animation: fadeIn 0.3s ease-out;
        }

        .error-message i {
            margin-right: 8px;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            margin-right: 10px;
            font-size: 16px;
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
                rgba(255, 255, 255, 0.2) 0%,
                rgba(255, 255, 255, 0) 60%
            );
            transform: translateY(0) rotate(15deg);
            transition: var(--transition);
        }

        .btn:hover::after {
            transform: translateY(-50%) rotate(15deg);
        }

        .login-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 14px;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            width: 100%;
        }

        .login-link:hover {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.08);
        }

        .login-link i {
            margin-right: 8px;
            transition: var(--transition);
            font-size: 14px;
        }

        .login-link:hover i {
            transform: translateX(-2px);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .glass-card {
            animation: fadeInUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards;
        }

        /* Dynamic fields */
        .dynamic-field {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .glass-card {
                max-width: 100%;
            }
            
            .card-header, .card-body {
                padding: 30px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .shape-1, .shape-2, .shape-3 {
                display: none;
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
                padding: 12px 12px 12px 40px;
                font-size: 13px;
            }
            
            .input-icon {
                left: 12px;
                font-size: 15px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .password-toggle {
                right: 12px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="glass-card">
        <div class="card-header">
            <h1>Бүртгүүлэх</h1>
            <p class="card-subtitle">Шинэ хэрэглэгч үүсгэх</p>
        </div>
        
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm">
                <div class="form-group">
                    <label for="full_name">Бүтэн нэр</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Жон Доу">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Имэйл хаяг</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="example@school.edu">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Утасны дугаар</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="form-control" required placeholder="99112233">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Нууц үг</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Хамгийн багадаа 8 тэмдэгт">
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Хэрэглэгчийн төрөл</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Сонгох...</option>
                        <option value="admin">Админ</option>
                        <option value="teacher">Багш</option>
                        <option value="student">Оюутан</option>
                    </select>
                </div>
                
                <!-- Department Selection (for teachers) -->
                <div class="form-group dynamic-field" id="departmentField">
                    <label for="department_id">Тэнхим</label>
                    <select id="department_id" name="department_id" class="form-control">
                        <option value="">Тэнхим сонгох...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Major Selection (for students) -->
                <div class="form-group dynamic-field" id="majorField">
                    <label for="major_id">Мэргэжил</label>
                    <select id="major_id" name="major_id" class="form-control">
                        <option value="">Мэргэжил сонгох...</option>
                        <?php foreach ($majors as $major): ?>
                            <option value="<?= $major['id'] ?>"><?= htmlspecialchars($major['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Class Selection (for students) -->
                <div class="form-group dynamic-field" id="classField">
                    <label for="class_id">Анги бүлэг</label>
                    <select id="class_id" name="class_id" class="form-control" disabled>
                        <option value="">Эхлээд мэргэжилээ сонгоно уу...</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Бүртгүүлэх
                </button>
            </form>
            
            <a href="login.php" class="login-link">
                <i class="fas fa-sign-in-alt"></i> Нэвтрэх хуудас руу буцах
            </a>
        </div>
    </div>

    <script>
        // Form control фокус хийхэд icon өөрчлөгдөх
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            const icon = input.previousElementSibling;
            
            if (icon && icon.classList.contains('input-icon')) {
                input.addEventListener('focus', () => {
                    icon.style.color = '#4361ee';
                    icon.style.transform = 'translateY(-50%) scale(1.1)';
                });
                
                input.addEventListener('blur', () => {
                    icon.style.color = '#6c757d';
                    icon.style.transform = 'translateY(-50%) scale(1)';
                });
            }
        });

        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Role сонгоход dynamic field харуулах
        const roleSelect = document.getElementById('role');
        const departmentField = document.getElementById('departmentField');
        const majorField = document.getElementById('majorField');
        const classField = document.getElementById('classField');
        const majorSelect = document.getElementById('major_id');
        const classSelect = document.getElementById('class_id');

        roleSelect.addEventListener('change', function() {
            // Hide all dynamic fields first
            departmentField.style.display = 'none';
            majorField.style.display = 'none';
            classField.style.display = 'none';
            
            // Clear selections and disable class select
            document.getElementById('department_id').value = '';
            majorSelect.value = '';
            classSelect.value = '';
            classSelect.disabled = true;
            classSelect.innerHTML = '<option value="">Эхлээд мэргэжилээ сонгоно уу...</option>';
            
            // Show relevant fields based on role
            if (this.value === 'teacher') {
                departmentField.style.display = 'block';
                document.getElementById('department_id').required = true;
            } else if (this.value === 'student') {
                majorField.style.display = 'block';
                majorSelect.required = true;
            }
        });

        // When major is selected, fetch classes for that major
        majorSelect.addEventListener('change', function() {
            if (this.value) {
                // Show loading state
                classSelect.innerHTML = '<option value="">Анги дуудаж байна...</option>';
                classSelect.disabled = true;
                classField.style.display = 'block';
                
                // Fetch classes from server
                fetch(`?major_id=${this.value}&ajax=1`)
                    .then(response => response.json())
                    .then(classes => {
                        classSelect.innerHTML = '<option value="">Анги сонгох...</option>';
                        classes.forEach(classItem => {
                            const option = document.createElement('option');
                            option.value = classItem.id;
                            option.textContent = `${classItem.name} анги (${classItem.year})`;
                            classSelect.appendChild(option);
                        });
                        classSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        classSelect.innerHTML = '<option value="">Анги ачаалж чадсангүй</option>';
                    });
            } else {
                classSelect.innerHTML = '<option value="">Эхлээд мэргэжилээ сонгоно уу...</option>';
                classSelect.disabled = true;
                classField.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const role = roleSelect.value;
            
            if (role === 'teacher' && !document.getElementById('department_id').value) {
                e.preventDefault();
                alert('Тэнхим сонгоно уу!');
            } else if (role === 'student' && !majorSelect.value) {
                e.preventDefault();
                alert('Мэргэжил сонгоно уу!');
            } else if (role === 'student' && !classSelect.value) {
                e.preventDefault();
                alert('Анги бүлэг сонгоно уу!');
            }
        });
    </script>
</body>
</html>