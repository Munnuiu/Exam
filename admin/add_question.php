<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

// Fetch available exams from database
$exams = [];
try {
    $stmt = $pdo->query("SELECT exam_id, exam_name FROM exams");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not fetch exams: " . $e->getMessage();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option1 = $_POST['option1'];
    $option2 = $_POST['option2'];
    $option3 = $_POST['option3'];
    $option4 = $_POST['option4'];
    $correct_option = $_POST['correct_option'];

    $sql = "INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id, $question_text, $option1, $option2, $option3, $option4, $correct_option]);

    if ($stmt->rowCount() > 0) {
        header("Location: manage_questions.php");
        exit();
    } else {
        $error = 'Error adding question. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Question</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --gray: #adb5bd;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            padding: 2.5rem;
            width: 100%;
            max-width: 700px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.25);
        }

        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 2rem;
            position: relative;
        }

        h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--accent);
            margin: 0.5rem auto 0;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
            font-size: 0.9rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.7);
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: var(--white);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--gray);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #6c757d;
            transform: translateY(-2px);
        }

        .error-message {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--danger);
            font-size: 0.9rem;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .glass-card {
                padding: 1.5rem;
            }
            
            .form-footer {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Floating label animation */
        .floating-label {
            position: relative;
        }

        .floating-label label {
            position: absolute;
            top: 0.8rem;
            left: 1rem;
            color: var(--gray);
            transition: all 0.3s;
            pointer-events: none;
        }

        .floating-label input:focus + label,
        .floating-label input:not(:placeholder-shown) + label,
        .floating-label textarea:focus + label,
        .floating-label textarea:not(:placeholder-shown) + label {
            top: -0.5rem;
            left: 0.8rem;
            font-size: 0.75rem;
            background-color: var(--white);
            padding: 0 0.2rem;
            color: var(--primary);
        }

        /* Animated button */
        .btn-animate {
            position: relative;
            overflow: hidden;
        }

        .btn-animate::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn-animate:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
   /* Additional style for exam dropdown */
   .exam-select {
            position: relative;
        }
        
        .exam-select::after {
            content: '▼';
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: var(--primary);
            pointer-events: none;
        }
        
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="glass-card">
        <h1>Add New Question</h1>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group exam-select">
                <label for="exam_id">Select Exam</label>
                <select name="exam_id" id="exam_id" required>
                    <option value="">-- Select an Exam --</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
                            <?php echo htmlspecialchars($exam['exam_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- [Rest of your form fields remain the same] -->
            <div class="form-group floating-label">
                <textarea name="question_text" id="question_text" placeholder=" " required></textarea>
                <label for="question_text">Question Text</label>
            </div>
            
            <div class="form-group floating-label">
                <input type="text" name="option1" id="option1" placeholder=" " required>
                <label for="option1">Option 1</label>
            </div>
            
            <div class="form-group floating-label">
                <input type="text" name="option2" id="option2" placeholder=" " required>
                <label for="option2">Option 2</label>
            </div>
            
            <div class="form-group floating-label">
                <input type="text" name="option3" id="option3" placeholder=" " required>
                <label for="option3">Option 3</label>
            </div>
            
            <div class="form-group floating-label">
                <input type="text" name="option4" id="option4" placeholder=" " required>
                <label for="option4">Option 4</label>
            </div>
            
            <div class="form-group">
                <label for="correct_option">Correct Option</label>
                <select name="correct_option" id="correct_option" required>
                    <option value="">Select correct option</option>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary btn-animate">
                    <span>Add Question</span>
                </button>
                <a href="../admin/manage_questions.php" class="btn btn-secondary">
                    Back to Questions
                </a>
            </div>
        </form>
    </div>
</body>
</html>