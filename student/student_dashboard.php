<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

date_default_timezone_set('Asia/Ulaanbaatar');
$current_time = date('Y-m-d H:i:s');
$student_id = $_SESSION['user_id'];

// Оюутны мэдээллийг авах (with class and major info)
$student_info = $pdo->prepare("SELECT u.*, c.name AS class_name, m.name AS major_name 
                              FROM users u 
                              LEFT JOIN classes c ON u.class_id = c.id 
                              LEFT JOIN majors m ON c.major_id = m.id 
                              WHERE u.id = ?");
$student_info->execute([$student_id]);
$student = $student_info->fetch(PDO::FETCH_ASSOC);

// Тэнхимүүдийг авах (using departments table)
$departments = $pdo->query("SELECT id, name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$selected_department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

// Сонгосон тэнхимийн багш нарыг авах
$teachers = [];
if ($selected_department_id) {
    $teachers = $pdo->prepare("SELECT u.id, u.full_name 
                              FROM users u
                              WHERE u.department_id = ? AND u.role = 'teacher'");
    $teachers->execute([$selected_department_id]);
    $teachers = $teachers->fetchAll(PDO::FETCH_ASSOC);
}

$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;

// Шалгалтуудыг авах
$exams = ['active' => [], 'expired' => []];
if ($selected_teacher_id) {
    try {
        $stmt = $pdo->prepare("SELECT exam_id, exam_name, description, start_time, end_time FROM exams WHERE teacher_id = ?");
        $stmt->execute([$selected_teacher_id]);
        $all_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($all_exams as $exam) {
            if ($exam['start_time'] <= $current_time && $exam['end_time'] >= $current_time) {
                $exams['active'][] = $exam;
            } elseif ($exam['end_time'] < $current_time) {
                $exams['expired'][] = $exam;
            }
        }
    } catch (PDOException $e) {
        die("Алдаа гарлаа: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оюутны самбар</title>
    <style>
        /* Your existing CSS styles remain unchanged */
        /* Үндсэн стиль */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f2 100%);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
            position: relative;
        }

        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .info-item {
            flex: 1;
            min-width: 200px;
        }

        .info-label {
            display: block;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #2c3e50;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .profile-section {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            top: 70px;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            padding: 15px;
            width: 200px;
            z-index: 100;
        }

        .profile-dropdown.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .profile-dropdown a {
            display: block;
            padding: 8px 12px;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s;
            margin-bottom: 5px;
        }

        .profile-dropdown a:hover {
            background: #f1f5f9;
            color: #3498db;
        }

        .profile-dropdown a i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            gap: 10px;
        }

        .action-btn {
            padding: 10px 20px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .action-btn i {
            margin-right: 8px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .action-btn.results {
            background: linear-gradient(90deg, #2ecc71, #27ae60);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 2px;
        }

        h2 {
            color: #34495e;
            font-size: 1.8rem;
            margin-top: 40px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        /* Форм стиль */
        form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }

        select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        button {
            padding: 12px 25px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        /* Шалгалтын жагсаалт */
        ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        li {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        li:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .active-exam {
            border-left-color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.05);
        }

        .expired-exam {
            border-left-color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.05);
            opacity: 0.8;
        }

        .exam-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .exam-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }

        .exam-time {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .no-exams {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Анимаци */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Хариу үйлдэлтэй дизайн */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            form {
                flex-direction: column;
                align-items: stretch;
            }
            
            ul {
                grid-template-columns: 1fr;
            }

            .profile-section {
                position: static;
                justify-content: flex-end;
                margin-bottom: 20px;
            }

            .profile-dropdown {
                right: auto;
                left: 50%;
                transform: translateX(-50%);
            }
            
            .student-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .info-item {
                min-width: 100%;
            }
        }
        /* ... */
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="profile-section">
            <img src="<?php echo !empty($student['profile_picture']) ? '../uploads/' . $student['profile_picture'] : '../assets/default-profile.png'; ?>" 
                 alt="Profile" class="profile-pic" id="profilePic">
            <div class="profile-dropdown" id="profileDropdown">
                <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Хувийн мэдээлэл</a>
                <a href="change_password.php"><i class="fas fa-key"></i> Нууц үг солих</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Гарах</a>
            </div>
        </div>
        
        <div class="student-info">
            <div class="info-item">
                <span class="info-label">Оюутны нэр</span>
                <p class="info-value"><?= htmlspecialchars($student['full_name'] ?? '') ?></p>
            </div>
            
            <?php if (!empty($student['class_name'])): ?>
                <div class="info-item">
                    <span class="info-label">Анги</span>
                    <p class="info-value"><?= htmlspecialchars($student['class_name']) ?></p>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Мэргэжил</span>
                    <p class="info-value"><?= htmlspecialchars($student['major_name'] ?? '') ?></p>
                </div>
            <?php endif; ?>
            
            <div class="info-item">
                <span class="info-label">Оюутны ID</span>
                <p class="info-value"><?= htmlspecialchars($student['student_id'] ?? '') ?></p>
            </div>
        </div>
        
        <h1>Оюутны самбар</h1>
        
        <div class="action-buttons">
            <a href="exam_results.php" class="action-btn results">
                <i class="fas fa-chart-bar"></i> Дүн харах
            </a>
        </div>
        
        <form method="get" action="student_dashboard.php">
            <div class="form-group">
                <select name="department_id" id="departmentSelect" required onchange="this.form.submit()">
                    <option value="">-- Тэнхим сонгох --</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['id']; ?>" <?= ($department['id'] == $selected_department_id) ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($selected_department_id): ?>
                <div class="form-group">
                    <select name="teacher_id" id="teacherSelect" required>
                        <option value="">-- Багш сонгох --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?= ($teacher['id'] == $selected_teacher_id) ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($teacher['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit">Шалгалтууд харах</button>
            <?php endif; ?>
        </form>
        
        <?php if ($selected_teacher_id): ?>
            <h2>Идэвхтэй шалгалтууд</h2>
            <?php if (!empty($exams['active'])): ?>
                <ul>
                    <?php foreach ($exams['active'] as $exam): ?>
                        <li class="active-exam">
                            <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                            <div class="exam-time">
                                <?php echo date('Y-m-d H:i', strtotime($exam['start_time'])) . " - " . date('Y-m-d H:i', strtotime($exam['end_time'])); ?>
                            </div>
                            <a href="take_exam.php?exam_id=<?= $exam['exam_id'] ?>" class="exam-link">Шалгалт өгөх</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-exams">Идэвхтэй шалгалт алга.</div>
            <?php endif; ?>
            
            <h2>Дууссан шалгалтууд</h2>
            <?php if (!empty($exams['expired'])): ?>
                <ul>
                    <?php foreach ($exams['expired'] as $exam): ?>
                        <li class="expired-exam">
                            <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                            <div class="exam-time">
                                Дууссан хугацаа: <?php echo date('Y-m-d H:i', strtotime($exam['end_time'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-exams">Дууссан шалгалт алга.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Profile dropdown-г удирдах
        document.getElementById('profilePic').addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.toggle('show');
        });

        // Дэлгэцэн дээр хаахад dropdown-г хаах
        window.addEventListener('click', function(event) {
            if (!event.target.matches('#profilePic')) {
                var dropdown = document.getElementById('profileDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Тэнхим сонгогдвол автоматаар form-г илгээх
        document.getElementById('departmentSelect').addEventListener('change', function() {
            if (this.value) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>