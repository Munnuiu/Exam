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

// Fetch the question ID from the URL
if (!isset($_GET['id'])) {
    header('Location: manage_questions.php');
    exit();
}

$question_id = $_GET['id'];

// Fetch the question details
$stmt = $pdo->prepare('SELECT eq.*, e.exam_name 
                       FROM questions eq 
                       JOIN exams e ON eq.exam_id = e.exam_id 
                       WHERE eq.id = ? AND e.teacher_id = ?');
$stmt->execute([$question_id, $teacher_id]);
$question = $stmt->fetch();

if (!$question) {
    echo "You do not have permission to edit this question.";
    exit();
}

$error = ''; // Variable to store error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $correct_option = $_POST['correct_option'];

    // Validate input fields
    if (empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4) || empty($correct_option)) {
        $error = 'All fields are required.';
    } else {
        // Update the question in the database
        $stmt = $pdo->prepare('UPDATE questions 
                               SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_option = ? 
                               WHERE id = ?');
        $stmt->execute([$question_text, $option1, $option2, $option3, $option4, $correct_option, $question_id]);

        // Redirect to the manage questions page after successful update
        header('Location: manage_questions.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Question</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --danger-color: #f72585;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .form-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-content {
            padding: 2rem;
        }

        .error-message {
            background-color: #fee;
            color: var(--danger-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--danger-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.25);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--gray-color);
            color: white;
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link svg {
            width: 1em;
            height: 1em;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .form-content {
                padding: 1.5rem;
            }
            
            .form-footer {
                flex-direction: column-reverse;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .btn-secondary {
                margin-left: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Edit Question</h1>
        </div>
        
        <div class="form-content">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="question_text">Question Text</label>
                    <textarea id="question_text" name="question_text" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="option1">Option 1</label>
                    <input type="text" id="option1" name="option1" value="<?php echo htmlspecialchars($question['option1']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="option2">Option 2</label>
                    <input type="text" id="option2" name="option2" value="<?php echo htmlspecialchars($question['option2']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="option3">Option 3</label>
                    <input type="text" id="option3" name="option3" value="<?php echo htmlspecialchars($question['option3']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="option4">Option 4</label>
                    <input type="text" id="option4" name="option4" value="<?php echo htmlspecialchars($question['option4']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="correct_option">Correct Answer</label>
                    <select id="correct_option" name="correct_option" required>
                        <option value="1" <?php echo $question['correct_option'] == 1 ? 'selected' : ''; ?>>Option 1</option>
                        <option value="2" <?php echo $question['correct_option'] == 2 ? 'selected' : ''; ?>>Option 2</option>
                        <option value="3" <?php echo $question['correct_option'] == 3 ? 'selected' : ''; ?>>Option 3</option>
                        <option value="4" <?php echo $question['correct_option'] == 4 ? 'selected' : ''; ?>>Option 4</option>
                    </select>
                </div>
                
                <div class="form-footer">
                    <a href="manage_questions.php" class="back-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Back to Questions
                    </a>
                    <div>
                        <button type="submit" class="btn btn-primary">Update Question</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>