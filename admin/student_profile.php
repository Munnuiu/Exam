<?php
session_start();
require '../database/db.php';

// Нэвтрээгүй хэрэглэгчийг шалгах
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Хэрэв админ бол сурагчийн ID-г URL-ээс авах
if ($_SESSION['role'] === 'admin' && isset($_GET['id'])) {
    $student_id = $_GET['id'];
} else {
    $student_id = $_SESSION['user_id'];
}

// Сурагчийн мэдээлэл авах
$stmt = $pdo->prepare("
    SELECT u.*, c.name as class_name, m.name as major_name 
    FROM users u
    LEFT JOIN classes c ON u.class_id = c.id
    LEFT JOIN majors m ON c.major_id = m.id
    WHERE u.id = ? AND u.role = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Сурагч олдсонгүй!";
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'majors.php' : '../dashboard.php'));
    exit();
}

$profile_picture = $student['profile_picture'] ?? 'default.jpg';

// Профайл шинэчлэх (зөвхөн тухайн хэрэглэгч эсвэл админ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && 
    ($_SESSION['user_id'] == $student_id || $_SESSION['role'] === 'admin')) {
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Зураг шинэчлэх
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // Хуучин зураг устгах (default.jpg биш бол)
            if ($profile_picture !== 'default.jpg' && file_exists($upload_dir . $profile_picture)) {
                unlink($upload_dir . $profile_picture);
            }
            $profile_picture = $new_filename;
        }
    }
    
    // Мэдээлэл шинэчлэх
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $profile_picture, $student_id]);
    
    $_SESSION['success_message'] = "Профайл амжилттай шинэчлэгдлээ!";
    header("Location: student_profile.php" . ($_SESSION['role'] === 'admin' ? "?id=$student_id" : ""));
    exit();
}

// Нууц үг солих (зөвхөн тухайн хэрэглэгч)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && $_SESSION['user_id'] == $student_id) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $student['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $student_id]);
            
            $_SESSION['success_message'] = "Нууц үг амжилттай солигдлоо!";
        } else {
            $_SESSION['error_message'] = "Шинэ нууц үг таарахгүй байна!";
        }
    } else {
        $_SESSION['error_message'] = "Одоогийн нууц үг буруу байна!";
    }
    
    header("Location: student_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['full_name']) ?> - Сурагчийн профайл</title>
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e5177a;
            transform: translateY(-2px);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-img-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--light-gray);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 0.9rem;
            color: white;
            background: var(--primary);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .file-input {
            display: none;
        }
        
        .file-label {
            display: block;
            padding: 10px 15px;
            background: var(--light);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-label:hover {
            background: var(--light-gray);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: rgba(76, 201, 240, 0.2);
            color: #0a9396;
            border-left: 4px solid #0a9396;
        }
        
        .alert-danger {
            background: rgba(248, 37, 133, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .student-id {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Сурагчийн профайл</h1>
            <a href="<?php 
                echo $_SESSION['role'] === 'admin' ? 
                'class_details.php?id='.$student['class_id'] : 
                '../dashboard.php'; 
            ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Буцах
            </a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="card">
                <h2 class="card-title">Профайлын мэдээлэл</h2>
                
                <div class="profile-info">
                    <div class="profile-img-container">
                        <img src="../uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-img">
                    </div>
                    <h3 class="user-name"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                    <span class="user-role">Сурагч</span>
                    <?php if (!empty($student['student_id'])): ?>
                        <div class="student-id">ID: <?php echo htmlspecialchars($student['student_id']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($_SESSION['user_id'] == $student_id || $_SESSION['role'] === 'admin'): ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Бүтэн нэр</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Имэйл</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Профайл зураг</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="file-input" accept="image/*">
                        <label for="profile_picture" class="file-label">
                            <i class="fas fa-camera"></i> Зураг сонгох
                        </label>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Хадгалах
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 class="card-title">Сурагчийн мэдээлэл</h2>
                
                <div class="info-item">
                    <span class="info-label">Бүртгүүлсэн огноо</span>
                    <p class="info-value"><?php echo date('Y-m-d H:i', strtotime($student['created_at'])); ?></p>
                </div>
                
                <?php if (!empty($student['last_login'])): ?>
                <div class="info-item">
                    <span class="info-label">Сүүлийн нэвтрэлт</span>
                    <p class="info-value"><?php echo date('Y-m-d H:i', strtotime($student['last_login'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($student['class_name'])): ?>
                <div class="info-item">
                    <span class="info-label">Мэргэжил</span>
                    <p class="info-value"><?php echo htmlspecialchars($student['major_name']); ?></p>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Анги</span>
                    <p class="info-value"><?php echo htmlspecialchars($student['class_name']); ?></p>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Сурагчийн ID</span>
                    <p class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['user_id'] == $student_id): ?>
                <h2 class="card-title" style="margin-top: 30px;">Нууц үг солих</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Одоогийн нууц үг</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Шинэ нууц үг</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Шинэ нууц үгээ баталгаажуулна уу</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Нууц үг солих
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>