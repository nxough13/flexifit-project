<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

session_start();

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

// Ensure POST values exist
if (!isset($_POST['schedule_date']) || !isset($_POST['equipment']) || !isset($_POST['member_id'])) {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$equipment = $_POST['equipment'];
$member_id = $_POST['member_id'];
$all_successful = true;

foreach ($equipment as $item) {
    $inventory_id = $item["id"];
    $start_time = date("H:i:s", strtotime($item["start"]));
    $end_time = date("H:i:s", strtotime($item["end"]));

    // ✅ Insert schedule into `equipment_usage` table
    $query = "INSERT INTO equipment_usage (member_id, inventory_id, schedule_date, start_time, end_time, status) 
              VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $member_id, $inventory_id, $schedule_date, $start_time, $end_time);

    if (!$stmt->execute()) {
        $all_successful = false;
    }
    $stmt->close();
}

// ✅ If all insertions succeed, return success message
if ($all_successful) {
    echo json_encode(["success" => true, "message" => "Schedule successfully saved."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save some schedules."]);
}
?>
