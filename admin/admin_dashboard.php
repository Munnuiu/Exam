<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$profile_picture = $user['profile_picture'] ?? 'default.jpg';

if ($role != 'admin') {
    header('Location: dashboard.php');
    exit();
}
// Мэргэжил нэмэх
function addMajor($name, $description) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO majors (name, description) VALUES (?, ?)");
    return $stmt->execute([$name, $description]);
}

// Анги нэмэх
function addClass($major_id, $name, $year) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO classes (major_id, name, year) VALUES (?, ?, ?)");
    return $stmt->execute([$major_id, $name, $year]);
}

// Сурагчийг ангид бүртгэх
function registerStudent($user_id, $class_id, $student_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET class_id = ?, student_id = ? WHERE id = ?");
    return $stmt->execute([$class_id, $student_id, $user_id]);
}
// Fetch all statistics
$stmt = $pdo->query('SELECT COUNT(*) as total_users FROM users');
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) as total_exams FROM exams');
$total_exams = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) as total_results FROM exam_results');
$total_results = $stmt->fetchColumn();

// Calculate user growth from last month
$last_month = date('Y-m-d', strtotime('-1 month'));
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at < ?');
$stmt->execute([$last_month]);
$last_month_users = $stmt->fetchColumn();
$user_change_percent = $last_month_users > 0 ? round(($total_users - $last_month_users) / $last_month_users * 100) : 0;

// Calculate new exams this week
$week_start = date('Y-m-d', strtotime('monday this week'));
$stmt = $pdo->prepare('SELECT COUNT(*) FROM exams WHERE created_at >= ?');
$stmt->execute([$week_start]);
$new_exams_this_week = $stmt->fetchColumn();

// Calculate exam attempts change from last month
$stmt = $pdo->prepare('SELECT COUNT(*) FROM exam_results WHERE submitted_at < ?');
$stmt->execute([$last_month]);
$last_month_attempts = $stmt->fetchColumn();
$attempt_change_percent = $last_month_attempts > 0 ? round(($total_results - $last_month_attempts) / $last_month_attempts * 100) : 0;

$stmt = $pdo->query('SELECT id, full_name, email, profile_picture FROM users');
$users = $stmt->fetchAll();

