<?php
session_start();
require '../database/db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if the user ID is provided in the URL
if (!isset($_GET['id'])) {
    header('Location:../admin/manage_users.php');
    exit();
}

$user_id = $_GET['id'];

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header('Location: manage_users.php');
    exit();
}

// Fetch the user to ensure they exist
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header('Location: manage_users.php');
    exit();
}

// Delete the user from the database
try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);

    $_SESSION['success'] = "User deleted successfully.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

// Redirect back to the manage users page
header('Location: manage_users.php');
exit();
?>