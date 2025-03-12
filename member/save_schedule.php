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
    $start_time = date("Y-m-d H:i:s", strtotime($item["start"]));  // Ensure full date-time format
    $end_time = date("Y-m-d H:i:s", strtotime($item["end"]));

    // Insert into equipment_usage table
    // Insert the schedule into the equipment_usage table
$query = "INSERT INTO equipment_usage (member_id, inventory_id, start_time, end_time) 
VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiss", $member_id, $inventory_id, $start_time, $end_time);

if ($stmt->execute()) {
// Update the equipment's status to 'in_use' in equipment_inventory table
$update_query = "UPDATE equipment_inventory SET status = 'in_use' WHERE inventory_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $inventory_id);
$update_stmt->execute();
$update_stmt->close();

echo json_encode(["success" => true, "message" => "Schedule saved successfully!"]);
} else {
echo json_encode(["success" => false, "message" => "Failed to save the schedule."]);
}
$stmt->close();

}

// neo
// If all insertions succeed
echo json_encode(["success" => true, "message" => "Schedule successfully saved."]);
?>