// Handle password change if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $user['Password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update_stmt->execute([$hashed_password, $user_id]);
            $password_success = "Нууц үг амжилттай өөрчлөгдсөн!";
        } else {
            $password_error = "Шинэ нууц үг таарахгүй байна!";
        }
    } else {
        $password_error = "Одоогийн нууц үг буруу байна!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ хяналтын самбар</title>
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .page-content {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2b2d42, #1a1a2e);
            color: white;
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .profile-section {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .profile-img-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .edit-profile-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .edit-profile-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(67, 97, 238, 0.2);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .nav-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .nav-item {
            margin: 5px 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(67, 97, 238, 0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .logout-btn {
            margin-top: auto;
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: none;
            padding: 12px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(247, 37, 133, 0.2);
            transform: translateX(5px);
        }
        
        .logout-btn i {
            margin-right: 10px;
        }
        
        /* Main Content Styles */
        main {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .current-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
        }
        
        .stat-card.users::after {
            background: var(--success);
        }
        
        .stat-card.exams::after {
            background: var(--warning);
        }
        
        .stat-card.results::after {
            background: var(--danger);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .stat-card.users .stat-icon {
            color: var(--success);
        }
        
        .stat-card.exams .stat-icon {
            color: var(--warning);
        }
        
        .stat-card.results .stat-icon {
            color: var(--danger);
        }
        
        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .stat-change {
            font-size: 0.85rem;
            color: var(--success);
            display: flex;
            align-items: center;
        }
        
        .stat-change.down {
            color: var(--danger);
        }
        
        /* Profile Section */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .profile-info, .password-change {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
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
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
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
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .page-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            
            .profile-section {
                width: 100%;
                padding: 10px 0;
                margin-bottom: 10px;
            }
            
            .nav-menu {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin: 5px;
            }
            
            .nav-link {
                padding: 10px 15px;
            }
            
            .logout-btn {
                margin-top: 0;
                width: 100%;
            }
            
            main {
                padding: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .current-date {
                margin-top: 10px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="sidebar">
        <div class="profile-section">
                <div class="profile-img-container">
                    <img src="../uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-img">
                    <button class="edit-profile-btn" onclick="window.location.href='profile.php'">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </div>
                <h3 class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?></h3>
                <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Хяналтын самбар</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Хэрэглэгчдийг удирдах</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="manage_exams.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Шалгалтуудыг удирдах</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="manage_questions.php" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Асуултуудыг удирдах</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="majors.php" class="nav-link">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Мэргэжил, ангийн удирдлага</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="department.php" class="nav-link">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Тэнхим удирдлага</span>
                    </a>
                </div>
                <button class="logout-btn" onclick="window.location.href='../auth/logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Гарах</span>
                </button>
            </div>
            <!-- Your existing sidebar content remains the same -->
        </div>
        
        <main>
            <div class="header">
                <h1>Админ хяналтын самбар</h1>
                <div class="current-date">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card users">
                    <i class="fas fa-users stat-icon"></i>
                    <h3 class="stat-title">Нийт хэрэглэгчид</h3>
                    <p class="stat-value"><?php echo $total_users; ?></p>
                    <div class="stat-change">
                        <i class="fas fa-arrow-<?php echo ($user_change_percent >= 0) ? 'up' : 'down'; ?>"></i>
                        <span>
                            Өнгөрсөн сараас 
                            <?php 
                            echo abs($user_change_percent).'%';
                            echo ($user_change_percent >= 0) ? ' өссөн' : ' буурсан';
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="stat-card exams">
                    <i class="fas fa-file-alt stat-icon"></i>
                    <h3 class="stat-title">Нийт шалгалтууд</h3>
                    <p class="stat-value"><?php echo $total_exams; ?></p>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <span>
                            Энэ долоо хоногт 
                            <?php echo $new_exams_this_week; ?> шинэ
                        </span>
                    </div>
                </div>
                
                <div class="stat-card results">
                    <i class="fas fa-chart-bar stat-icon"></i>
                    <h3 class="stat-title">Шалгалтад хамрагдсан нийт тоо</h3>
                    <p class="stat-value"><?php echo $total_results; ?></p>
                    <div class="stat-change <?php echo ($attempt_change_percent < 0) ? 'down' : ''; ?>">
                        <i class="fas fa-arrow-<?php echo ($attempt_change_percent >= 0) ? 'up' : 'down'; ?>"></i>
                        <span>
                            <?php
                            echo abs($attempt_change_percent).'%';
                            echo ($attempt_change_percent >= 0) ? ' их оролдлого' : ' бага оролдлого';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="profile-container">
                <div class="profile-info">
                    <h2 class="section-title">Профайл мэдээлэл</h2>
                    
                    <div class="info-item">
                        <span class="info-label">Бүтэн нэр</span>
                        <p class="info-value"><?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?></p>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Имэйл</span>
                        <p class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></p>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Утас</span>
                        <p class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Үүрэг</span>
                        <p class="info-value"><?php echo htmlspecialchars(ucfirst($role)); ?></p>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Сүүлийн нэвтрэлт</span>
                        <p class="info-value"><?php echo date('M j, Y g:i a', strtotime($user['last_login'] ?? 'now')); ?></p>
                    </div>
                    
                    <button class="btn btn-primary" onclick="window.location.href='profile.php'">
                        <i class="fas fa-user-edit"></i> Профайлыг засах
                    </button>
                </div>
                
                <div class="password-change">
                    <h2 class="section-title">Нууц үг солих</h2>
                    
                    <?php if (isset($password_success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $password_success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($password_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $password_error; ?>
                        </div>
                    <?php endif; ?>
                    
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
                </div>
            </div>
            <!-- Your existing profile and password change sections remain the same -->
        </main>
    </div>
</body>
</html>