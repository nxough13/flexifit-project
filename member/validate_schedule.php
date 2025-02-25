<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Ensure POST values exist
if (!isset($_POST['schedule_date']) || !isset($_POST['equipment'])) {
    echo json_encode(["conflict" => true, "message" => "Invalid request data."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$equipment = $_POST['equipment'];
$response = ["conflict" => false];

foreach ($equipment as $item) {
    $inventory_id = $item["id"]; // Use inventory_id (not equipment_id)
    $start_time = $item["start"];
    $end_time = $item["end"];

    // Fix: Improved time overlap check
    $query = "SELECT * FROM schedules 
              WHERE inventory_id = ? 
              AND schedule_date = ? 
              AND (
                  (start_time < ? AND end_time > ?) OR  -- Case 1: Existing start < new end AND existing end > new start
                  (start_time < ? AND end_time > ?) OR  -- Case 2: Existing start < new start AND existing end > new start
                  (start_time >= ? AND end_time <= ?)   -- Case 3: Existing schedule completely inside new time
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssss", $inventory_id, $schedule_date, $end_time, $start_time, $start_time, $start_time, $start_time, $end_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response["conflict"] = true;
        $response["message"] = "Conflict detected for equipment ID: " . $inventory_id;
        break;
    }
}

echo json_encode($response);
?>
