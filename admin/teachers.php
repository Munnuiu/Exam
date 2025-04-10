<?php
session_start();
require '../database/db.php';

// Холболт
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Хайлтын параметрүүд
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Хуудаслалт
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Багш нарын жагсаалт авах (филтертэй)
$query = "SELECT SQL_CALC_FOUND_ROWS u.id, u.full_name, u.email, u.phone, d.name AS department_name 
          FROM users u
          LEFT JOIN departments d ON u.department_id = d.id
          WHERE u.role = 'teacher'";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($department_filter)) {
    if ($department_filter === 'unassigned') {
        $query .= " AND u.department_id IS NULL";
    } else {
        $query .= " AND u.department_id = ?";
        $params[] = $department_filter;
        $types .= 'i';
    }
}

$query .= " ORDER BY u.full_name LIMIT ? OFFSET ?";
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

function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Багш нарын жагсаалт</title>
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
        
        .teacher-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .teacher-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .teacher-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .department-badge {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 500;
        }
        
        .contact-info {
            color: var(--secondary);
            font-size: 0.9rem;
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
                        <a class="nav-link active" href="teachers.php"><i class="bi bi-people me-1"></i> Багш нар</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i> Багш нарын жагсаалт</h2>
                <p class="text-muted mb-0">Системд бүртгэлтэй багш нар</p>
            </div>
            <a href="add_user.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Шинэ багш
            </a>
        </div>

        <!-- Хайлт болон шүүлтүүр -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" name="search" placeholder="Нэр, имэйл эсвэл утасны дугаараар хайх..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Багш нарын жагсаалт -->
        <?php if ($teacherList->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $teacherList->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card teacher-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="teacher-avatar me-3">
                                        <?= getInitials($row['full_name']) ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($row['full_name']) ?></h5>
                                        <?php if ($row['department_name']): ?>
                                            <span class="badge department-badge mb-2"><?= $row['department_name'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mb-2">Тэнхимгүй</span>
                                        <?php endif; ?>
                                        <div class="contact-info">
                                            <div><i class="bi bi-envelope me-2"></i> <?= $row['email'] ?></div>
                                            <div class="mt-1"><i class="bi bi-telephone me-2"></i> <?= $row['phone'] ?: '---' ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="action-btn me-2" title="Засах">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </a>
                                    <a href="teacher_profile.php?id=<?= $row['id'] ?>" class="action-btn" title="Дэлгэрэнгүй">
                                        <i class="bi bi-eye text-success"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
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
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Багш олдсонгүй</h5>
                    <p class="text-muted">Хайлтын үр дүн олдсонгүй эсвэл багш бүртгэгдээгүй байна</p>
                    <a href="teachers.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Бүх багш үзэх
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>