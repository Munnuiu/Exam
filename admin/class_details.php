<?php
session_start();
require '../database/db.php';

// Нэвтрээгүй хэрэглэгчийг шалгах
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Зөвхөн админ хандах эрхтэй эсэхийг шалгах
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

// ID параметр шалгах
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: majors.php');
    exit();
}

$class_id = $_GET['id'];

// Ангийн мэдээлэл авах
$stmt = $pdo->prepare("
    SELECT c.*, m.name as major_name 
    FROM classes c 
    JOIN majors m ON c.major_id = m.id 
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

// Анги олдсонгүй бол
if (!$class) {
    $_SESSION['error_message'] = "Анги олдсонгүй!";
    header('Location: majors.php');
    exit();
}

// Ангид хамаарах сурагчдыг авах
$students = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.student_id, u.profile_picture, u.last_login
    FROM users u
    WHERE u.class_id = ? AND u.role = 'student'
    ORDER BY u.full_name ASC
");
$students->execute([$class_id]);
$student_list = $students->fetchAll();

// Сурагчийг ангиас хасах
if (isset($_GET['remove_student'])) {
    $student_id = $_GET['remove_student'];
    
    $stmt = $pdo->prepare("UPDATE users SET class_id = NULL, student_id = NULL WHERE id = ? AND class_id = ?");
    $stmt->execute([$student_id, $class_id]);
    
    $_SESSION['success_message'] = "Сурагчийг ангиас амжилттай хаслаа!";
    header("Location: class_details.php?id=$class_id");
    exit();
}

// Ангийн мэдээлэл шинэчлэх
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_class'])) {
    $name = trim($_POST['name']);
    $year = trim($_POST['year']);

    if (!empty($name) && !empty($year)) {
        $stmt = $pdo->prepare("UPDATE classes SET name = ?, year = ? WHERE id = ?");
        $stmt->execute([$name, $year, $class_id]);
        
        $_SESSION['success_message'] = "Ангийн мэдээлэл амжилттай шинэчлэгдлээ!";
        header("Location: class_details.php?id=$class_id");
        exit();
    } else {
        $error = "Ангийн нэр болон он оруулна уу!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ангийн дэлгэрэнгүй</title>
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
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #3ab4d8;
            transform: translateY(-2px);
        }
        
        .btn i {
            margin-right: 8px;
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .table th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
        }
        
        .table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
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
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background: rgba(76, 201, 240, 0.1);
            color: #0a9396;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: var(--light);
            padding: 15px;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-class"></i> Ангийн дэлгэрэнгүй</h1>
            <a href="majors.php" class="btn">
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
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="card-title"><?php echo htmlspecialchars($class['name']); ?> анги</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Мэргэжил</div>
                    <div class="info-value"><?php echo htmlspecialchars($class['major_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Он</div>
                    <div class="info-value"><?php echo htmlspecialchars($class['year']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Сурагчдын тоо</div>
                    <div class="info-value"><?php echo count($student_list); ?></div>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name" class="form-label">Ангийн нэр</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($class['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="year" class="form-label">Он</label>
                    <input type="number" id="year" name="year" class="form-control" 
                           value="<?php echo htmlspecialchars($class['year']); ?>" required>
                </div>
                
                <button type="submit" name="update_class" class="btn btn-primary">
                    <i class="fas fa-save"></i> Ангийн мэдээлэл шинэчлэх
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Ангийн сурагчид</h2>
            
            <?php if (empty($student_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Сурагч бүртгэгдээгүй байна</h3>
                    <p>Сурагчдыг ангид бүртгэх хэсгээс нэмнэ үү</p>
                    <a href="majors.php#students" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Сурагч бүртгэх
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Зураг</th>
                            <th>Нэр</th>
                            <th>Имэйл</th>
                            <th>Сурагчийн ID</th>
                            <th>Сүүлийн нэвтрэлт</th>
                            <th>Үйлдэл</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_list as $index => $student): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <img src="../uploads/<?php echo htmlspecialchars($student['profile_picture'] ?: 'default.jpg'); ?>" 
                                         alt="Profile" class="profile-img">
                                </td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td>
                                    <?php if ($student['last_login']): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($student['last_login'])); ?>
                                    <?php else: ?>
                                        Нэвтрээгүй
                                    <?php endif; ?>
                                </td>
                                <td class="action-btns">
                                    <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-eye"></i> Үзэх
                                    </a>
                                    <a href="class_details.php?id=<?php echo $class_id; ?>&remove_student=<?php echo $student['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Та энэ сурагчийг ангиас хасахдаа итгэлтэй байна уу?');">
                                        <i class="fas fa-user-minus"></i> Хасах
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Устгах үйлдлийг баталгаажуулах
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Та энэ сурагчийг ангиас хасахдаа итгэлтэй байна уу?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>