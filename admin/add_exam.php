<?php
session_start();
require '../database/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if the user is a teacher or admin
$role = $_SESSION['role'];
if ($role != 'teacher' && $role != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_name = trim($_POST['exam_name']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $created_at = date('Y-m-d H:i:s');

    // Validate input
    if (empty($exam_name) || empty($start_time) || empty($end_time)) {
        $_SESSION['error'] = "Шаардлагатай бүх талбарыг бөглөх ёстой.";
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $_SESSION['error'] = "Дуусах цаг нь эхлэх цагаас хойш байх ёстой.";
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO exams (exam_name, teacher_id, description, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$exam_name, $teacher_id, $description, $start_time, $end_time, $created_at]);

            $_SESSION['success'] = "Шалгалт амжилттай үүсгэгдсэн!";
            header('Location: manage_exams.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Шалгалт үүсгэх алдаа: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шинэ шалгалт үүсгэх</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --danger: #ef4444;
            --success: #10b981;
            --light: #f9fafb;
            --dark: #111827;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .btn-primary {
            background: var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .ck-editor__editable {
            min-height: 150px;
            border-radius: 0 0 8px 8px !important;
        }
        
        .ck.ck-toolbar {
            border-radius: 8px 8px 0 0 !important;
            border-color: #e5e7eb !important;
        }
        
        .ck.ck-editor__main>.ck-editor__editable {
            border-color: #e5e7eb !important;
            border-top: none !important;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="w-full max-w-2xl animate-fade-in">
        <div class="card">
            <!-- Header -->
            <div class="header-gradient px-6 py-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-book-open mr-2"></i> Шинэ шалгалт үүсгэх
                </h2>
            </div>
            
            <!-- Body -->
            <div class="p-6">
                <!-- Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-4 p-3 bg-red-50 text-red-600 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-4 p-3 bg-green-50 text-green-600 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Form -->
                <form method="POST" action="add_exam.php" class="space-y-5">
                    <!-- Exam Name -->
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-book text-blue-500 mr-2"></i>Шалгалтын нэр<span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="exam_name" placeholder="Шалгалтын нэрийг оруулна уу" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               required>
                    </div>
                    
                    <!-- Description with Rich Text Editor -->
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-align-left text-blue-500 mr-2"></i>Тодорхойлолт
                        </label>
                        <textarea id="editor" name="description" placeholder="Шалгалтын тайлбарыг оруулна уу"></textarea>
                    </div>
                    
                    <!-- Date/Time Picker -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>Эхлэх цаг<span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="start_time" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>Дуусах цаг <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="end_time" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4">
                        <a href="manage_exams.php" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-arrow-left mr-2"></i> Шалгалтууд руу буцах
                        </a>
                        <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i> Шалгалт үүсгэх
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize CKEditor
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'alignment', '|',
                        'numberedList', 'bulletedList', '|',
                        'link', 'blockQuote', 'insertTable', '|',
                        'undo', 'redo'
                    ],
                    shouldNotGroupWhenFull: true
                },
                language: 'en',
                licenseKey: '',
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>