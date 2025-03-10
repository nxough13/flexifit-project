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

// Check if inventory_id is provided
if (isset($_POST['inventory_id'])) {
    $inventory_id = $_POST['inventory_id'];

    // Update the active_status to 'active'
    $stmt = $conn->prepare("UPDATE equipment_inventory SET active_status = 'active' WHERE inventory_id = ?");
    $stmt->bind_param("i", $inventory_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Equipment re-enabled successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "No equipment ID provided.";
}
// neo
$conn->close();

// Redirect back to the equipment list
header("Location: view-equipments.php");
exit();
?>
