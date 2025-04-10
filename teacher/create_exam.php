<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

require '../database/db.php';
$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new exam
    if (isset($_POST['add_exam'])) {
        $exam_name = $_POST['exam_name'];
        $description = $_POST['description'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $pdo->prepare('INSERT INTO exams (exam_name, description, start_time, end_time, teacher_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$exam_name, $description, $start_time, $end_time, $teacher_id]);
        
        $_SESSION['success_message'] = 'Exam created successfully!';
    }
    // Update exam
    elseif (isset($_POST['update_exam'])) {
        $exam_id = $_POST['exam_id'];
        $exam_name = $_POST['exam_name'];
        $description = $_POST['description'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        // Verify teacher owns the exam
        $stmt = $pdo->prepare('UPDATE exams SET exam_name = ?, description = ?, start_time = ?, end_time = ? 
                              WHERE exam_id = ? AND teacher_id = ?');
        $stmt->execute([$exam_name, $description, $start_time, $end_time, $exam_id, $teacher_id]);
        
        $_SESSION['success_message'] = 'Exam updated successfully!';
    }
    
    header('Location: create_exam.php');
    exit();
}

// Handle exam deletion
if (isset($_GET['delete_id'])) {
    $exam_id = $_GET['delete_id'];
    
    // Verify teacher owns the exam
    $stmt = $pdo->prepare('DELETE FROM exams WHERE exam_id = ? AND teacher_id = ?');
    $stmt->execute([$exam_id, $teacher_id]);
    
    $_SESSION['success_message'] = 'Exam deleted successfully!';
    header('Location: create_exam.php');
    exit();
}

// Display success message if exists
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Pagination setup
$rows_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Search functionality
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = 'SELECT * FROM exams WHERE teacher_id = ?';
$params = [$teacher_id];

if (!empty($search_term)) {
    $query .= ' AND (exam_name LIKE ? OR description LIKE ?)';
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Count total exams for pagination
$count_query = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
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
$query .= " ORDER BY start_time DESC LIMIT $offset, $rows_per_page";

// Fetch exams for current page
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams | Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            gap: 0.5rem;
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
            background-color: var(--secondary);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background-color: #3730a3;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #d1145a;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }
        
        .btn-success:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--gray);
        }
        
        .btn-outline:hover {
            background-color: var(--light);
        }
        
        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50rem;
        }
        
        .badge-success {
            color: #fff;
            background-color: var(--success);
        }
        
        .badge-warning {
            color: #fff;
            background-color: var(--warning);
        }
        
        .badge-danger {
            color: #fff;
            background-color: var(--danger);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.6rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--dark);
            background-color: var(--white);
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .form-control:focus {
            color: var(--dark);
            background-color: var(--white);
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-50px);
            transition: var(--transition);
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-item {
            list-style: none;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--border-radius);
            background-color: var(--white);
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid #dee2e6;
        }
        
        .page-link:hover {
            background-color: var(--light);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }
        
        .page-item.disabled .page-link {
            color: var(--gray);
            pointer-events: none;
            background-color: var(--white);
            border-color: #dee2e6;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .empty-state p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--white);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .action-btn.edit {
            background-color: var(--info);
        }
        
        .action-btn.delete {
            background-color: var(--danger);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge i {
            font-size: 0.6rem;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-upcoming {
            background-color: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }
        
        .status-ended {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .action-btns {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-clipboard-list"></i> Manage Exams</h1>
                <button class="btn btn-primary" id="openAddModal">
                    <i class="fas fa-plus"></i> Add New Exam
                </button>
            </div>
            
            <!-- Search Box -->
            <div class="search-container">
                <form method="get" action="create_exam.php" class="search-form" style="flex: 1; display: flex; gap: 0.5rem;">
                    <input type="text" name="search" class="search-input" placeholder="Search exams..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search_term)): ?>
                        <a href="create_exam.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Exams Table -->
            <?php if (empty($exams)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>No exams found</h3>
                    <p>You haven't created any exams yet. Click the button above to add your first exam.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Exam Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): 
                            $now = new DateTime();
                            $start_time = new DateTime($exam['start_time']);
                            $end_time = new DateTime($exam['end_time']);
                            
                            if ($now < $start_time) {
                                $status = 'upcoming';
                                $status_text = 'Upcoming';
                            } elseif ($now > $end_time) {
                                $status = 'ended';
                                $status_text = 'Ended';
                            } else {
                                $status = 'active';
                                $status_text = 'Active';
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['description'] ?: 'No description'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <i class="fas fa-circle"></i> <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($exam['start_time'])); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($exam['end_time'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <div class="action-btn edit" onclick="openEditModal(
                                            '<?php echo $exam['exam_id']; ?>',
                                            '<?php echo htmlspecialchars($exam['exam_name'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($exam['description'], ENT_QUOTES); ?>',
                                            '<?php echo date('Y-m-d\TH:i', strtotime($exam['start_time'])); ?>',
                                            '<?php echo date('Y-m-d\TH:i', strtotime($exam['end_time'])); ?>'
                                        )">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <a href="create_exam.php?delete_id=<?php echo $exam['exam_id']; ?>" 
                                           class="action-btn delete"
                                           onclick="return confirm('Are you sure you want to delete this exam?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="create_exam.php?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="create_exam.php?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $current_page - 2);
                    $end = min($total_pages, $current_page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="create_exam.php?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="create_exam.php?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="create_exam.php?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="teacher_dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Add Exam Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Exam</h3>
                <button class="close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="exam_name" class="form-label">Exam Name</label>
                        <input type="text" id="exam_name" name="exam_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="form-label">Description (optional)</label>
                        <textarea id="description" name="description" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="datetime-local" id="start_time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="datetime-local" id="end_time" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" name="add_exam">
                        <i class="fas fa-save"></i> Save Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Exam Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Exam</h3>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_exam_id" name="exam_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_exam_name" class="form-label">Exam Name</label>
                        <input type="text" id="edit_exam_name" name="exam_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description" class="form-label">Description (optional)</label>
                        <textarea id="edit_description" name="description" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_time" class="form-label">Start Time</label>
                        <input type="datetime-local" id="edit_start_time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_time" class="form-label">End Time</label>
                        <input type="datetime-local" id="edit_end_time" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" name="update_exam">
                        <i class="fas fa-save"></i> Update Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        function openEditModal(examId, examName, description, startTime, endTime) {
            document.getElementById('edit_exam_id').value = examId;
            document.getElementById('edit_exam_name').value = examName;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Event listeners
        document.getElementById('openAddModal').addEventListener('click', openAddModal);
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target === document.getElementById('editModal')) {
                closeEditModal();
            }
        });
        
        // Set current datetime as default for new exams
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            
            document.getElementById('start_time').min = localISOTime;
            document.getElementById('end_time').min = localISOTime;
        });
    </script>
</body>
</html>