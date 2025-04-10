<?php
// delete_question.php

session_start();
require_once '../database/db.php'; // Ensure this path is correct

// Хэрэглэгч админ эсвэл багш эсэхийг шалгах
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Use prepared statements to prevent SQL injection
    $sql = "DELETE FROM questions WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header("Location: manage_questions.php");
        exit();
    } else {
        echo "Error deleting record.";
    }
}
?>