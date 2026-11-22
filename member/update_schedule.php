<?php
ob_start(); // Turn on output buffering
header("Content-Type: application/json");
session_start();
require_once '../includes/config.php';

$schedule_id = $_POST['schedule_id'];
$date = $_POST['edit_date'];
$start = $_POST['edit_start_time'];
$end = $_POST['edit_end_time'];
$equipment_id = $_POST['edit_equipment'];
$trainer_id = $_POST['edit_trainer'] ?? null;

// Update the main schedule
$update = $conn->prepare("UPDATE schedules SET date = ?, start_time = ?, end_time = ?, inventory_id = ? WHERE schedule_id = ?");
$update->bind_param("sssii", $date, $start, $end, $equipment_id, $schedule_id);
$update->execute();

// Update or insert trainer
$conn->query("DELETE FROM schedule_trainer WHERE schedule_id = $schedule_id");
if ($trainer_id) {
    $insert_trainer = $conn->prepare("INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) VALUES (?, ?, 'approved')");
    $insert_trainer->bind_param("ii", $schedule_id, $trainer_id);
    $insert_trainer->execute();

    // Decrement free_training_session (replaces trigger)
    $member_query = $conn->prepare("SELECT member_id FROM schedules WHERE schedule_id = ?");
    $member_query->bind_param("i", $schedule_id);
    $member_query->execute();
    $member_result = $member_query->get_result()->fetch_assoc();
    $member_query->close();
    if ($member_result) {
        $dec = $conn->prepare("UPDATE members SET free_training_session = free_training_session - 1 WHERE member_id = ? AND free_training_session > 0");
        $dec->bind_param("i", $member_result['member_id']);
        $dec->execute();
        $dec->close();
    }
}

echo json_encode(["status" => "success", "message" => "Schedule updated successfully."]);
?>
<?php ob_end_flush(); // At the end of file ?>