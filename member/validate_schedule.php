<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Ensure POST values exist
if (!isset($_POST['schedule_date'])) {
    echo json_encode(["conflict" => true, "message" => "Missing schedule date."]);
    exit;
}

if (!isset($_POST['equipment'])) {
    echo json_encode(["conflict" => true, "message" => "No equipment selected."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$equipment = $_POST['equipment'];
$member_id = $_POST['member_id'];  // Get member ID from POST request
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
    $duration = ($end_time - $start_time) / 60; // Convert to minutes
    if ($duration < 5 || $duration > 40) {
        echo json_encode(["conflict" => true, "message" => "Duration for equipment ID: " . $inventory_id . " must be between 5 and 40 minutes."]);
        exit;
    }

    // ✅ 3. Check for Schedule Conflicts (Same Member Using Another Equipment)
    $query = "SELECT * FROM schedules 
              WHERE member_id = ? 
              AND schedule_date = ? 
              AND (
                  (start_time < ? AND end_time > ?) OR  
                  (start_time < ? AND end_time > ?) OR  
                  (start_time >= ? AND end_time <= ?)   
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssss", $member_id, $schedule_date, 
                      date("H:i:s", $end_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $end_time));
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["conflict" => true, "message" => "Schedule conflict! Member is already using another equipment during this time."]);
        exit;
    }

    // ✅ 5. Check if Equipment is Already Booked
    $equipment_query = "SELECT * FROM schedules 
                        WHERE inventory_id = ? 
                        AND schedule_date = ? 
                        AND status != 'cancelled'
                        AND (
                            (start_time < ? AND end_time > ?) OR  
                            (start_time < ? AND end_time > ?) OR  
                            (start_time >= ? AND end_time <= ?)   
                        )";

    $stmt = $conn->prepare($equipment_query);
    $stmt->bind_param("isssssss", $inventory_id, $schedule_date, 
                      date("H:i:s", $end_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $start_time), 
                      date("H:i:s", $start_time), date("H:i:s", $end_time));
    $stmt->execute();
    $equipment_result = $stmt->get_result();

    if ($equipment_result->num_rows > 0) {
        echo json_encode(["conflict" => true, "message" => "Schedule conflict! Equipment ID: " . $inventory_id . " is already booked at this time."]);
        exit;
    }
}

echo json_encode(["conflict" => false, "message" => "Schedule is valid."]);
?>
