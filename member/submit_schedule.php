<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not authenticated."]));
}

$user_id = $_SESSION['user_id'];

// Fetch member_id based on user_id
$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

if (!$member_id) {
    die(json_encode(["status" => "error", "message" => "Member ID not found."]));
}

// Get data from AJAX request
$data = json_decode(file_get_contents("php://input"), true);

$schedule_date = $data['schedule_date'];
$equipment_list = $data['equipment'];
$trainer_id = $data['trainer_id'] ?? null;

foreach ($equipment_list as $equipment) {
    $inventory_id = $equipment['id'];
    $start_time = $equipment['start'];
    $end_time = $equipment['end'];

    // Insert into schedules table for equipment
    $insert_query = "INSERT INTO schedules (member_id, inventory_id, date, start_time, end_time, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisss", $member_id, $inventory_id, $schedule_date, $start_time, $end_time);
    
    if (!$stmt->execute()) {
        die(json_encode(["status" => "error", "message" => "Error scheduling equipment."]));
    }
    
    $schedule_id = $stmt->insert_id;
    $stmt->close();

    // Update equipment status to 'in_use'
    $update_query = "UPDATE equipment_inventory SET status = 'in_use' WHERE inventory_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $stmt->close();

    // If a trainer is selected, insert into schedule_trainer table
    if ($trainer_id) {
        // Check if the member has a free training session available
        $check_sessions_query = "SELECT free_training_session FROM members WHERE member_id = ?";
        $stmt = $conn->prepare($check_sessions_query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $stmt->bind_result($free_sessions);
        $stmt->fetch();
        $stmt->close();

        if ($free_sessions > 0) {
            // Insert trainer schedule with 'approved' status
            $insert_trainer_schedule_query = "INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) 
                                              VALUES (?, ?, 'approved')";
            $stmt = $conn->prepare($insert_trainer_schedule_query);
            $stmt->bind_param("ii", $schedule_id, $trainer_id);
            $stmt->execute();
            $stmt->close();

            // Deduct one free session from the member
            $update_sessions_query = "UPDATE members SET free_training_session = free_training_session - 1 WHERE member_id = ?";
            $stmt = $conn->prepare($update_sessions_query);
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // If no free sessions are available, set the trainer schedule status as 'pending'
            $update_trainer_status_query = "UPDATE schedule_trainer SET trainer_status = 'pending' WHERE schedule_id = ?";
            $stmt = $conn->prepare($update_trainer_status_query);
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

echo json_encode(["status" => "success", "message" => "Schedule saved successfully."]);
?>
