<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
session_start();
// Ensure POST values exist
if (!isset($_POST['schedule_date']) || !isset($_POST['equipment']) || !isset($_POST['member_id'])) {
    echo json_encode(["conflict" => true, "message" => "Missing required data."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$equipment = $_POST['equipment'];
$member_id = $_POST['member_id'];
$response = ["conflict" => false];

foreach ($equipment as $item) {
    $inventory_id = $item["id"];
    $start_time = strtotime($item["start"]);
    $end_time = strtotime($item["end"]);

    // ✅ 1. Ensure End Time is After Start Time
    if ($end_time <= $start_time) {
        echo json_encode(["conflict" => true, "message" => "End time must be after start time for equipment ID: " . $inventory_id]);
        exit;
    }

    // ✅ 2. Enforce Minimum (5 mins) and Maximum (40 mins) Duration
    $duration = ($end_time - $start_time) / 60;
    if ($duration < 5 || $duration > 40) {
        echo json_encode(["conflict" => true, "message" => "Duration for equipment ID: " . $inventory_id . " must be between 5 and 40 minutes."]);
        exit;
    }

    // ✅ 3. Ensure End Time Doesn't Go Past Midnight
    if (date("Y-m-d", $start_time) !== date("Y-m-d", $end_time)) {
        echo json_encode(["conflict" => true, "message" => "End time cannot extend to the next day for equipment ID: " . $inventory_id]);
        exit;
    }

    // ✅ 4. Check for Conflicts in `equipment_usage` Table
    $conflict_query = "SELECT 1 FROM equipment_usage 
                       WHERE inventory_id = ? 
                       AND schedule_date = ? 
                       AND status != 'cancelled' 
                       AND (
                           (start_time < ? AND end_time > ?) OR  
                           (start_time < ? AND end_time > ?) OR  
                           (start_time >= ? AND end_time <= ?)   
                       )";
    
    $stmt = $conn->prepare($conflict_query);
    $stmt->bind_param("isssssss", $inventory_id, $schedule_date, 
                      date("H:i:s", $end_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $end_time));
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["conflict" => true, "message" => "Schedule conflict! Equipment ID: " . $inventory_id . " is already booked at this time."]);
        exit;
    }
    $stmt->close();
}

echo json_encode(["conflict" => false, "message" => "Schedule is valid."]);
?>
