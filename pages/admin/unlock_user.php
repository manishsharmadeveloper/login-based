<?php
session_start();

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

// Only admin allowed
if ($_SESSION['role'] != 'admin') {
    die("Access denied");
}

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $stmt = $conn->prepare("
        UPDATE users 
        SET is_locked = 0, failed_attempts = 0 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "User unlocked successfully.";
}
?>