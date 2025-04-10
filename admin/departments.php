<?php
session_start();
require '../database/db.php';

// Холболт
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Тэнхим нэмэх
if (isset($_POST['add_department'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Тэнхим амжилттай нэмэгдлээ";
        header("Location: departments.php");
        exit();
    }
}

// Тэнхим устгах
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Эхлээд тэнхимд хамааралтай багшнуудыг шалгах
    $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE department_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();
    
    if ($count > 0) {
        $_SESSION['error'] = "Энэ тэнхимд багш нар хамааралтай байна. Эхлээд тэднийг өөр тэнхим рүү шилжүүлнэ үү.";
    } else {
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Тэнхим амжилттай устгагдлаа";
    }
    header("Location: departments.php");
    exit();
}

// Тэнхимүүдийн жагсаалт авах
$departments = $conn->query("
    SELECT d.*, COUNT(u.id) as teacher_count 
    FROM departments d
    LEFT JOIN users u ON d.id = u.department_id AND u.role = 'teacher'
    GROUP BY d.id
    ORDER BY d.name
");
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тэнхимүүдийн жагсаалт</title>
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
            --gray: #e5e7eb;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
        }
        
        .department-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .department-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .teacher-count-badge {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 500;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background-color: var(--gray);
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
                        <a class="nav-link" href="department.php"><i class="bi bi-house-door me-1"></i> Нүүр</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers.php"><i class="bi bi-people me-1"></i> Багш нар</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="departments.php"><i class="bi bi-building me-1"></i> Тэнхимүүд</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php"><i class="bi bi-exit me-1"></i> Гарах</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-building text-primary me-2"></i> Тэнхимүүдийн жагсаалт</h2>
                <p class="text-muted mb-0">Системд бүртгэлтэй тэнхимүүд</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="bi bi-plus-lg me-1"></i> Шинэ тэнхим
            </button>
        </div>

        <!-- Тэнхимүүдийн жагсаалт -->
        <div class="row g-4">
            <?php if ($departments->num_rows > 0): ?>
                <?php while ($row = $departments->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card department-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($row['name']) ?></h5>
                                    <span class="badge teacher-count-badge">
                                        <i class="bi bi-people me-1"></i> <?= $row['teacher_count'] ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-4">ID: <?= $row['id'] ?></p>
                                <div class="d-flex justify-content-end">
                                    <a href="edit_department.php?id=<?= $row['id'] ?>" class="action-btn me-2" title="Засах">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </a>
                                    <a href="departments.php?delete=<?= $row['id'] ?>" class="action-btn" title="Устгах" onclick="return confirm('Та энэ тэнхимийг устгахдаа итгэлтэй байна уу?')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Тэнхим олдсонгүй</h5>
                            <p class="text-muted">Системд тэнхим бүртгэгдээгүй байна</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                                <i class="bi bi-plus-lg me-2"></i> Тэнхим нэмэх
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Шинэ тэнхим нэмэх Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel"><i class="bi bi-plus-circle me-2"></i> Шинэ тэнхим</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Тэнхимийн нэр</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Цуцлах</button>
                        <button type="submit" name="add_department" class="btn btn-primary">Хадгалах</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>