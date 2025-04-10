<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Include the database connection
require '../database/db.php';

$teacher_id = $_SESSION['user_id']; // Get the logged-in teacher's ID

// Fetch teacher's profile data
$stmt = $pdo->prepare('SELECT full_name, profile_picture FROM users WHERE id = ?');
$stmt->execute([$teacher_id]);
$user = $stmt->fetch();
$profile_picture = $user['profile_picture'] ?? 'default.jpg';
$full_name = $user['full_name'] ?? 'Teacher';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --danger-color: #ff4444;
            --warning-color: #ffbb33;
            --border-radius: 12px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f2 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .profile-img-container {
            position: relative;
            cursor: pointer;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .profile-img:hover {
            transform: scale(1.05);
            border-color: white;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1rem 0;
            min-width: 200px;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition);
        }

        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .profile-dropdown a:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .profile-dropdown a i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .user-info h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-info p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .dashboard-content {
            padding: 2rem;
        }

        .welcome-message {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .welcome-message h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .dashboard-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .dashboard-card p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(63, 55, 201, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .profile-section {
                margin-top: 1rem;
                flex-direction: column;
            }
            
            .dashboard-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-info">
                <h1>Тавтай морил, <?php echo htmlspecialchars($full_name); ?></h1>
                <p>Багшийн хяналтын самбар</p>
            </div>
            
            <div class="profile-section">
                <div class="profile-img-container" id="profileToggle">
                <img src="<?php echo !empty($user['profile_picture']) ? '../uploads/' . htmlspecialchars($user['profile_picture']) : '../uploads/default.jpg'; ?>" 
     alt="Profile" class="profile-img">

                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Профайлыг засах</a>
                    <a href="change_password.php"><i class="fas fa-key"></i> Нууц үг солих</a>
                    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Гарах</a>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="welcome-message">
                <h2>Таны удирдлагын төв</h2>
                <p>Шалгалт, асуултуудыг удирдаж, оюутны үр дүнг нэг дороос хараарай. Оюутны гүйцэтгэлийг хянах, сайжруулахад хэрэгтэй бүх зүйл байгаа</p>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>шалгалтыг удирдах</h3>
                    <p>Уг цэсний тусламжтайгаар оюутнууддаа зориулж шинэ шалгалтыг төлөвлөж болно</p>
                    <a href="create_exam.php" class="btn"><i class="fas fa-plus"></i> Удирдах</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>Асуултуудыг удирдах</h3>
                    <p>Ирээдүйн шалгалтад зориулж асуултын сангаа үүсгэж, засаж, цэгцлээрэй.</p>
                    <a href="manage_questions.php" class="btn"><i class="fas fa-cog"></i> Удирдах</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Үр дүнг харах</h3>
                    <p>Оюутны гүйцэтгэлд дүн шинжилгээ хийж, дэлгэрэнгүй хараарай</p>
                    <a href="view_results.php" class="btn"><i class="fas fa-eye"></i> Харах</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Profile dropdown toggle
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');
        
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            profileDropdown.classList.remove('show');
        });
    </script>
</body>
</html>