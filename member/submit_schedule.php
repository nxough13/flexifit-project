<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
session_start();
// Debug log file
$log_file = "schedule_debug.log";
file_put_contents($log_file, "--- New Request " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents($log_file, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    file_put_contents($log_file, "Connection error: " . $conn->connect_error . "\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    file_put_contents($log_file, "No user_id in session\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "User not authenticated."]));
}

$member_id = $_SESSION['member_id'];  // Fetch it directly from the session
 // Store in session when the user logs in

$user_id = $_SESSION['user_id'];
file_put_contents($log_file, "User ID: $user_id\n", FILE_APPEND);

// Fetch member_id based on user_id
$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

if (!$member_id) {
    file_put_contents($log_file, "No member_id found for user_id: $user_id\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "Member ID not found for user: " . $user_id]));
}

file_put_contents($log_file, "Member ID: $member_id\n", FILE_APPEND);

// Get data from AJAX request
if (!isset($_POST['date']) || !isset($_POST['equipment'])) {
    file_put_contents($log_file, "Missing required POST parameters\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "Missing required parameters."]));
}

$date = $_POST['date'];
$equipment_list = json_decode($_POST['equipment'], true); 
$trainer_id = isset($_POST['trainer_id']) ? $_POST['trainer_id'] : null;

file_put_contents($log_file, "Date: $date\nEquipment: " . print_r($equipment_list, true) . "\nTrainer ID: $trainer_id\n", FILE_APPEND);

// Validate that we have equipment data
if (empty($equipment_list)) {
    file_put_contents($log_file, "Empty equipment list\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "No equipment data received."]));
}

// Process each equipment item
try {
    foreach ($equipment_list as $equipment) {
        $inventory_id = $equipment['id'];
        $start_time = $equipment['start'];
        $end_time = $equipment['end'];
        
        file_put_contents($log_file, "Processing equipment: ID=$inventory_id, Start=$start_time, End=$end_time\n", FILE_APPEND);

        // Insert into schedules table for equipment
        $insert_query = "INSERT INTO schedules (member_id, inventory_id, date, start_time, end_time, status) 
                         VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            file_put_contents($log_file, "Prepare error: " . $conn->error . "\n", FILE_APPEND);
            die(json_encode(["status" => "error", "message" => "Error preparing statement: " . $conn->error]));
        }
        
        $stmt->bind_param("iisss", $member_id, $inventory_id, $date, $start_time, $end_time);
        
        if (!$stmt->execute()) {
            file_put_contents($log_file, "Execute error: " . $stmt->error . "\n", FILE_APPEND);
            die(json_encode(["status" => "error", "message" => "Error scheduling equipment: " . $stmt->error]));
        }
        
        $schedule_id = $stmt->insert_id;
        file_put_contents($log_file, "Inserted schedule ID: $schedule_id\n", FILE_APPEND);
        $stmt->close();

        // Update Equipment Status
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
        $result = $stmt->execute();
        file_put_contents($log_file, "Equipment status update result: " . ($result ? "success" : "failed") . "\n", FILE_APPEND);
        $stmt->close();

        // If a trainer is selected, insert into schedule_trainer table
        if ($trainer_id) {
            $insert_trainer_schedule_query = "INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) 
                                              VALUES (?, ?, 'approved')";
            $stmt = $conn->prepare($insert_trainer_schedule_query);
            $stmt->bind_param("ii", $schedule_id, $trainer_id);
            $result = $stmt->execute();
            file_put_contents($log_file, "Trainer schedule insert result: " . ($result ? "success" : "failed") . "\n", FILE_APPEND);
            $stmt->close();
        }
    }

    file_put_contents($log_file, "All schedules processed successfully\n", FILE_APPEND);
    echo json_encode(["status" => "success", "message" => "Schedule saved successfully."]);
    
} catch (Exception $e) {
    file_put_contents($log_file, "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "An error occurred: " . $e->getMessage()]);
}
?>