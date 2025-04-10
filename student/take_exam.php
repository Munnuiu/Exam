<?php
session_start();

// Check if the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

require '../database/db.php';

$student_id = $_SESSION['user_id'];
$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    die("Exam ID not provided.");
}

// Fetch exam details
$stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    die("Exam not found.");
}

// Fetch questions
$stmt = $pdo->prepare('SELECT * FROM questions WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $score = 0;
    $total_questions = count($questions);

    foreach ($questions as $question) {
        $question_id = $question['id'];
        $correct_option = $question['correct_option'];
        // Changed to handle array format from form
        $student_answer = $_POST['answer'][$question_id] ?? null;

        if ($student_answer && $student_answer == $correct_option) {
            $score++;
        }
    }

    if ($total_questions > 0) {
        $score = ($score / $total_questions) * 100;
    } else {
        $score = 0;
    }

    // Save result
    $stmt = $pdo->prepare('INSERT INTO exam_results (exam_id, student_id, score) VALUES (?, ?, ?)');
    $stmt->execute([$exam_id, $student_id, $score]);

    // Redirect after submission
    header('Location: exam_results.php?exam_id='.$exam_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: #333;
        }

        /* Container */
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #3498db;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .profile-container {
            display: flex;
            align-items: center;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .nav-item {
            color: white;
            font-weight: 500;
        }

        /* Exam Heading */
        .main-top h1 {
            font-size: 24px;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }

        /* Card Styling */
        .card {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        /* Radio Button Styling */
        .options {
            display: flex;
            flex-direction: column;
        }

        .options label {
            background: #ecf0f1;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .options label:hover {
            background: #d5dbdb;
        }

        .options input {
            margin-right: 12px;
            transform: scale(1.1);
        }

        /* Submit Button */
        .submit-btn {
            display: block;
            width: 100%;
            padding: 14px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color: white;
            background: #2ecc71;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #27ae60;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 15px;
            }

            .submit-btn {
                font-size: 16px;
                padding: 12px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <div class="profile-container">
                <img src="../uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-img">
            </div>
            <a>
                <span class="nav-item">Амжилт</span>
            </a>
        </nav>
        <form action="take_exam.php?exam_id=<?php echo $exam_id; ?>" method="post">
        <div class="main">
            <div class="main-top">
                <h1>Take Exam: <?php echo htmlspecialchars($exam['exam_name']); ?></h1>
            </div>

            <div class="main-skills">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <?php foreach ($questions as $question): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                            <div class="options">
                                <label>
                                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="1" required>
                                    <?php echo htmlspecialchars($question['option1']); ?>
                                </label>
                                <label>
                                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="2">
                                    <?php echo htmlspecialchars($question['option2']); ?>
                                </label>
                                <label>
                                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="3">
                                    <?php echo htmlspecialchars($question['option3']); ?>
                                </label>
                                <label>
                                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="4">
                                    <?php echo htmlspecialchars($question['option4']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="submit-btn">Шалгалтыг дуусгах</button>
            </div>
        </div>
        </form>
    </div>
</body>
</html>

