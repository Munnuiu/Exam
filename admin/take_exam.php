<?php
session_start();
require '../database/db.php'; // Adjust the path to your db.php file

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

// Fetch exams available for the student or admin
$stmt = $pdo->query('SELECT * FROM exams');
$exams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h2>Take Exam</h2>

    <?php if (empty($exams)): ?>
        <p>No exams available at the moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Exam ID</th>
                    <th>Exam Name</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?php echo $exam['exam_id']; ?></td>
                        <td><?php echo $exam['exam_name']; ?></td>
                        <td><?php echo $exam['created_at']; ?></td>
                        <td>
                            <a href="view_exam.php?id=<?php echo $exam['exam_id']; ?>" class="btn">Take Exam</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="admin_dashboard.php" class="btn">Back to Dashboard</a></p>
</body>
</html>