<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";

// Database connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Soft delete user
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $stmt = $conn->prepare("UPDATE users SET deleted_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('User deactivated successfully!'); window.location='view-users.php';</script>";
    } else {
        echo "Error deactivating user!";
    }
} else {
    echo "Invalid request!";
}

$conn->close();
?>
