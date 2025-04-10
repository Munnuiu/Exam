<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Pagination settings
$results_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $results_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = 'SELECT er.*, e.exam_name 
          FROM exam_results er
          LEFT JOIN exams e ON er.exam_id = e.exam_id
          WHERE er.student_id = ?';

$params = [$student_id];

// Add search condition if provided
if (!empty($search)) {
    $query .= ' AND (e.exam_name LIKE ? OR er.score LIKE ?)';
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Count total results for pagination
$count_query = "SELECT COUNT(*) FROM ($query) AS total";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_results = $stmt->fetchColumn();

// Add sorting (newest first) and pagination
$query .= ' ORDER BY er.submitted_at DESC LIMIT ? OFFSET ?';
$params[] = $results_per_page;
$params[] = $offset;

// Fetch paginated results
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Calculate total pages
$total_pages = ceil($total_results / $results_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Previous styles remain the same */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }

        h2 {
            color: #34495e;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 2px;
        }

        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .results-table th {
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .results-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .results-table tr:last-child td {
            border-bottom: none;
        }

        .results-table tr:hover {
            background-color: #f8f9fa;
        }

        .results-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Score Colors */
        .score-excellent {
            color: #2ecc71;
            font-weight: bold;
        }

        .score-good {
            color: #3498db;
            font-weight: bold;
        }

        .score-average {
            color: #f39c12;
            font-weight: bold;
        }

        .score-poor {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Back Button */
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            text-decoration: none;
            text-align: center;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .back-btn i {
            margin-right: 8px;
        }

        /* No Results Message */
        .no-results {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .results-table {
                display: block;
                overflow-x: auto;
            }
            
            h2 {
                font-size: 1.8rem;
            }
        }
        /* Search and Pagination Styles */
        .search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 90%;
            padding: 12px 20px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-box input:focus {
            border-color: #3498db;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 8px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #3498db;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination .disabled {
            color: #ddd;
            pointer-events: none;
        }
        
        .latest-results {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .latest-results h3 {
            margin-top: 0;
            color: #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .latest-results h3 i {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-chart-bar"></i> Шалгалтын үр дүн</h2>
        
        <!-- Search Box -->
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" placeholder="Шалгалтын нэр эсвэл оноогоор хайх..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="results-count">
                Нийт үр дүн: <?php echo $total_results; ?>
            </div>
        </div>
        
        <!-- Latest Results Section -->
        <?php if (!empty($results) && $current_page == 1): ?>
        <div class="latest-results">
            <h3><i class="fas fa-bolt"></i> Сүүлийн шалгалтын дүн</h3>
            <div class="score-display <?php 
                if ($results[0]['score'] >= 90) echo 'score-excellent';
                elseif ($results[0]['score'] >= 70) echo 'score-good';
                elseif ($results[0]['score'] >= 50) echo 'score-average';
                else echo 'score-poor';
            ?>">
                <?php echo $results[0]['score']; ?>%
                <span style="font-size: 0.9em; color: #7f8c8d;">
                    (<?php echo htmlspecialchars($results[0]['exam_name'] ?? 'Exam #'.$results[0]['exam_id']); ?>)
                </span>
            </div>
            <p>Шалгалт өгсөн огноо: <?php echo date('Y-m-d H:i', strtotime($results[0]['submitted_at'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($results)): ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Шалгалтын нэр</th>
                        <th>Оноо</th>
                        <th>Шалгалт өгсөн огноо</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): 
                        $scoreClass = '';
                        if ($result['score'] >= 90) {
                            $scoreClass = 'score-excellent';
                        } elseif ($result['score'] >= 70) {
                            $scoreClass = 'score-good';
                        } elseif ($result['score'] >= 50) {
                            $scoreClass = 'score-average';
                        } else {
                            $scoreClass = 'score-poor';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['exam_name'] ?? 'Exam #'.$result['exam_id']); ?></td>
                        <td class="<?php echo $scoreClass; ?>">
                            <?php echo $result['score']; ?>%
                            <?php if ($result['score'] >= 90): ?>
                                <i class="fas fa-star"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($result['submitted_at'])); ?></td>
                        <td>
                            <?php if ($result['score'] >= 50): ?>
                                <span style="color: #2ecc71;"><i class="fas fa-check-circle"></i> Тэнцсэн</span>
                            <?php else: ?>
                                <span style="color: #e74c3c;"><i class="fas fa-times-circle"></i> Амжилтгүй</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Өмнөх
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-left"></i> Өмнөх</span>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'">1</a>';
                    if ($start_page > 2) echo '<span>...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $current_page) {
                        echo '<span class="active">'.$i.'</span>';
                    } else {
                        echo '<a href="?page='.$i.(!empty($search) ? '&search='.urlencode($search) : '').'">'.$i.'</a>';
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span>...</span>';
                    echo '<a href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').'">'.$total_pages.'</a>';
                }
                ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                        Дараах <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled">Дараах <i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>Шалгалтын дүн олдсонгүй. <?php echo !empty($search) ? 'Хайлтын үр дүн олдсонгүй.' : 'Эндээс үр дүнгээ харахын тулд зарим шалгалтыг бөглөнө үү.'; ?></p>
                <?php if (!empty($search)): ?>
                    <a href="?" class="back-btn" style="margin-top: 10px; display: inline-block;">
                        <i class="fas fa-times"></i> Хайлтыг цуцлах
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="student_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Хяналтын самбар руу буцах
        </a>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                window.location.href = '?search=' + encodeURIComponent(searchTerm);
            }
        });
        
        // Highlight latest result
        document.addEventListener('DOMContentLoaded', function() {
            const firstRow = document.querySelector('.results-table tbody tr');
            if (firstRow) {
                firstRow.style.backgroundColor = '#f0f7ff';
                firstRow.style.borderLeft = '3px solid #3498db';
            }
        });
    </script>
</body>
</html>