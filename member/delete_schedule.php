<?php
ob_start(); // Turn on output buffering
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "", "flexifit_db");

$schedule_id = $_POST['schedule_id'];

// Soft delete (set status to cancelled)
$stmt = $conn->prepare("UPDATE schedules SET status = 'cancelled' WHERE schedule_id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();

echo json_encode(["status" => "success", "message" => "Schedule deleted."]);
?>
<?php ob_end_flush(); // At the end of file ?>
