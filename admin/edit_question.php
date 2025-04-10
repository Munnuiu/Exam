<?php
session_start();
require_once '../database/db.php';

// Check if user is admin or teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

// Get question details
$question = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $correct_option = $_POST['correct_option'];

    try {
        $stmt = $pdo->prepare("UPDATE questions SET exam_id = ?, question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_option = ? WHERE id = ?");
        $stmt->execute([$exam_id, $question_text, $option1, $option2, $option3, $option4, $correct_option, $id]);

        $_SESSION['success'] = "Question updated successfully!";
        header("Location: manage_questions.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating question: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question | Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
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
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: rgba(79, 70, 229, 0.05);
        }
        
        /* CKEditor Styles */
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
        
        .ck.ck-dropdown__panel, .ck.ck-list {
            z-index: 1005 !important;
        }
        
        .option-input {
            position: relative;
        }
        
        .option-input input {
            padding-left: 30px;
        }
        
        .option-input::before {
            content: "•";
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 20px;
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
<body class="p-4 md:p-6">
    <div class="max-w-3xl mx-auto animate-fade-in">
        <div class="card">
            <!-- Header -->
            <div class="header-gradient px-6 py-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-question-circle mr-2"></i> Edit Question
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
                
                <?php if (isset($question)): ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $question['id']; ?>">
                    <input type="hidden" name="exam_id" value="<?php echo $question['exam_id']; ?>">
                    
                    <!-- Question Text with Rich Text Editor -->
                    <div class="mb-6">
                        <label class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-question text-primary mr-2"></i>Question Text <span class="text-red-500">*</span>
                        </label>
                        <textarea id="editor" name="question_text"><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                    </div>
                    
                    <!-- Options -->
                    <div class="space-y-4 mb-6">
                        <h3 class="text-lg font-medium text-gray-700 flex items-center">
                            <i class="fas fa-list-ol text-primary mr-2"></i>Options
                        </h3>
                        
                        <div class="option-input">
                            <label class="sr-only">Option 1</label>
                            <input type="text" name="option1" value="<?php echo htmlspecialchars($question['option1']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                   placeholder="Option 1" required>
                        </div>
                        
                        <div class="option-input">
                            <label class="sr-only">Option 2</label>
                            <input type="text" name="option2" value="<?php echo htmlspecialchars($question['option2']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                   placeholder="Option 2" required>
                        </div>
                        
                        <div class="option-input">
                            <label class="sr-only">Option 3</label>
                            <input type="text" name="option3" value="<?php echo htmlspecialchars($question['option3']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                   placeholder="Option 3" required>
                        </div>
                        
                        <div class="option-input">
                            <label class="sr-only">Option 4</label>
                            <input type="text" name="option4" value="<?php echo htmlspecialchars($question['option4']); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                   placeholder="Option 4" required>
                        </div>
                    </div>
                    
                    <!-- Correct Option -->
                    <div class="mb-6">
                        <label class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-check-circle text-primary mr-2"></i>Correct Option <span class="text-red-500">*</span>
                        </label>
                        <select name="correct_option" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition" required>
                            <option value="1" <?php echo $question['correct_option'] == 1 ? 'selected' : ''; ?>>Option 1</option>
                            <option value="2" <?php echo $question['correct_option'] == 2 ? 'selected' : ''; ?>>Option 2</option>
                            <option value="3" <?php echo $question['correct_option'] == 3 ? 'selected' : ''; ?>>Option 3</option>
                            <option value="4" <?php echo $question['correct_option'] == 4 ? 'selected' : ''; ?>>Option 4</option>
                        </select>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4">
                        <a href="manage_questions.php" class="btn-secondary text-center px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i> Cancel
                        </a>
                        <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i> Update Question
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-question-circle text-4xl mb-2"></i>
                    <p>Question not found</p>
                    <a href="manage_questions.php" class="inline-block mt-4 text-primary hover:underline">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Questions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize CKEditor for question text
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'alignment', '|',
                        'numberedList', 'bulletedList', '|',
                        'link', 'blockQuote', 'insertTable', '|',
                        'undo', 'redo', '|',
                        'fontBackgroundColor', 'fontColor', 'fontSize', '|',
                        'specialCharacters', 'horizontalLine'
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