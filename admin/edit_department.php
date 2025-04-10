<?php
session_start();
require '../database/db.php';

// Холболт
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Тэнхимийн ID шалгах
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Тэнхимийн ID буруу байна";
    header("Location: departments.php");
    exit();
}

$department_id = (int)$_GET['id'];

// Тэнхимийн мэдээлэл авах
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();
$stmt->close();

if (!$department) {
    $_SESSION['error'] = "Тэнхим олдсонгүй";
    header("Location: departments.php");
    exit();
}

// Тэнхимийн багш нарын тоо
$teacher_count = $conn->query("SELECT COUNT(*) FROM users WHERE department_id = $department_id AND role = 'teacher'")->fetch_row()[0];

// Мэдээлэл шинэчлэх
if (isset($_POST['update_department'])) {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Тэнхимийн нэрийг бөглөнө үү";
    } else {
        $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $department_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Тэнхимийн мэдээлэл амжилттай шинэчлэгдлээ";
            header("Location: departments.php");
            exit();
        } else {
            $_SESSION['error'] = "Алдаа гарлаа: " . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тэнхим засах</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f9fafb;
            --dark: #111827;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f3f4f6;
        }
        
        .department-form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background-color: white;
        }
        
        .form-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .department-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .teacher-count-badge {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                <i class="bi bi-building me-2"></i>
                Тэнхимийн Удирдлага
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i> Нүүр</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers.php"><i class="bi bi-people me-1"></i> Багш нар</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="departments.php"><i class="bi bi-building me-1"></i> Тэнхимүүд</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="department-form-container">
            <div class="text-center form-header">
                <i class="bi bi-building department-icon"></i>
                <h2>Тэнхим засах</h2>
                <div class="d-flex justify-content-center align-items-center mt-3">
                    <span class="badge teacher-count-badge me-2">
                        ID: <?= $department['id'] ?>
                    </span>
                    <span class="badge teacher-count-badge">
                        <i class="bi bi-people me-1"></i> <?= $teacher_count ?> багш
                    </span>
                </div>
            </div>

            <form method="POST">
                <div class="mb-4">
                    <label for="name" class="form-label">Тэнхимийн нэр</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= htmlspecialchars($department['name']) ?>" required>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="departments.php" class="btn btn-outline-secondary me-md-2">
                        <i class="bi bi-x-circle"></i> Цуцлах
                    </a>
                    <button type="submit" name="update_department" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Хадгалах
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>