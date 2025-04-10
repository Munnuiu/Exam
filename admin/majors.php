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

$user_id = $_SESSION['user_id'];

// Мэргэжил нэмэх
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_major'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO majors (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $_SESSION['success_message'] = "Мэргэжил амжилттай нэмэгдлээ!";
        header('Location: majors.php');
        exit();
    } else {
        $error = "Мэргэжлийн нэр оруулна уу!";
    }
}

// Анги нэмэх
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $major_id = $_POST['major_id'];
    $name = trim($_POST['class_name']);
    $year = $_POST['class_year'];

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO classes (major_id, name, year) VALUES (?, ?, ?)");
        $stmt->execute([$major_id, $name, $year]);
        $_SESSION['success_message'] = "Анги амжилттай нэмэгдлээ!";
        header('Location: majors.php');
        exit();
    } else {
        $error = "Ангийн нэр оруулна уу!";
    }
}

// Сурагч бүртгэх
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student'])) {
    $user_id = $_POST['student_user_id'];
    $class_id = $_POST['class_id'];
    $student_id = trim($_POST['student_id']);

    if (!empty($student_id)) {
        $stmt = $pdo->prepare("UPDATE users SET class_id = ?, student_id = ? WHERE id = ?");
        $stmt->execute([$class_id, $student_id, $user_id]);
        $_SESSION['success_message'] = "Сурагч амжилттай бүртгэгдлээ!";
        header('Location: majors.php');
        exit();
    } else {
        $error = "Сурагчийн ID оруулна уу!";
    }
}

