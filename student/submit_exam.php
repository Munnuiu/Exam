<?php 
session_start();
require '../database/db.php';

// Хэрэглэгч нэвтэрсэн эсэхийг шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

// Шалгалтын ID-г авах
$exam_id = $_POST['exam_id'] ?? $_GET['exam_id'] ?? null;
if (!$exam_id) {
    die("✖ Алдаа: Шалгалтын ID олдсонгүй");
}

$student_id = $_SESSION['user_id'];

try {
    // Шалгалтын нэрийг авах
    $stmt = $pdo->prepare('SELECT exam_name FROM exams WHERE exam_id = ?');
    $stmt->execute([$exam_id]);
    $exam_name = $stmt->fetchColumn();

    if (!$exam_name) {
        die("✖ Алдаа: Шалгалтын нэр олдсонгүй. Exam ID: $exam_id");
    }

    // Оюутны хариултыг хадгалах
    $answer = $_POST['answer'] ?? [];
    save_student_answer($pdo, $exam_id, $student_id, $answer);

    // Оноог тооцоолох
    $score = calculate_score($pdo, $exam_id, $student_id);

    if ($score === null) {
        die("✖ Алдаа: Оноог тооцоолох боломжгүй");
    }

    // Шалгалтын оноог хадгалах
    save_exam_result($pdo, $exam_id, $student_id, $score);
    $message = "✓ Шалгалтын оноо амжилттай хадгалагдлаа!";

} catch (Exception $e) {
    die("✖ Алдаа: " . htmlspecialchars($e->getMessage()));
}

function save_student_answer($pdo, $exam_id, $student_id, $answer) {
    try {
        // Ensure answer are in correct format
        $formatted_answer = [];
        foreach ($answer as $q_id => $answer) {
            $formatted_answer[(int)$q_id] = (int)$answer;
        }
        
        $answer_json = json_encode($formatted_answer, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$exam_id, $student_id]);
        $existing_result = $stmt->fetchColumn();

        if ($existing_result) {
            $stmt = $pdo->prepare("UPDATE exam_results SET answer = ?, submitted_at = NOW() WHERE exam_id = ? AND student_id = ?");
            $stmt->execute([$answer_json, $exam_id, $student_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO exam_results (exam_id, student_id, answer, submitted_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$exam_id, $student_id, $answer_json]);
        }
    } catch (Exception $e) {
        die("✖ Алдаа: Оюутны хариултыг хадгалах үед алдаа гарлаа - " . htmlspecialchars($e->getMessage()));
    }
}

function save_exam_result($pdo, $exam_id, $student_id, $score) {
    try {
        $stmt = $pdo->prepare("UPDATE exam_results SET score = ?, submitted_at = NOW() WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$score, $exam_id, $student_id]);
    } catch (Exception $e) {
        die("✖ Алдаа: Оноог хадгалах үед алдаа гарлаа - " . htmlspecialchars($e->getMessage()));
    }
}

function calculate_score($pdo, $exam_id, $student_id) {
    try {
        // Get all questions for this exam
        $stmt = $pdo->prepare('SELECT id, correct_option FROM questions WHERE exam_id = ?');
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($questions) === 0) {
            return null;
        }

        // Get student's answer
        $stmt = $pdo->prepare('SELECT answer FROM exam_results WHERE exam_id = ? AND student_id = ?');
        $stmt->execute([$exam_id, $student_id]);
        $answer_json = $stmt->fetchColumn();

        if (!$answer_json) {
            return 0;
        }

        $student_answer = json_decode($answer_json, true);
        if (!is_array($student_answer)) {
            return 0;
        }

        $correct_answer = 0;
        foreach ($questions as $question) {
            $q_id = (int)$question['id'];
            $correct_option = (int)$question['correct_option'];
            
            if (isset($student_answer[$q_id]) && (int)$student_answer[$q_id] === $correct_option) {
                $correct_answer++;
            }
        }

        return round(($correct_answer / count($questions)) * 100, 2);

    } catch (Exception $e) {
        die("✖ Алдаа: Оноог тооцоолох үед алдаа гарлаа - " . htmlspecialchars($e->getMessage()));
    }
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шалгалтын Дүн</title>
    <style>
        .container { text-align: center; margin-top: 50px; }
        .message { color: green; font-size: 18px; margin-bottom: 20px; }
        .score-display { font-size: 24px; font-weight: bold; color: blue; margin-top: 20px; }
        .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #fff; background: #007BFF; padding: 10px 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Шалгалтын Дүн</h1>
        
        <div class="message"><?php echo $message; ?></div>
        
        <div class="exam-info">
            <p><strong>Шалгалтын нэр:</strong> <?php echo htmlspecialchars($exam_name); ?></p>
            <p><strong>Шалгалтын ID:</strong> <?php echo htmlspecialchars($exam_id); ?></p>
            <div class="score-display"><?php echo htmlspecialchars($score); ?>%</div>
        </div>
        
        <a href="student_dashboard.php" class="back-link">Буцах</a>
    </div>
</body>
</html>