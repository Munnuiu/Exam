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
    $name = trim($_POST['department_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Тэнхим амжилттай нэмэгдлээ";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// Багшийг тэнхимд хуваарилах
if (isset($_POST['assign_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $department_id = (int)$_POST['department_id'];
    
    $stmt = $conn->prepare("UPDATE users SET department_id = ? WHERE id = ? AND role = 'teacher'");
    $stmt->bind_param("ii", $department_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Багш амжилттай хуваарилагдлаа";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Багшийг тэнхимээс чөлөөлөх
if (isset($_GET['release'])) {
    $teacher_id = (int)$_GET['release'];
    $stmt = $conn->prepare("UPDATE users SET department_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Багшийг тэнхимээс амжилттай чөлөөлөв";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Хайлтын параметрүүд
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Хуудаслалт
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Багш нарын жагсаалт авах (филтертэй)
$query = "SELECT SQL_CALC_FOUND_ROWS id, full_name, department_id FROM users WHERE role = 'teacher'";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND full_name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($department_filter)) {
    if ($department_filter === 'unassigned') {
        $query .= " AND department_id IS NULL";
    } else {
        $query .= " AND department_id = ?";
        $params[] = $department_filter;
        $types .= 'i';
    }
}

$query .= " ORDER BY full_name LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teacherList = $stmt->get_result();

// Нийт бичлэгийн тоо
$total_result = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_pages = ceil($total_result / $per_page);

// Тэнхимүүдийн жагсаалт
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

function getDepartmentName($id, $conn) {
    if (!$id) return '<span class="badge bg-gray-200 text-gray-800">Тэнхимгүй</span>';
    
    $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();
    return $name ? '<span class="badge bg-primary">'.$name.'</span>' : '<span class="badge bg-gray-200 text-gray-800">Тэнхимгүй</span>';
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тэнхимийн удирдлага</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f9fafb;
            --dark: #111827;
            --gray: #e5e7eb;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .navbar {
            box-shadow: var(--card-shadow);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .badge {
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        .btn {
            font-weight: 500;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-warning {
            color: var(--warning);
            border-color: var(--warning);
        }
        
        .btn-outline-warning:hover {
            background-color: var(--warning);
            border-color: var(--warning);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid var(--gray);
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 16px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .pagination .page-link {
            color: var(--primary);
        }
        
        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: var(--secondary);
        }
        
        .filter-section {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: var(--card-shadow);
        }
        
        .stats-card i {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .stats-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .stats-card p {
            color: var(--secondary);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 99;
            transition: all 0.3s;
        }
        
        .floating-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                        <a class="nav-link active" href="department.php"><i class="bi bi-house-door me-1"></i> Нүүр</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers.php"><i class="bi bi-people me-1"></i> Багш нар</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="departments.php"><i class="bi bi-building me-1"></i> Тэнхимүүд</a>
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

        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold mb-0">Тэнхимд Багш Хуваарилах</h2>
                <p class="text-muted mb-0">Багш нарыг тэнхимд хялбархан хуваарилах систем</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-people"></i>
                    <h3><?= $teacherList->num_rows ?></h3>
                    <p>Нийт багш</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-building"></i>
                    <h3><?= $departments->num_rows ?></h3>
                    <p>Нийт тэнхим</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-person-x"></i>
                    <h3>
                        <?php 
                        $unassigned = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND department_id IS NULL")->fetch_row()[0];
                        echo $unassigned;
                        ?>
                    </h3>
                    <p>Тэнхимгүй багш</p>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-4">
            <!-- Тэнхим нэмэх -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                            <i class="bi bi-plus-circle-fill text-primary fs-5"></i>
                        </div>
                        <h3 class="h5 mb-0">Шинэ тэнхим нэмэх</h3>
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="department_name" class="form-label">Тэнхимийн нэр</label>
                            <input type="text" class="form-control" id="department_name" name="department_name" placeholder="Жишээ нь: Програм хангамж" required>
                        </div>
                        <button type="submit" name="add_department" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-plus-circle me-2"></i> Тэнхим нэмэх
                        </button>
                    </form>
                </div>
            </div>

            <!-- Багш хуваарилах -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                            <i class="bi bi-person-check-fill text-success fs-5"></i>
                        </div>
                        <h3 class="h5 mb-0">Багш хуваарилах</h3>
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Багш сонгох</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">-- Багш сонгох --</option>
                                <?php
                                $teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name");
                                if ($teachers->num_rows > 0) {
                                    while ($row = $teachers->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Тэнхим сонгох</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">-- Тэнхим сонгох --</option>
                                <?php
                                $departments->data_seek(0);
                                while ($row = $departments->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_teacher" class="btn btn-success w-100 py-2">
                            <i class="bi bi-check-circle me-2"></i> Хуваарилах
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Хайлт болон шүүлтүүр -->
        <div class="filter-section mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" name="search" placeholder="Багшийн нэрээр хайх..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="department">
                        <option value="">Бүх тэнхим</option>
                        <option value="unassigned" <?= $department_filter === 'unassigned' ? 'selected' : '' ?>>Тэнхимгүй</option>
                        <?php
                        $departments->data_seek(0);
                        while ($row = $departments->fetch_assoc()) {
                            $selected = $department_filter == $row['id'] ? 'selected' : '';
                            echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Шүүх
                    </button>
                </div>
            </form>
        </div>

        <!-- Багш нарын жагсаалт -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h4 mb-0">
                    <i class="bi bi-people-fill text-primary me-2"></i>
                    Багш нарын жагсаалт
                </h3>
                <div>
                    <span class="badge bg-primary">
                        Нийт: <?= $total_result ?>
                    </span>
                </div>
            </div>

            <?php if ($teacherList->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Багшийн нэр</th>
                                <th scope="col">Тэнхим</th>
                                <th scope="col">Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = ($page - 1) * $per_page + 1;
                            while ($row = $teacherList->fetch_assoc()):
                                $departmentName = getDepartmentName($row['department_id'], $conn);
                                $initials = getInitials($row['full_name']);
                            ?>
                                <tr>
                                    <th scope="row"><?= $counter++ ?></th>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="teacher-avatar me-3">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($row['full_name']) ?></h6>
                                                <small class="text-muted">ID: <?= $row['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $departmentName ?></td>
                                    <td>
                                        <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-pencil-square"></i> Засах
                                        </a>
                                        <?php if ($row['department_id']): ?>
                                            <a href="?release=<?= $row['id'] ?>&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Багшийг тэнхимээс чөлөөлөх үү?')">
                                                <i class="bi bi-arrow-left-right"></i> Чөлөөлөх
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-gray-200 text-gray-800">Тэнхимгүй</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Хуудаслалт -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Багш олдсонгүй</h5>
                    <p class="text-muted">Хайлтын үр дүн олдсонгүй эсвэл багш бүртгэгдээгүй байна</p>
                    <a href="?" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Бүх багш үзэх
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="#" class="floating-btn btn btn-primary">
        <i class="bi bi-question-lg"></i>
    </a>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center text-muted">
            <p class="mb-0">&copy; <?= date('Y') ?> Тэнхимийн Удирдлагын Систем. Бүх эрх хуулиар хамгаалагдсан.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto dismiss alert after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>
<?php 
$conn->close();

// Туслах функц: Нэрнээс үсгийн товчлол гаргах
function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>