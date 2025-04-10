<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Include the database connection
require '../database/db.php';

$teacher_id = $_SESSION['user_id']; // Get the logged-in teacher's ID

// Fetch exams created by the logged-in teacher
$stmt = $pdo->prepare('SELECT * FROM exams WHERE teacher_id = ?');
$stmt->execute([$teacher_id]);
$exams = $stmt->fetchAll();

// Fetch questions created by the logged-in teacher
$stmt = $pdo->prepare('SELECT eq.*, e.exam_name 
                       FROM questions eq 
                       JOIN exams e ON eq.exam_id = e.exam_id 
                       WHERE e.teacher_id = ?');
$stmt->execute([$teacher_id]);
$questions = $stmt->fetchAll();

// Fetch results with class information
$stmt = $pdo->prepare('SELECT er.*, e.exam_name, u.full_name AS student_name, 
                              c.name AS class_name, u.student_id
                       FROM exam_results er 
                       JOIN exams e ON er.exam_id = e.exam_id 
                       JOIN users u ON er.student_id = u.id 
                       LEFT JOIN classes c ON u.class_id = c.id
                       WHERE e.teacher_id = ?');
$stmt->execute([$teacher_id]);
$results = $stmt->fetchAll();

// Calculate statistics for analytics slides
$total_exams = count($exams);
$total_questions = count($questions);
$total_students = count(array_unique(array_column($results, 'student_id')));
$avg_score = $total_students > 0 ? round(array_sum(array_column($results, 'score')) / count($results), 1) : 0;

// Get exam performance data for charts
$exam_performance = [];
foreach ($exams as $exam) {
    $exam_id = $exam['exam_id'];
    $stmt = $pdo->prepare('SELECT AVG(score) as avg_score, COUNT(*) as count 
                           FROM exam_results 
                           WHERE exam_id = ?');
    $stmt->execute([$exam_id]);
    $performance = $stmt->fetch();
    $exam_performance[] = [
        'exam_name' => $exam['exam_name'],
        'avg_score' => $performance['avg_score'] ? round($performance['avg_score'], 1) : 0,
        'student_count' => $performance['count']
    ];
}

// Get class-wise performance
$class_performance = [];
if (count($results) > 0) {
    $stmt = $pdo->prepare('SELECT c.name AS class_name, AVG(er.score) as avg_score, COUNT(DISTINCT er.student_id) as student_count
                           FROM exam_results er
                           JOIN exams e ON er.exam_id = e.exam_id
                           JOIN users u ON er.student_id = u.id
                           LEFT JOIN classes c ON u.class_id = c.id
                           WHERE e.teacher_id = ?
                           GROUP BY c.id');
    $stmt->execute([$teacher_id]);
    $class_performance = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>Багшийн хянах самбар</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--gray-color);
            font-size: 1.1rem;
        }

        /* Analytics Slides */
        .analytics-slider {
            margin-bottom: 3rem;
            padding: 1rem;
        }

        .swiper {
            width: 100%;
            height: 100%;
            padding: 1rem 0;
        }

        .analytics-slide {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card {
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--gray-color);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        /* Tabs */
        .tab-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tab-btn {
            padding: 0.8rem 1.5rem;
            background-color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: var(--gray-color);
        }

        .tab-btn.active {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }

        .tab-btn:hover:not(.active) {
            background-color: #f0f0f0;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title {
            font-size: 1.4rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Search */
        .search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 0.7rem 1rem 0.7rem 2.5rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1rem 0;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8faff;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-primary {
            background-color: #e3f2fd;
            color: var(--primary-color);
        }

        .badge-success {
            background-color: #e6f7ee;
            color: #28a745;
        }

        .badge-warning {
            background-color: #fff4e5;
            color: var(--warning-color);
        }

        .badge-danger {
            background-color: #fde8ef;
            color: var(--danger-color);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 0.6rem 1rem;
            border-radius: var(--border-radius);
            background-color: white;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #e0e0e0;
            transition: var(--transition);
        }

        .page-link:hover {
            background-color: #f0f0f0;
        }

        .page-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Buttons */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background-color: white;
            color: var(--primary-color);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            text-decoration: none;
            margin-top: 1rem;
        }

        .back-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }

        /* Swiper navigation */
        .swiper-button-prev, .swiper-button-next {
            color: var(--primary-color);
            background: rgba(255, 255, 255, 0.8);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .swiper-button-prev::after, .swiper-button-next::after {
            font-size: 1.2rem;
        }

        .swiper-pagination-bullet-active {
            background: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .analytics-slide {
                padding: 1.5rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            th, td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>Багшийн хянах самбар</h1>
            <p>Шалгалтын мэдээлэл ба дүн шинжилгээ</p>
        </div>

        <!-- Analytics Slides -->
        <div class="analytics-slider">
            <div class="swiper">
                <div class="swiper-wrapper">
                    <!-- Slide 1: Overview -->
                    <div class="swiper-slide analytics-slide">
                        <div class="stat-card">
                            <div class="stat-value"><?= $total_exams ?></div>
                            <div class="stat-label">Нийт шалгалт</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="examsChart"></canvas>
                        </div>
                    </div>

                    <!-- Slide 2: Questions -->
                    <div class="swiper-slide analytics-slide">
                        <div class="stat-card">
                            <div class="stat-value"><?= $total_questions ?></div>
                            <div class="stat-label">Нийт асуулт</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="questionsChart"></canvas>
                        </div>
                    </div>

                    <!-- Slide 3: Students -->
                    <div class="swiper-slide analytics-slide">
                        <div class="stat-card">
                            <div class="stat-value"><?= $total_students ?></div>
                            <div class="stat-label">Нийт оюутан</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="studentsChart"></canvas>
                        </div>
                    </div>

                    <!-- Slide 4: Performance -->
                    <div class="swiper-slide analytics-slide">
                        <div class="stat-card">
                            <div class="stat-value"><?= $avg_score ?>%</div>
                            <div class="stat-label">Дундаж дүн</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-container">
            <button class="tab-btn active" onclick="openTab('exams')">Шалгалтууд</button>
            <button class="tab-btn" onclick="openTab('questions')">Асуултууд</button>
            <button class="tab-btn" onclick="openTab('results')">Үр дүн</button>
        </div>

        <!-- Exams Tab -->
        <div id="exams" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Шалгалтуудын жагсаалт</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="examSearch" placeholder="Шалгалт хайх..." onkeyup="searchTable('examSearch', 'examTable')">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="examTable">
                        <thead>
                            <tr>
                                <th>Дугаар</th>
                                <th>Шалгалтын нэр</th>
                                <th>Үүсгэсэн огноо</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td><?= $exam['exam_id']; ?></td>
                                    <td><?= htmlspecialchars($exam['exam_name']); ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($exam['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="examTable-pagination" class="pagination"></div>
            </div>
        </div>

        <!-- Questions Tab -->
        <div id="questions" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Асуултуудын жагсаалт</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="questionSearch" placeholder="Асуулт хайх..." onkeyup="searchTable('questionSearch', 'questionTable')">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="questionTable">
                        <thead>
                            <tr>
                                <th>Дугаар</th>
                                <th>Шалгалт</th>
                                <th>Асуулт</th>
                                <th>Зөв хариулт</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question): ?>
                                <tr>
                                    <td><?= $question['id']; ?></td>
                                    <td><?= htmlspecialchars($question['exam_name']); ?></td>
                                    <td><?= htmlspecialchars(substr($question['question_text'], 0, 50)) . (strlen($question['question_text']) > 50 ? '...' : ''); ?></td>
                                    <td><span class="badge badge-success"><?= $question['correct_option']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="questionTable-pagination" class="pagination"></div>
            </div>
        </div>

        <!-- Results Tab -->
        <div id="results" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Оюутнуудын үр дүн</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="resultSearch" placeholder="Оюутан хайх..." onkeyup="searchTable('resultSearch', 'resultTable')">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="resultTable">
                        <thead>
                            <tr>
                                <th>Оюутны нэр</th>
                                <th>Оюутны ID</th>
                                <th>Анги</th>
                                <th>Шалгалт</th>
                                <th>Оноо</th>
                                <th>Огноо</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars($result['student_name']); ?></td>
                                    <td><?= htmlspecialchars($result['student_id']); ?></td>
                                    <td>
                                        <?php if (!empty($result['class_name'])): ?>
                                            <span class="badge badge-primary"><?= htmlspecialchars($result['class_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Ангилалгүй</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($result['exam_name']); ?></td>
                                    <td>
                                        <?php 
                                        $score = $result['score'];
                                        if ($score >= 90) {
                                            echo '<span class="badge badge-success">'.$score.'</span>';
                                        } elseif ($score >= 60) {
                                            echo '<span class="badge badge-warning">'.$score.'</span>';
                                        } else {
                                            echo '<span class="badge badge-danger">'.$score.'</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($result['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="resultTable-pagination" class="pagination"></div>
            </div>
        </div>

        <a href="teacher_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Буцах
        </a>
    </div>

    <!-- Add Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper
        const swiper = new Swiper('.swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1200: {
                    slidesPerView: 3,
                }
            }
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Exams Chart
            const examsCtx = document.getElementById('examsChart').getContext('2d');
            new Chart(examsCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($exam_performance, 'exam_name')) ?>,
                    datasets: [{
                        label: 'Дундаж дүн',
                        data: <?= json_encode(array_column($exam_performance, 'avg_score')) ?>,
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Questions Chart (pie chart showing question distribution)
            const questionsCtx = document.getElementById('questionsChart').getContext('2d');
            new Chart(questionsCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($exam_performance, 'exam_name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($exam_performance, 'student_count')) ?>,
                        backgroundColor: [
                            'rgba(67, 97, 238, 0.7)',
                            'rgba(72, 149, 239, 0.7)',
                            'rgba(76, 201, 240, 0.7)',
                            'rgba(248, 150, 30, 0.7)',
                            'rgba(247, 37, 133, 0.7)'
                        ]
                    }]
                }
            });

            // Students Chart (class distribution)
            const studentsCtx = document.getElementById('studentsChart').getContext('2d');
            new Chart(studentsCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($class_performance, 'class_name') ? array_column($class_performance, 'class_name') : ['Ангилалгүй']) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($class_performance, 'student_count') ? array_column($class_performance, 'student_count') : [0]) ?>,
                        backgroundColor: [
                            'rgba(67, 97, 238, 0.7)',
                            'rgba(72, 149, 239, 0.7)',
                            'rgba(76, 201, 240, 0.7)',
                            'rgba(248, 150, 30, 0.7)'
                        ]
                    }]
                }
            });

            // Performance Chart (class-wise performance)
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'radar',
                data: {
                    labels: <?= json_encode(array_column($class_performance, 'class_name') ? array_column($class_performance, 'class_name') : ['Ангилалгүй']) ?>,
                    datasets: [{
                        label: 'Дундаж дүн',
                        data: <?= json_encode(array_column($class_performance, 'avg_score') ? array_column($class_performance, 'avg_score') : [0]) ?>,
                        backgroundColor: 'rgba(76, 201, 240, 0.2)',
                        borderColor: 'rgba(76, 201, 240, 1)',
                        pointBackgroundColor: 'rgba(76, 201, 240, 1)'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });

            // Initialize pagination
            paginateTable("examTable", 5);
            paginateTable("questionTable", 5);
            paginateTable("resultTable", 5);
        });

        // Tab functionality
        function openTab(tabName) {
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Search functionality
        function searchTable(inputId, tableId) {
            let input = document.getElementById(inputId).value.toLowerCase();
            let rows = document.getElementById(tableId).getElementsByTagName("tr");
            
            for (let i = 1; i < rows.length; i++) {
                let cells = rows[i].getElementsByTagName("td");
                let found = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(input)) {
                        found = true;
                        break;
                    }
                }
                rows[i].style.display = found ? "" : "none";
            }
        }

        // Pagination functionality
        function paginateTable(tableId, rowsPerPage) {
            let table = document.getElementById(tableId);
            let rows = table.getElementsByTagName("tr");
            let totalRows = rows.length - 1;
            let pageCount = Math.ceil(totalRows / rowsPerPage);
            let paginationDiv = document.getElementById(tableId + "-pagination");

            paginationDiv.innerHTML = "";
            
            if (pageCount <= 1) return;
            
            for (let i = 0; i < pageCount; i++) {
                let pageLink = document.createElement("a");
                pageLink.href = "#";
                pageLink.className = "page-link";
                pageLink.innerText = i + 1;
                pageLink.onclick = function() {
                    showPage(tableId, i, rowsPerPage);
                    setActivePage(this);
                    return false;
                };
                paginationDiv.appendChild(pageLink);
            }
            
            // Set first page as active initially
            if (paginationDiv.firstChild) {
                paginationDiv.firstChild.classList.add("active");
            }
            showPage(tableId, 0, rowsPerPage);
        }

        function showPage(tableId, pageIndex, rowsPerPage) {
            let table = document.getElementById(tableId);
            let rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                if (i > pageIndex * rowsPerPage && i <= (pageIndex + 1) * rowsPerPage) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function setActivePage(activeLink) {
            let links = activeLink.parentElement.getElementsByClassName("page-link");
            for (let link of links) {
                link.classList.remove("active");
            }
            activeLink.classList.add("active");
        }
    </script>
</body>
</html>