// Мэргэжил устгах
if (isset($_GET['delete_major'])) {
    $id = $_GET['delete_major'];
    
    // Эхлээд уг мэргэжилд хамаарах анги байгаа эсэхийг шалгах
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE major_id = ?");
    $stmt->execute([$id]);
    $class_count = $stmt->fetchColumn();
    
    if ($class_count == 0) {
        $stmt = $pdo->prepare("DELETE FROM majors WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "Мэргэжил амжилттай устгагдлаа!";
    } else {
        $_SESSION['error_message'] = "Энэ мэргэжилд хамаарах ангиуд байгаа тул устгах боломжгүй!";
    }
    
    header('Location: majors.php');
    exit();
}

// Анги устгах
if (isset($_GET['delete_class'])) {
    $id = $_GET['delete_class'];
    
    // Эхлээд уг ангид сурагч байгаа эсэхийг шалгах
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE class_id = ?");
    $stmt->execute([$id]);
    $student_count = $stmt->fetchColumn();
    
    if ($student_count == 0) {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "Анги амжилттай устгагдлаа!";
    } else {
        $_SESSION['error_message'] = "Энэ ангид сурагчид байгаа тул устгах боломжгүй!";
    }
    
    header('Location: majors.php');
    exit();
}

// Бүх мэргэжлийг авах
$majors = $pdo->query("SELECT * FROM majors ORDER BY name ASC")->fetchAll();

// Бүх ангийг мэргэжлийн хамт авах
$classes = $pdo->query("
    SELECT c.id, c.name as class_name, c.year, m.name as major_name, m.id as major_id, 
           (SELECT COUNT(*) FROM users WHERE class_id = c.id) as student_count
    FROM classes c 
    JOIN majors m ON c.major_id = m.id
    ORDER BY m.name ASC, c.year DESC, c.name ASC
")->fetchAll();

// Бүртгэлгүй сурагчдыг авах
$unregistered_students = $pdo->query("
    SELECT id, full_name, email 
    FROM users 
    WHERE role = 'student' AND (class_id IS NULL OR class_id = 0)
    ORDER BY full_name ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мэргэжил, ангийн удирдлага</title>
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
        
        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
            
            .tab-container {
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Мэргэжил, ангийн удирдлага</h1>
            <a href="admin_dashboard.php" class="btn">
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
        
        <div class="tab-container">
            <div class="tab active" onclick="openTab('majors')">
                <i class="fas fa-graduation-cap"></i> Мэргэжлүүд
            </div>
            <div class="tab" onclick="openTab('classes')">
                <i class="fas fa-users-class"></i> Ангиуд
            </div>
            <div class="tab" onclick="openTab('students')">
                <i class="fas fa-user-graduate"></i> Сурагч бүртгэл
            </div>
        </div>
        
        <!-- Мэргэжлийн хэсэг -->
        <div id="majors" class="tab-content active">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> Шинэ мэргэжил нэмэх</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Мэргэжлийн нэр</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Тайлбар</label>
                        <textarea id="description" name="description" class="form-control"></textarea>
                    </div>
                    
                    <button type="submit" name="add_major" class="btn btn-primary">
                        <i class="fas fa-save"></i> Хадгалах
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title"><i class="fas fa-list"></i> Мэргэжлийн жагсаалт</h2>
                
                <?php if (empty($majors)): ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Мэргэжил бүртгэгдээгүй байна</h3>
                        <p>Дээрх форм ашиглан шинэ мэргэжил нэмнэ үү</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Мэргэжлийн нэр</th>
                                <th>Тайлбар</th>
                                <th>Ангиуд</th>
                                <th>Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($majors as $index => $major): ?>
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE major_id = ?");
                                $stmt->execute([$major['id']]);
                                $class_count = $stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($major['name']); ?></td>
                                    <td><?php echo htmlspecialchars($major['description'] ?: '-'); ?></td>
                                    <td><?php echo $class_count; ?> анги</td>
                                    <td class="action-btns">
                                        <a href="edit_major.php?id=<?php echo $major['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Засах
                                        </a>
                                        <a href="majors.php?delete_major=<?php echo $major['id']; ?>" class="btn btn-danger" 
                                           onclick="return confirm('Та энэ мэргэжлийг устгахдаа итгэлтэй байна уу?');">
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
        
        <!-- Ангийн хэсэг -->
        <div id="classes" class="tab-content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> Шинэ анги нэмэх</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="major_id" class="form-label">Мэргэжил сонгох</label>
                        <select id="major_id" name="major_id" class="form-control" required>
                            <?php foreach ($majors as $major): ?>
                                <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="class_name" class="form-label">Ангийн нэр</label>
                        <input type="text" id="class_name" name="class_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="class_year" class="form-label">Он</label>
                        <input type="number" id="class_year" name="class_year" class="form-control" 
                               value="<?php echo date('Y'); ?>" required>
                    </div>
                    
                    <button type="submit" name="add_class" class="btn btn-primary">
                        <i class="fas fa-save"></i> Хадгалах
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title"><i class="fas fa-list"></i> Ангийн жагсаалт</h2>
                
                <?php if (empty($classes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users-class"></i>
                        <h3>Анги бүртгэгдээгүй байна</h3>
                        <p>Дээрх форм ашиглан шинэ анги нэмнэ үү</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Мэргэжил</th>
                                <th>Ангийн нэр</th>
                                <th>Он</th>
                                <th>Сурагчид</th>
                                <th>Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $index => $class): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($class['major_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td><?php echo $class['year']; ?></td>
                                    <td><?php echo $class['student_count']; ?> сурагч</td>
                                    <td class="action-btns">
                                        <a href="class_details.php?id=<?php echo $class['id']; ?>" class="btn btn-success">
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
        
        <!-- Сурагч бүртгэлийн хэсэг -->
        <div id="students" class="tab-content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-user-plus"></i> Сурагч бүртгэх</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_user_id" class="form-label">Сурагч сонгох</label>
                        <select id="student_user_id" name="student_user_id" class="form-control" required>
                            <?php if (empty($unregistered_students)): ?>
                                <option value="" disabled selected>Бүртгэлгүй сурагч байхгүй</option>
                            <?php else: ?>
                                <?php foreach ($unregistered_students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']); ?> 
                                        (<?php echo htmlspecialchars($student['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="class_id" class="form-label">Анги сонгох</label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['major_name']); ?> - 
                                    <?php echo htmlspecialchars($class['class_name']); ?> 
                                    (<?php echo $class['year']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id" class="form-label">Сурагчийн ID</label>
                        <input type="text" id="student_id" name="student_id" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="register_student" class="btn btn-primary" <?php echo empty($unregistered_students) || empty($classes) ? 'disabled' : ''; ?>>
                        <i class="fas fa-user-plus"></i> Бүртгэх
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title"><i class="fas fa-users"></i> Бүртгэлгүй сурагчид</h2>
                
                <?php if (empty($unregistered_students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>Бүх сурагчид ангид бүртгэгдсэн байна</h3>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Нэр</th>
                                <th>Имэйл</th>
                                <th>Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unregistered_students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <a href="#" onclick="document.getElementById('student_user_id').value='<?php echo $student['id']; ?>'; openTab('students');" 
                                           class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i> Бүртгэх
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Таб солих функц
        function openTab(tabName) {
            // Бүх таб контентуудыг харагдахгүй болгох
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Бүх табуудыг идэвхгүй болгох
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Сонгосон таб болон контентыг идэвхжүүлэх
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab[onclick="openTab('${tabName}')"]`).classList.add('active');
        }
        
        // Устгах үйлдлийг баталгаажуулах
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Та энэ үйлдлийг хийхдээ итгэлтэй байна уу?')) {
                    e.preventDefault();
                }
            });
        });
        
        // URL-д hash байгаа эсэхийг шалгах
        if (window.location.hash) {
            const tabName = window.location.hash.substring(1);
            if (['majors', 'classes', 'students'].includes(tabName)) {
                openTab(tabName);
            }
        }
    </script>
</body>
</html>