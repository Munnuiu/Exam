<?php
session_start();

// Хэрэв хэрэглэгч нэвтрээгүй бол login хуудас руу шилжүүлэх
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require '../database/db.php';

$user_id = $_SESSION['user_id'];

// Хэрэглэгчийн мэдээллийг татаж авах
$stmt = $pdo->prepare('SELECT full_name, email, phone, profile_picture FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Мэдээлэл шинэчлэх үед
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Профайл зураг хадгалах
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = '../uploads/';
        $target_file = $target_dir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);
        $profile_picture = basename($_FILES['profile_picture']['name']);
    } else {
        $profile_picture = $user['profile_picture'];
    }

    // Өгөгдлийг шинэчлэх
    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, profile_picture = ? WHERE id = ?');
    $stmt->execute([$full_name, $email, $phone, $profile_picture, $user_id]);
    
    // Системээс дахин уншуулах
    $_SESSION['full_name'] = $full_name;
    header('Location: teacher_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профайл засах | Premium дизайн</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #564FC9;
            --primary-light: #A29DFF;
            --secondary: #FF6584;
            --accent: #42D7D6;
            --light: #F8F9FF;
            --dark: #2E2E48;
            --gray: #A3A3C2;
            --success: #4ADE80;
            --error: #F87171;
            --border-radius-lg: 20px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 15px 50px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #F5F7FF 0%, #E6E9FF 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(108, 99, 255, 0.2);
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(108,99,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: -1;
            animation: rotate 15s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .card-header {
            padding: 30px 40px 20px;
            text-align: center;
            position: relative;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 28px;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .profile-section {
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 160px;
            height: 160px;
            margin-bottom: 25px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 5px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .avatar-wrapper:hover {
            transform: scale(1.05);
        }

        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            position: relative;
            overflow: hidden;
        }

        .avatar-edit {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 13px;
            cursor: pointer;
            opacity: 0;
            transition: var(--transition);
            transform: translateY(10px);
        }

        .avatar-wrapper:hover .avatar-edit {
            opacity: 1;
            transform: translateY(0);
        }

        .avatar-edit i {
            margin-right: 5px;
        }

        .form-group {
            width: 100%;
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid rgba(163, 163, 194, 0.3);
            border-radius: var(--border-radius-md);
            font-size: 15px;
            transition: var(--transition);
            background-color: rgba(248, 249, 255, 0.5);
            color: var(--dark);
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
            outline: none;
            padding-left: 50px;
        }

        .file-input {
            display: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--border-radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 5px 20px rgba(108, 99, 255, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            margin-right: 10px;
            font-size: 16px;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3) 0%,
                rgba(255, 255, 255, 0) 60%
            );
            transform: translateY(100%);
            transition: var(--transition);
        }

        .btn:hover::after {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 25px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 14px;
            padding: 8px 15px;
            border-radius: var(--border-radius-md);
        }

        .back-link:hover {
            color: var(--primary);
            background: rgba(108, 99, 255, 0.1);
            transform: translateX(-5px);
        }

        .back-link i {
            margin-right: 8px;
            transition: var(--transition);
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        .decoration {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.1) 0%, rgba(66, 215, 214, 0.1) 100%);
            z-index: -1;
            animation: float 8s ease-in-out infinite;
        }

        .decoration-1 {
            top: 10%;
            left: 10%;
        }

        .decoration-2 {
            bottom: 10%;
            right: 10%;
            animation-delay: 2s;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        /* Анимаци */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card {
            animation: fadeInUp 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        /* Responsive дизайн */
        @media (max-width: 768px) {
            .glass-card {
                max-width: 100%;
            }
            
            .card-header, .profile-section {
                padding: 25px;
            }
            
            .avatar-wrapper {
                width: 140px;
                height: 140px;
            }
            
            h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .card-header, .profile-section {
                padding: 20px 15px;
            }
            
            .avatar-wrapper {
                width: 120px;
                height: 120px;
            }
            
            .form-control {
                padding: 12px 15px 12px 40px;
            }
            
            .input-icon {
                left: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>
    
    <div class="glass-card">
        <div class="card-header">
            <h1>Профайл шинэчлэх</h1>
            <p class="card-subtitle">Хувийн мэдээллээ шинэчлэнэ үү</p>
        </div>
        
        <div class="profile-section">
            <div class="avatar-wrapper">
                <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar" alt="Profile Picture">
                <label for="profile-picture" class="avatar-edit">
                    <i class="fas fa-camera"></i> Зураг солих
                </label>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="full_name">Бүтэн нэр</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Имэйл хаяг</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Утас</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="phone" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                </div>
                
                <input type="file" id="profile-picture" name="profile_picture" class="file-input" accept="image/*">
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Хадгалах
                </button>
            </form>
            
            <a href="teacher_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Буцах
            </a>
        </div>
    </div>

    <script>
        // Зураг солих товч дээр дарахад файл сонгох цонх гаргах
        document.querySelector('.avatar-edit').addEventListener('click', function() {
            document.getElementById('profile-picture').click();
        });
        
        // Сонгосон зураг дэлгэцэнд харуулах
        document.getElementById('profile-picture').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    document.querySelector('.avatar').src = event.target.result;
                    
                    // Зураг солигдсоны дараа жижиг анимаци
                    const avatar = document.querySelector('.avatar-wrapper');
                    avatar.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        avatar.style.transform = 'scale(1)';
                    }, 300);
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Form control фокус хийхэд icon өөрчлөгдөх
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            const icon = input.previousElementSibling;
            
            input.addEventListener('focus', () => {
                icon.style.color = 'var(--primary)';
                icon.style.transform = 'translateY(-50%) scale(1.2)';
            });
            
            input.addEventListener('blur', () => {
                icon.style.color = 'var(--gray)';
                icon.style.transform = 'translateY(-50%) scale(1)';
            });
        });
    </script>
</body>
</html>
