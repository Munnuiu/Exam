<?php
// manage_questions.php

session_start();
require_once '../database/db.php';

// Check if user is admin or teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

// Initialize search term
$searchTerm = '';
$questions = [];

// Check if search form was submitted
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    
    // Prepare SQL with search condition using positional parameters
    $sql = "SELECT * FROM questions WHERE 
            question_text LIKE ? OR
            option1 LIKE ? OR
            option2 LIKE ? OR
            option3 LIKE ? OR
            option4 LIKE ?";
    
    $stmt = $pdo->prepare($sql);
    // Execute with array of parameters
    $stmt->execute([
        "%$searchTerm%",
        "%$searchTerm%", 
        "%$searchTerm%",
        "%$searchTerm%",
        "%$searchTerm%"
    ]);
    $questions = $stmt->fetchAll();
} else {
    // Get all questions if no search term
    $stmt = $pdo->query('SELECT * FROM questions');
    $questions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions | Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS styles */
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
        
        .btn-danger {
            background: var(--danger);
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover td {
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        .option-item {
            padding: 4px 0;
            position: relative;
            padding-left: 20px;
        }
        
        .option-item:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary);
        }
        
        .correct-option {
            font-weight: 600;
            color: var(--success);
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
    <div class="max-w-7xl mx-auto animate-fade-in">
        <div class="card overflow-hidden">
            <!-- Header -->
            <div class="header-gradient px-6 py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-question-circle mr-2"></i> Manage Questions
                    </h2>
                    <div class="mt-3 md:mt-0">
                        <a href="add_question.php" class="inline-flex items-center px-4 py-2 bg-white text-primary rounded-lg font-medium hover:bg-gray-50 transition">
                            <i class="fas fa-plus-circle mr-2"></i> Add New Question
                        </a>
                    </div>
                </div>
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
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-4 p-3 bg-green-50 text-green-600 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Box - Now a form that submits to the same page -->
                <form method="GET" action="manage_questions.php" class="mb-6 flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative flex-grow">
                        <input type="text" name="search" placeholder="Search questions..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="manage_questions.php" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
                
                <!-- Questions Table -->
                <div class="table-container">
                    <table class="table">
                        <head>
                            <tr>
                                <th class="text-left">ID</th>
                                <th class="text-left">Exam ID</th>
                                <th class="text-left">Question</th>
                                <th class="text-left">Options</th>
                                <th class="text-left">Correct</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </head>
                        <body>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-question-circle text-4xl mb-2"></i>
                                        No questions found <?php echo !empty($searchTerm) ? 'matching your search' : ''; ?>
                                        <?php if (!empty($searchTerm)): ?>
                                            <a href="manage_questions.php" class="inline-block mt-4 text-primary hover:underline">
                                                <i class="fas fa-arrow-left mr-2"></i> Show all questions
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $row): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['exam_id']; ?></td>
                                    <td class="font-medium"><?php echo htmlspecialchars($row['question_text']); ?></td>
                                    <td>
                                        <div class="option-item <?php echo $row['correct_option'] == 1 ? 'correct-option' : ''; ?>">
                                            <?php echo htmlspecialchars($row['option1']); ?>
                                        </div>
                                        <div class="option-item <?php echo $row['correct_option'] == 2 ? 'correct-option' : ''; ?>">
                                            <?php echo htmlspecialchars($row['option2']); ?>
                                        </div>
                                        <div class="option-item <?php echo $row['correct_option'] == 3 ? 'correct-option' : ''; ?>">
                                            <?php echo htmlspecialchars($row['option3']); ?>
                                        </div>
                                        <div class="option-item <?php echo $row['correct_option'] == 4 ? 'correct-option' : ''; ?>">
                                            <?php echo htmlspecialchars($row['option4']); ?>
                                        </div>
                                    </td>
                                    <td class="font-semibold text-success">
                                        Option <?php echo $row['correct_option']; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-2">
                                            <a href="edit_question.php?id=<?php echo $row['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-primary text-white rounded-md text-sm hover:bg-primary-dark transition">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <a href="delete_question.php?id=<?php echo $row['id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this question?');"
                                               class="inline-flex items-center px-3 py-1 bg-danger text-white rounded-md text-sm hover:bg-red-700 transition">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </body>
                    </table>
                </div>
                
                <!-- Back to Dashboard -->
                <div class="mt-6">
                    <a href="../admin/admin_dashboard.php" class="inline-flex items-center text-primary hover:underline">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>