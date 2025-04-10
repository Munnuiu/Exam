<?php
session_start();
require '../database/db.php';

// Check if the user is logged in and is an admin or teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Pagination settings - changed to 3 rows per page
$limit = 3; // Number of exams per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch exams based on the user's role and search query
if ($role == 'admin') {
    $sql = 'SELECT * FROM exams WHERE exam_name LIKE :search ORDER BY exam_id DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $sql = 'SELECT * FROM exams WHERE teacher_id = :teacher_id AND exam_name LIKE :search ORDER BY exam_id DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':teacher_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$exams = $stmt->fetchAll();

// Count total exams for pagination
if ($role == 'admin') {
    $sql = 'SELECT COUNT(*) FROM exams WHERE exam_name LIKE :search';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $sql = 'SELECT COUNT(*) FROM exams WHERE teacher_id = :teacher_id AND exam_name LIKE :search';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':teacher_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->execute();
$total_exams = $stmt->fetchColumn();
$total_pages = ceil($total_exams / $limit);

// Handle exam deletion
if (isset($_GET['delete_id'])) {
    $exam_id = $_GET['delete_id'];

    if ($role == 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ?');
        $stmt->execute([$exam_id]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?');
        $stmt->execute([$exam_id, $user_id]);
    }
    $exam = $stmt->fetch();

    if ($exam) {
        try {
            $stmt = $pdo->prepare('DELETE FROM exams WHERE exam_id = ?');
            $stmt->execute([$exam_id]);
            $_SESSION['success'] = "Шалгалтыг амжилттай устгасан.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Шалгалтыг устгахад алдаа гарлаа: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Танд энэ шалгалтыг устгах зөвшөөрөл байхгүй байна.";
    }

    header('Location: manage_exams.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шалгалтуудыг удирдах</title>
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
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h2 {
            font-size: 1.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-box input[type="text"] {
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            width: 300px;
            transition: var(--transition);
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
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.2);
        }
        
        .alert-error {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid rgba(247, 37, 133, 0.2);
        }
        
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 0 1px var(--light-gray);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        
        th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .pagination a {
            background-color: var(--light);
            color: var(--primary);
        }
        
        .pagination .current {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box input[type="text"] {
                width: 100%;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-book-open"></i> Шалгалтуудыг удирдах</h2>
            <a href="add_exam.php" class="btn btn-primary"><i class="fas fa-plus"></i> Шинэ шалгалт нэмэх</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="search-box">
            <form action="manage_exams.php" method="GET">
                <input type="text" name="search" placeholder="Нэрээр хайх..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Хайх</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Шалгалтын нэр</th>
                        <th>Тодорхойлолт</th>
                        <th>Үйлдлүүд</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exams)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>Шалгалт олдсонгүй</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><?php echo $exam['exam_id']; ?></td>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['description']); ?></td>
                                <td class="actions">
                                    <a href="edit_exam.php?id=<?php echo $exam['exam_id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Засварлах</a>
                                    <a href="manage_exams.php?delete_id=<?php echo $exam['exam_id']; ?>" class="btn btn-danger" onclick="return confirm('Та энэ шалгалтыг устгахдаа итгэлтэй байна уу?');"><i class="fas fa-trash-alt"></i> Устгах</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="manage_exams.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i> Өмнөх</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="manage_exams.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="manage_exams.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Дараах <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="../admin/admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Хяналтын самбар руу буцах</a>
        </div>
    </div>
</body>
</html>