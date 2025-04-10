<?php
session_start();
require '../database/db.php';

// Холболт
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Багшийн ID шалгах
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Багшийн ID буруу байна";
    header("Location: index.php");
    exit();
}

$teacher_id = (int)$_GET['id'];

// Багшийн мэдээлэл авах
$stmt = $conn->prepare("SELECT id, full_name, email, phone, department_id FROM users WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$stmt->close();

if (!$teacher) {
    $_SESSION['error'] = "Багш олдсонгүй";
    header("Location: index.php");
    exit();
}

// Тэнхимүүдийн жагсаалт
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");

// Мэдээлэл шинэчлэх
if (isset($_POST['update_teacher'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    // Валидаци
    if (empty($full_name) || empty($email)) {
        $_SESSION['error'] = "Бүх талбарыг бөглөнө үү";
    } else {
        // Check if department_id should be NULL or has value
        if ($department_id === null) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department_id = NULL WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $teacher_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("sssii", $full_name, $email, $phone, $department_id, $teacher_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Багшийн мэдээлэл амжилттай шинэчлэгдлээ";
            header("Location: teachers.php");
            exit();
        } else {
            $_SESSION['error'] = "Алдаа гарлаа: " . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Багшийн мэдээлэл засах</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .teacher-form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .form-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .profile-icon {
            font-size: 3rem;
            color: #4361ee;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="teacher-form-container">
            <div class="text-center form-header">
                <i class="bi bi-person-badge profile-icon"></i>
                <h2>Багшийн мэдээлэл засах</h2>
                <p class="text-muted">ID: <?= $teacher['id'] ?></p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Багшийн нэр</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">И-мэйл</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($teacher['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Утасны дугаар</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($teacher['phone']) ?>">
                </div>

                <div class="mb-4">
                    <label for="department_id" class="form-label">Тэнхим</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">-- Тэнхим сонгох --</option>
                        <?php
                        if ($departments->num_rows > 0) {
                            $departments->data_seek(0);
                            while ($row = $departments->fetch_assoc()) {
                                $selected = $row['id'] == $teacher['department_id'] ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="teachers.php" class="btn btn-outline-secondary me-md-2">
                        <i class="bi bi-x-circle"></i> Цуцлах
                    </a>
                    <button type="submit" name="update_teacher" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Хадгалах
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>