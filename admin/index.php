<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['user_type'] == 'guest') {
    // Guests cannot access members or admin areas
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['user_type'] == 'member' && basename($_SERVER['PHP_SELF']) == 'admin/index.php') {
    // Members cannot access the admin area
    header("Location: ../member/index.php");
    exit();
}
?>
