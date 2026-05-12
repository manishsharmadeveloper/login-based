<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkRole($role)
{
    if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
        header("Location: ../login/login.php");
        exit();
    }

    if ($_SESSION['role'] !== $role) {
        header("Location: ../login/login.php");
        exit();
    }
}
?>