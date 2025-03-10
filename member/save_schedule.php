<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

// Ensure POST values exist
if (!isset($_POST['schedule_date']) || !isset($_POST['equipment'])) {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$equipment = $_POST['equipment'];
$member_id = $_POST['member_id']; // Make sure this is passed from the frontend

foreach ($equipment as $item) {
    $inventory_id = $item["id"]; 
    $start_time = date("H:i:s", strtotime($item["start"]));
    $end_time = date("H:i:s", strtotime($item["end"]));

    // Insert the schedule into the database
    $query = "INSERT INTO schedules (member_id, inventory_id, schedule_date, start_time, end_time) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $member_id, $inventory_id, $schedule_date, $start_time, $end_time);
    
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Error saving schedule for equipment ID: " . $inventory_id]);
        exit;
    }
}
// neo
// If all insertions succeed
echo json_encode(["success" => true, "message" => "Schedule successfully saved."]);
?>
