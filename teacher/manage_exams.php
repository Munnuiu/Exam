<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

require '../database/db.php'; // Make sure this path is correct

$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new exam
    if (isset($_POST['add_exam'])) {
        $stmt = $pdo->prepare('INSERT INTO exams (exam_name, description, start_time, end_time, teacher_id) 
                              VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['exam_name'],
            $_POST['description'],
            $_POST['start_time'],
            $_POST['end_time'],
            $teacher_id
        ]);
    }
    // Update exam
    elseif (isset($_POST['update_exam'])) {
        $stmt = $pdo->prepare('UPDATE exams 
                              SET exam_name = ?, description = ?, start_time = ?, end_time = ? 
                              WHERE exam_id = ? AND teacher_id = ?');
        $stmt->execute([
            $_POST['exam_name'],
            $_POST['description'],
            $_POST['start_time'],
            $_POST['end_time'],
            $_POST['exam_id'],
            $teacher_id
        ]);
    }
    
    header('Location: manage_exams.php');
    exit();
}

// Handle exam deletion
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare('DELETE FROM exams WHERE exam_id = ? AND teacher_id = ?');
    $stmt->execute([$_GET['delete_id'], $teacher_id]);
    
    header('Location: manage_exams.php');
    exit();
}

// Fetch all exams for the current teacher
try {
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE teacher_id = ? ORDER BY created_at DESC');
    $stmt->execute([$teacher_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize as empty array if no exams found
    if (!$exams) {
        $exams = [];
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Exams</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        a {
            margin-right: 10px;
            text-decoration: none;
            color: #0066cc;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h2>Manage Exams</h2>
    
    <?php if (empty($exams)): ?>
        <p>No exams found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Exam Name</th>
                <th>Description</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($exams as $exam): ?>
            <tr>
                <td><?php echo htmlspecialchars($exam['exam_id']); ?></td>
                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                <td><?php echo htmlspecialchars($exam['description']); ?></td>
                <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                <td><?php echo htmlspecialchars($exam['created_at']); ?></td>
                <td>
                    <a href="edit_exam.php?id=<?php echo $exam['exam_id']; ?>">Edit</a>
                    <a href="manage_exams.php?delete_id=<?php echo $exam['exam_id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this exam?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    
    <p><a href="add_exam.php">Add New Exam</a></p>
    <p><a href="create_exam.php">Back to Dashboard</a></p>
</body>
</html>