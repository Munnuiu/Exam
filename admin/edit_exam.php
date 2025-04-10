<?php
session_start();
require '../database/db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Get the exam ID from the URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Exam ID is missing.";
    header('Location: manage_exams.php');
    exit();
}

$exam_id = $_GET['id'];

// Fetch the exam details from the database
$stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    $_SESSION['error'] = "Шалгалт олдсонгүй.";
    header('Location: manage_exams.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_name = trim($_POST['exam_name']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Validate inputs
    if (empty($exam_name) || empty($start_time) || empty($end_time)) {
        $_SESSION['error'] = "Шаардлагатай бүх талбарыг бөглөнө үү.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $_SESSION['error'] = "Дуусах цаг нь эхлэх цагаас хойш байх ёстой.";
    } else {
        try {
            // Update the exam in the database
            $stmt = $pdo->prepare('UPDATE exams SET exam_name = ?, description = ?, start_time = ?, end_time = ? WHERE exam_id = ?');
            $stmt->execute([$exam_name, $description, $start_time, $end_time, $exam_id]);

            $_SESSION['success'] = "Шалгалт амжилттай шинэчлэгдсэн.";
            header('Location: manage_exams.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Шалгалтыг шинэчлэхэд алдаа гарлаа: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шалгалтыг засварлах</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
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
        
        .edit-container {
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
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
        
        .edit-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: var(--dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label .required {
            color: var(--danger);
            margin-left: 4px;
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
        
        .ck-editor__editable {
            min-height: 200px;
            border-radius: 0 0 var(--border-radius) var(--border-radius) !important;
        }
        
        .ck.ck-toolbar {
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: 1px solid var(--light-gray) !important;
            border-bottom: none !important;
        }
        
        .ck.ck-editor__main>.ck-editor__editable {
            border: 1px solid var(--light-gray) !important;
            border-top: none !important;
        }
        
        .datetime-input {
            display: flex;
            gap: 15px;
        }
        
        .datetime-input .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
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
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
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
        
        @media (max-width: 768px) {
            .edit-form {
                padding: 20px;
            }
            
            .datetime-input {
                flex-direction: column;
                gap: 25px;
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h2><i class="fas fa-edit"></i> Шалгалтыг засварлах</h2>
        </div>
        
        <div class="edit-form">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="edit_exam.php?id=<?php echo $exam_id; ?>" method="POST">
                <div class="form-group">
                    <label for="exam_name">
                        <i class="fas fa-book"></i> Шалгалтын нэр <span class="required">*</span>
                    </label>
                    <input type="text" id="exam_name" name="exam_name" class="form-control" 
                           value="<?php echo htmlspecialchars($exam['exam_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="editor">
                        <i class="fas fa-align-left"></i> Тодорхойлолт
                    </label>
                    <textarea id="editor" name="description"><?php echo htmlspecialchars($exam['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                
                <div class="datetime-input">
                    <div class="form-group">
                        <label for="start_time">
                            <i class="fas fa-clock"></i> Эхлэх цаг <span class="required">*</span>
                        </label>
                        <input type="datetime-local" id="start_time" name="start_time" class="form-control" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($exam['start_time'])); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">
                            <i class="fas fa-clock"></i> Дуусах цаг <span class="required">*</span>
                        </label>
                        <input type="datetime-local" id="end_time" name="end_time" class="form-control" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($exam['end_time'])); ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="manage_exams.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Шалгалтууд руу буцах
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Шалгалтыг шинэчлэх
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'alignment', '|',
                        'numberedList', 'bulletedList', '|',
                        'indent', 'outdent', '|',
                        'link', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                        'undo', 'redo', '|',
                        'fontBackgroundColor', 'fontColor', 'fontSize', 'fontFamily', '|',
                        'code', 'codeBlock', '|',
                        'specialCharacters', 'horizontalLine'
                    ],
                    shouldNotGroupWhenFull: false
                },
                language: 'en',
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                        'tableProperties',
                        'tableCellProperties'
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>