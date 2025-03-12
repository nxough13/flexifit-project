<?php
header('Content-Type: application/json');
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
session_start();
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Get JSON data from request
$data = json_decode(file_get_contents("php://input"), true);

// Debugging: Check if data is received
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received."]);
    exit();
}

// Extract data
$schedule_date = $data['schedule_date'] ?? null;
$trainer_id = $data['trainer_id'] ?? null;
$equipment = $data['equipment'] ?? [];

if (!$schedule_date || empty($equipment)) {
    echo json_encode(["status" => "error", "message" => "Schedule date and equipment are required."]);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null; // Ensure user is logged in
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User is not logged in."]);
    exit();
}

// Debugging: Log received data
error_log("Schedule Date: $schedule_date, Trainer ID: $trainer_id, User ID: $user_id");
error_log("Equipment Data: " . print_r($equipment, true));

foreach ($equipment as $equip) {
    $inventory_id = $equip['id'];
    $start_time = $equip['start'];
    $end_time = $equip['end'];

    // Debugging: Log each equipment entry
    error_log("Processing Equipment - ID: $inventory_id, Start: $start_time, End: $end_time");

    // Insert into equipment_usage table
    $stmt = $conn->prepare("INSERT INTO equipment_usage (user_id, inventory_id, schedule_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    
    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "SQL Prepare Error: " . $conn->error]);
        exit();
    }

    $stmt->bind_param("iisss", $user_id, $inventory_id, $schedule_date, $start_time, $end_time);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "SQL Execution Error: " . $stmt->error]);
        exit();
    }
}

// If a trainer is selected, insert into schedule_trainer table
if (!empty($trainer_id)) {
    $stmt = $conn->prepare("INSERT INTO schedule_trainer (user_id, trainer_id, schedule_date, status) VALUES (?, ?, ?, 'pending')");
    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "SQL Prepare Error (Trainer): " . $conn->error]);
        exit();
    }

    $stmt->bind_param("iis", $user_id, $trainer_id, $schedule_date);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "SQL Execution Error (Trainer): " . $stmt->error]);
        exit();
    }
}

echo json_encode(["status" => "success", "message" => "Schedule created successfully."]);
exit();
?>
