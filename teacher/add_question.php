<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.php');
    exit();
}

// Include the database connection
require '../database/db.php';

$teacher_id = $_SESSION['user_id']; // Get the logged-in teacher's ID

// Fetch exams created by the logged-in teacher
$stmt = $pdo->prepare('SELECT * FROM exams WHERE teacher_id = ?');
$stmt->execute([$teacher_id]);
$exams = $stmt->fetchAll();

$error = ''; // Variable to store error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $correct_option = $_POST['correct_option'];

    // Validate input fields
    if (empty($exam_id) || empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4) || empty($correct_option)) {
        $error = 'All fields are required.';
    } else {
        // Verify that the exam belongs to the logged-in teacher
        $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?');
        $stmt->execute([$exam_id, $teacher_id]);
        $exam = $stmt->fetch();

        if ($exam) {
            // Insert the new question into the database
            $stmt = $pdo->prepare('INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$exam_id, $question_text, $option1, $option2, $option3, $option4, $correct_option]);

            // Redirect to the manage questions page after successful insertion
            header('Location: manage_questions.php');
            exit();
        } else {
            $error = 'You do not have permission to add questions to this exam.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Question</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .error {
            color: var(--danger-color);
            background-color: #ffebee;
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--danger-color);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input[type="text"], 
        textarea, 
        select {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        input[type="text"]:focus, 
        textarea:focus, 
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        button {
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-block;
        }
        
        button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            padding: 8px 0;
        }
        
        .back-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Question</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="exam_id">Exam:</label>
                <select id="exam_id" name="exam_id" required>
                    <option value="">Select an exam</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['exam_id']; ?>"><?php echo htmlspecialchars($exam['exam_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="question_text">Question Text:</label>
                <textarea id="question_text" name="question_text" required></textarea>
            </div>

            <div class="options-grid">
                <div class="form-group">
                    <label for="option1">Option 1:</label>
                    <input type="text" id="option1" name="option1" required>
                </div>

                <div class="form-group">
                    <label for="option2">Option 2:</label>
                    <input type="text" id="option2" name="option2" required>
                </div>

                <div class="form-group">
                    <label for="option3">Option 3:</label>
                    <input type="text" id="option3" name="option3" required>
                </div>

                <div class="form-group">
                    <label for="option4">Option 4:</label>
                    <input type="text" id="option4" name="option4" required>
                </div>
            </div>

            <div class="form-group">
                <label for="correct_option">Correct Option:</label>
                <select id="correct_option" name="correct_option" required>
                    <option value="">Select the correct option</option>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <button type="submit">Add Question</button>
        </form>
        <a href="manage_questions.php" class="back-link">← Back to Manage Questions</a>
    </div>
</body>
</html>