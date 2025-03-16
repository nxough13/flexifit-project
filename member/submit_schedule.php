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

    // ✅ Trainer Availability Check
    if ($trainer_id) {
        $trainer_check_query = "SELECT * FROM schedule_trainer st
                                JOIN schedules s ON st.schedule_id = s.schedule_id
                                WHERE st.trainer_id = ? 
                                AND s.date = ?
                                AND (
                                    (s.start_time < ? AND s.end_time > ?) OR 
                                    (s.start_time < ? AND s.end_time > ?) OR 
                                    (s.start_time >= ? AND s.end_time <= ?)
                                )";
        $stmt = $conn->prepare($trainer_check_query);
        $stmt->bind_param("isssssss", $trainer_id, $schedule_date, 
                          $end_time, $start_time, 
                          $start_time, $start_time, 
                          $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            die(json_encode(["status" => "error", "message" => "Trainer is already booked at this time."]));
        }
        $stmt->close();
    }

    // ✅ Insert into schedules table for equipment
    $insert_query = "INSERT INTO schedules (member_id, inventory_id, date, start_time, end_time, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisss", $member_id, $inventory_id, $schedule_date, $start_time, $end_time);
    
    if (!$stmt->execute()) {
        die(json_encode(["status" => "error", "message" => "Error scheduling equipment."]));
    }
    
    $schedule_id = $stmt->insert_id;
    $stmt->close();

    // ✅ Update Equipment Status Properly (Fix Conflict)
    $update_query = "UPDATE equipment_inventory 
                     SET status = CASE 
                          WHEN NOT EXISTS (
                              SELECT 1 FROM schedules 
                              WHERE inventory_id = ? 
                              AND status NOT IN ('cancelled', 'completed')
                          ) 
                          THEN 'available'
                          ELSE 'in_use' 
                     END 
                     WHERE inventory_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $inventory_id, $inventory_id);
    $stmt->execute();
    $stmt->close();

    // ✅ If a trainer is selected, insert into schedule_trainer table
    if ($trainer_id) {
        $insert_trainer_schedule_query = "INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) 
                                          VALUES (?, ?, 'approved')";
        $stmt = $conn->prepare($insert_trainer_schedule_query);
        $stmt->bind_param("ii", $schedule_id, $trainer_id);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode(["status" => "success", "message" => "Schedule saved successfully."]);
?>
