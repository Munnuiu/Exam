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

// Pagination variables
$rows_per_page = 3;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Search functionality
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = 'SELECT eq.*, e.exam_name, e.exam_id
          FROM questions eq 
          JOIN exams e ON eq.exam_id = e.exam_id
          WHERE e.teacher_id = ?';

$params = [$teacher_id];

// Add search condition if search term exists
if (!empty($search_term)) {
    $query .= ' AND (eq.question_text LIKE ? OR e.exam_name LIKE ?)';
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Count total questions for pagination
$count_query = str_replace('eq.*, e.exam_name, e.exam_id', 'COUNT(*) as total', $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_rows / $rows_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Add pagination to main query
$offset = ($current_page - 1) * $rows_per_page;
$query .= " LIMIT $offset, $rows_per_page";

// Fetch questions for current page
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll();

// Handle question deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Verify that the question belongs to the logged-in teacher
    $stmt = $pdo->prepare('SELECT eq.* FROM questions eq 
                           JOIN exams e ON eq.exam_id = e.exam_id 
                           WHERE eq.id = ? AND e.teacher_id = ?');
    $stmt->execute([$delete_id, $teacher_id]);
    $question = $stmt->fetch();

    if ($question) {
        // Delete the question
        $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
        $stmt->execute([$delete_id]);

        // Redirect to refresh the page
        header('Location: manage_questions.php?page=' . $current_page . (!empty($search_term) ? '&search=' . urlencode($search_term) : ''));
        exit();
    } else {
        echo "You do not have permission to delete this question.";
    }
}

// Handle exam name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exam_name'])) {
    $exam_id = $_POST['exam_id'];
    $new_exam_name = $_POST['new_exam_name'];
    
    // Verify that the exam belongs to the logged-in teacher
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?');
    $stmt->execute([$exam_id, $teacher_id]);
    $exam = $stmt->fetch();
    
    if ($exam) {
        // Update the exam name
        $stmt = $pdo->prepare('UPDATE exams SET exam_name = ? WHERE exam_id = ?');
        $stmt->execute([$new_exam_name, $exam_id]);
        
        // Redirect to refresh the page
        header('Location: manage_questions.php?page=' . $current_page . (!empty($search_term) ? '&search=' . urlencode($search_term) : ''));
        exit();
    } else {
        echo "You do not have permission to update this exam.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions</title>
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
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        h1::before {
            content: "📝";
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            gap: 0.5rem;
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
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #e3176a;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #3ab0d6;
            transform: translateY(-2px);
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            width: 100%;
            max-width: 500px;
        }

        .search-box input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.25);
        }

        .search-box button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-box button:hover {
            background-color: #3a7bc8;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            overflow: hidden;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e9f5ff;
        }

        .exam-header {
            background-color: #e3f2fd !important;
            font-weight: 600;
        }

        .exam-header td {
            padding: 1rem;
            border-bottom: 2px solid var(--accent-color);
        }

        .exam-name-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-left: 1rem;
        }

        .exam-name-form input {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .exam-name-form button {
            padding: 0.5rem 1rem;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .exam-name-form button:hover {
            background-color: #3ab0d6;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .edit-btn {
            background-color: var(--accent-color);
            color: white;
        }

        .edit-btn:hover {
            background-color: #3a7bc8;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .delete-btn:hover {
            background-color: #e3176a;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: var(--transition);
        }

        .pagination a {
            color: var(--primary-color);
            border: 1px solid #dee2e6;
        }

        .pagination a:hover {
            background-color: #e9ecef;
        }

        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #adb5bd;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Questions</h1>
        
        <div class="header-actions">
            <div class="search-box">
                <input type="text" name="search" placeholder="Search by question or exam name..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="manage_questions.php" class="btn btn-secondary">Clear Search</a>
                <?php endif; ?>
            </div>
            
            <div>
                <a href="add_question.php" class="btn btn-primary">
                    <span>+</span> Add New Question
                </a>
                <a href="teacher_dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="empty-state">
                <div>📭</div>
                <h3>No questions found</h3>
                <p>Start by adding a new question</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Exam Name</th>
                        <th>Question Text</th>
                        <th>Options</th>
                        <th>Correct Answer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_exam_id = null;
                    foreach ($questions as $question): 
                        if ($current_exam_id !== $question['exam_id']):
                            $current_exam_id = $question['exam_id'];
                    ?>
                    <tr class="exam-header">
                        <td colspan="6">
                            <strong>Exam: </strong>
                            <span id="exam-name-<?php echo $question['exam_id']; ?>">
                                <?php echo htmlspecialchars($question['exam_name']); ?>
                            </span>
                            <form class="exam-name-form" method="post" action="manage_questions.php">
                                <input type="hidden" name="exam_id" value="<?php echo $question['exam_id']; ?>">
                                <input type="text" name="new_exam_name" value="<?php echo htmlspecialchars($question['exam_name']); ?>" required>
                                <button type="submit" name="update_exam_name">Update</button>
                                <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                                <?php if (!empty($search_term)): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?php echo $question['id']; ?></td>
                        <td><?php echo htmlspecialchars($question['exam_name']); ?></td>
                        <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                        <td>
                            <ol type="A" style="margin-left: 1.2rem;">
                                <li><?php echo htmlspecialchars($question['option1']); ?></li>
                                <li><?php echo htmlspecialchars($question['option2']); ?></li>
                                <li><?php echo htmlspecialchars($question['option3']); ?></li>
                                <li><?php echo htmlspecialchars($question['option4']); ?></li>
                            </ol>
                        </td>
                        <td>
                            <span style="background-color: #d4edda; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                <?php echo $question['correct_option']; ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="edit_question.php?id=<?php echo $question['id']; ?>" class="action-btn edit-btn">Edit</a>
                            <a href="manage_questions.php?delete_id=<?php echo $question['id']; ?>&page=<?php echo $current_page; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                               class="action-btn delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this question?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="manage_questions.php?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">First</a>
                <a href="manage_questions.php?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Previous</a>
            <?php endif; ?>

            <?php 
            // Show page numbers
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);
            
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="manage_questions.php?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="manage_questions.php?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Next</a>
                <a href="manage_questions.php?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Last</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>