<?php
// release_teacher.php
session_start();
require '../database/db.php';

// Холболт
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Багшийн ID шалгах
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Багшийн ID буруу байна";
    header("Location: department.php");
    exit();
}

$teacher_id = (int)$_GET['id'];

// Багшийг тэнхимээс чөлөөлөх
$stmt = $conn->prepare("UPDATE users SET department_id = NULL WHERE id = ?");
$stmt->bind_param("i", $teacher_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Багшийг тэнхимээс амжилттай чөлөөлөв";
} else {
    $_SESSION['error'] = "Алдаа гарлаа: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: department.php");
exit();
?>