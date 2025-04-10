<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch users
$sql = 'SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(4, $limit, PDO::PARAM_INT);
$stmt->bindValue(5, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Count total users
$count_sql = 'SELECT COUNT(*) FROM users WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?';
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
$count_stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
$count_stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
$count_stmt->execute();
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хэрэглэгчдийг удирдах | Админ самбар</title>
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
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Search and Add User Section */
        .action-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-form {
            display: flex;
            align-items: center;
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px 12px 40px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }
        
        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            color: var(--gray);
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
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #2c2f80;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .users-table th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .users-table tbody tr {
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }
        
        .users-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .users-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .users-table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .role-badge.admin {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }
        
        .role-badge.user {
            background: rgba(76, 201, 240, 0.1);
            color: #0a9396;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 15px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }
        
        .page-link {
            padding: 10px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--dark);
            background: white;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .page-link:hover, .page-link.current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Messages */
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--gray);
            text-decoration: none;
            margin-top: 20px;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .users-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .search-form {
                max-width: 100%;
            }
            
            .users-table td, .users-table th {
                padding: 12px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Хэрэглэгчдийг удирдах</h1>
            <a href="../admin/admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Хяналтын самбар руу буцах
            </a>
        </div>
        
        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Action Bar -->
        <div class="action-bar">
            <form class="search-form" action="manage_users.php" method="GET">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Хэрэглэгчдийг нэр эсвэл имэйлээр хайх..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            
            <a href="add_user.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Шинэ хэрэглэгч нэмэх
            </a>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Хэрэглэгч</th>
                        <th>Имэйл</th>
                        <th>Утас</th>
                        <th>Үүрэг</th>
                        <th>Үйлдлүүд</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <h3>Хэрэглэгч олдсонгүй</h3>
                                <p>Хайлтаа тохируулж эсвэл шинэ хэрэглэгч нэмж үзнэ үү</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0px;">
                                        <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-img" alt="Profile">
                                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="action-btn btn-primary">
                                            <i class="fas fa-edit"></i> Засах
                                        </a>
                                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                            <i class="fas fa-trash-alt"></i> Устгах
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="manage_users.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php 
            // Show limited pagination links
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if ($start > 1) {
                echo '<a href="manage_users.php?page=1&search='.urlencode($search).'" class="page-link">1</a>';
                if ($start > 2) echo '<span class="page-link disabled">...</span>';
            }
            
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="manage_users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'current' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php
            if ($end < $total_pages) {
                if ($end < $total_pages - 1) echo '<span class="page-link disabled">...</span>';
                echo '<a href="manage_users.php?page='.$total_pages.'&search='.urlencode($search).'" class="page-link">'.$total_pages.'</a>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="manage_users.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>