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

$major_id = $_GET['id'];

// Мэргэжлийн мэдээлэл авах
$stmt = $pdo->prepare("SELECT * FROM majors WHERE id = ?");
$stmt->execute([$major_id]);
$major = $stmt->fetch();

// Мэргэжил олдсонгүй бол
if (!$major) {
    $_SESSION['error_message'] = "Мэргэжил олдсонгүй!";
    header('Location: majors.php');
    exit();
}

// Мэргэжлийн мэдээлэл шинэчлэх
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_major'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE majors SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $major_id]);
        
        $_SESSION['success_message'] = "Мэргэжлийн мэдээлэл амжилттай шинэчлэгдлээ!";
        header("Location: edit_major.php?id=$major_id");
        exit();
    } else {
        $error = "Мэргэжлийн нэр оруулна уу!";
    }
}

// Энэ мэргэжилд хамаарах ангиудыг авах
$classes = $pdo->prepare("
    SELECT c.id, c.name, c.year, 
           (SELECT COUNT(*) FROM users WHERE class_id = c.id) as student_count
    FROM classes c 
    WHERE c.major_id = ?
    ORDER BY c.year DESC, c.name ASC
");
$classes->execute([$major_id]);
$class_list = $classes->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мэргэжлийн мэдээлэл засах</title>
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
            <h1><i class="fas fa-edit"></i> Мэргэжлийн мэдээлэл засах</h1>
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
            <h2 class="card-title"><?php echo htmlspecialchars($major['name']); ?></h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name" class="form-label">Мэргэжлийн нэр</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($major['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Тайлбар</label>
                    <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($major['description']); ?></textarea>
                </div>
                
                <button type="submit" name="update_major" class="btn btn-primary">
                    <i class="fas fa-save"></i> Хадгалах
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Энэ мэргэжилд хамаарах ангиуд</h2>
            
            <?php if (empty($class_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-class"></i>
                    <h3>Анги бүртгэгдээгүй байна</h3>
                    <a href="majors.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Шинэ анги нэмэх
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ангийн нэр</th>
                            <th>Он</th>
                            <th>Сурагчид</th>
                            <th>Үйлдэл</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_list as $index => $class): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                <td><?php echo $class['year']; ?></td>
                                <td><?php echo $class['student_count']; ?> сурагч</td>
                                <td class="action-btns">
                                    <a href="class_details.php?id=<?php echo $class['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Дэлгэрэнгүй
                                    </a>
                                    <a href="majors.php?delete_class=<?php echo $class['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('Та энэ ангийг устгахдаа итгэлтэй байна уу?');">
                                        <i class="fas fa-trash-alt"></i> Устгах
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
                if (!confirm('Та энэ үйлдлийг хийхдээ итгэлтэй байна уу?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>