<?php
require '../database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['major_id'])) {
    echo json_encode([]);
    exit;
}

$majorId = (int)$_GET['major_id'];
$stmt = $pdo->prepare('SELECT id, name, year FROM classes WHERE major_id = ?');
$stmt->execute([$majorId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($classes);