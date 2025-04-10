<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_GET['id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $profile_picture = $user['profile_picture'];
    $password_changed = false;

    // Handle password change if new password provided
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $_SESSION['error'] = "Passwords do not match";
            header('Location: edit_user.php?id='.$user_id);
            exit();
        }
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $password_changed = true;
    }

    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        
        // Delete old picture if exists
        if (!empty($user['profile_picture']) && file_exists($target_dir.$user['profile_picture'])) {
            unlink($target_dir.$user['profile_picture']);
        }
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION['error'] = "Only JPG, JPEG, PNG, GIF image formats are allowed";
            header('Location: edit_user.php?id='.$user_id);
            exit();
        }
        
        // Generate unique filename
        $new_filename = uniqid().'.'.$file_ext;
        $target_file = $target_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture = $new_filename;
        } else {
            $_SESSION['error'] = "Error uploading image";
            header('Location: edit_user.php?id='.$user_id);
            exit();
        }
    }

    // Update user data in database
    if ($password_changed) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, profile_picture = ?, password = ? WHERE id = ?');
        $stmt->execute([$full_name, $email, $phone, $role, $profile_picture, $new_password, $user_id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, profile_picture = ? WHERE id = ?');
        $stmt->execute([$full_name, $email, $phone, $role, $profile_picture, $user_id]);
    }

    $_SESSION['success'] = "Хэрэглэгч амжилттай шинэчлэгдсэн!";
    header('Location: manage_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хэрэглэгчийг засах | Админ самбар</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Card Container */
        .edit-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        /* Header Section */
        .edit-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .edit-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .edit-header i {
            font-size: 1.3rem;
        }
        
        /* Profile Image Section */
        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .profile-img-container {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .change-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .change-photo-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }
        
        /* Form Styles */
        .edit-form {
            padding: 25px;
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
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .select-role {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }
        
        /* Button Styles */
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
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
            margin-top: 15px;
            width: 100%;
        }
        
        .btn-secondary:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        /* Footer Links */
        .form-footer {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }
        
        .back-link {
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 15px;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        /* Role Badges */
        .role-option {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: var(--transition);
        }
        
        .role-option:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: auto;
        }
        
        .role-badge.admin {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }
        
        .role-badge.teacher {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }
        
        .role-badge.student {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 576px) {
            .edit-container {
                max-width: 100%;
            }
            
            .edit-header h2 {
                font-size: 1.3rem;
            }
            
            .edit-form {
                padding: 20px;
            }
        }
        /* Add these new styles for password fields */
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
        }
        
        .password-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }
        
        .password-section h3 {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <form class="edit-form" method="POST" enctype="multipart/form-data">
            <div class="edit-header">
                <h2><i class="fas fa-user-edit"></i> Хэрэглэгчийн профайлыг засах</h2>
            </div>
            
            <div class="profile-section">
                <div class="profile-img-container">
                    <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" 
                         class="profile-img" alt="Profile Picture" id="profile-preview">
                    <button type="button" class="change-photo-btn" onclick="document.getElementById('profile_picture').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="profile_picture" name="profile_picture" style="display: none;" 
                           accept="image/*" onchange="previewImage(this)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="full_name" class="form-label">Бүтэн нэр</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Имэйл хаяг</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone" class="form-label">Утас</label>
                <input type="phone" id="phone" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Хэрэглэгчийн үүрэг</label>
                <select id="role" name="role" class="form-control select-role" required>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>
                    Админ
                    </option>
                    <option value="teacher" <?php echo $user['role'] == 'teacher' ? 'selected' : ''; ?>>
                    Багш 
                    </option>
                    <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>
                    Оюутан
                    </option>
                </select>
            </div>

            <!-- Password Change Section -->
            <div class="password-section">
                <h3><i class="fas fa-key"></i> Нууц үг солих</h3>
                
                <div class="form-group password-toggle">
                    <label for="new_password" class="form-label">Шинэ нууц үг</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                    <i class="fas fa-eye toggle-icon" onclick="togglePassword('new_password', this)"></i>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="confirm_password" class="form-label">Нууц үгээ баталгаажуулна уу</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_password', this)"></i>
                </div>
                
                <div class="form-group">
                    <small class="text-muted">Одоогийн нууц үгийг хадгалахын тулд хоосон орхино уу</small>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Профайлыг шинэчлэх
            </button>
            
            <div class="form-footer">
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Цуцлах
                </a>
            </div>
        </form>
    </div>

    <script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    </script>
</body>
</html>