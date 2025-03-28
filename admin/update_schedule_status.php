<?php
ob_start(); // Turn on output buffering
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

if (isset($_POST['schedule_id']) && isset($_POST['status'])) {
    $schedule_id = $_POST['schedule_id'];
    $status = $_POST['status'];

    if ($status != 'approved') {
        echo json_encode(["status" => "error", "message" => "Only pending schedules can be approved."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE schedules SET status = ? WHERE schedule_id = ?");
    $stmt->bind_param("si", $status, $schedule_id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Schedule status updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating schedule status."]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>
<?php ob_end_flush(); // At the end of file ?>
