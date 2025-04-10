<?php
session_start();
require '../database/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if the user is a student or admin
$role = $_SESSION['role'];
if ($role != 'student' && $role != 'admin') {
    // Teachers or unauthorized roles are redirected to the dashboard
    header('Location: dashboard.php');
    exit();
}

$exam_id = $_GET['id'];

// Fetch exam questions
$stmt = $pdo->prepare('SELECT * FROM examquestions WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Exam</title>
    <style>
        /* Modern CSS Variables */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --text-color: #333;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Reset and Basic Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
            color: var(--text-color);
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
        }

        /* Form Styles */
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .question {
            margin-bottom: 20px;
        }

        .question p {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .question label {
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .question input[type="radio"] {
            margin-right: 10px;
        }

        /* Button Styles */
        button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Back Link */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h2>Exam Questions</h2>

    <form method="POST" action="submit_exam.php">
        <?php foreach ($questions as $question): ?>
            <div class="question">
                <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                <label>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="1" required>
                    <?php echo htmlspecialchars($question['option1']); ?>
                </label>
                <label>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="2" required>
                    <?php echo htmlspecialchars($question['option2']); ?>
                </label>
                <label>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="3" required>
                    <?php echo htmlspecialchars($question['option3']); ?>
                </label>
                <label>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="4" required>
                    <?php echo htmlspecialchars($question['option4']); ?>
                </label>
            </div>
        <?php endforeach; ?>

        <button type="submit">Submit Exam</button>
    </form>

    <a href="take_exam.php" class="back-link">Back to Exams</a>
</body>
</html>