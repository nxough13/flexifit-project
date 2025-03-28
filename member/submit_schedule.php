<?php
ob_start(); // Turn on output buffering
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

$member_id = $_SESSION['member_id'];
$user_id = $_SESSION['user_id'];
file_put_contents($log_file, "User ID: $user_id, Member ID: $member_id\n", FILE_APPEND);

// Get data from AJAX request
if (!isset($_POST['date'])) {
    file_put_contents($log_file, "Missing date parameter\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "Please select a date."]));
}

$date = $_POST['date'];
$equipment_list = isset($_POST['equipment']) ? json_decode($_POST['equipment'], true) : null;
$trainer_id = isset($_POST['trainer_id']) ? $_POST['trainer_id'] : null;

file_put_contents($log_file, "Date: $date\nEquipment: " . print_r($equipment_list, true) . "\nTrainer ID: $trainer_id\n", FILE_APPEND);

// Validate we have at least equipment or trainer
if (empty($equipment_list) && empty($trainer_id)) {
    file_put_contents($log_file, "No equipment or trainer selected\n", FILE_APPEND);
    die(json_encode(["status" => "error", "message" => "Please select at least one equipment or trainer."]));
}

try {
    $conn->autocommit(FALSE); // Start transaction
    
    // Process equipment if provided
    if (!empty($equipment_list)) {
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
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("iisss", $member_id, $inventory_id, $date, $start_time, $end_time);
            
            if (!$stmt->execute()) {
                throw new Exception("Error scheduling equipment: " . $stmt->error);
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
            if (!$stmt->execute()) {
                throw new Exception("Error updating equipment status: " . $stmt->error);
            }
            $stmt->close();

            // If trainer is selected, link to this schedule
            if ($trainer_id) {
                $insert_trainer_query = "INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) 
                                       VALUES (?, ?, 'approved')";
                $stmt = $conn->prepare($insert_trainer_query);
                $stmt->bind_param("ii", $schedule_id, $trainer_id);
                if (!$stmt->execute()) {
                    throw new Exception("Error assigning trainer: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    } 
    // Handle trainer-only booking
    elseif ($trainer_id) {
        file_put_contents($log_file, "Processing trainer-only booking\n", FILE_APPEND);
        
        // Insert a schedule without equipment
        $insert_query = "INSERT INTO schedules (member_id, date, start_time, end_time, status) 
                        VALUES (?, ?, NULL, NULL, 'pending')";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Error preparing schedule statement: " . $conn->error);
        }
        
        $stmt->bind_param("is", $member_id, $date);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating trainer schedule: " . $stmt->error);
        }
        
        $schedule_id = $stmt->insert_id;
        $stmt->close();

        // Assign trainer to this schedule
        $insert_trainer_query = "INSERT INTO schedule_trainer (schedule_id, trainer_id, trainer_status) 
                               VALUES (?, ?, 'approved')";
        $stmt = $conn->prepare($insert_trainer_query);
        $stmt->bind_param("ii", $schedule_id, $trainer_id);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning trainer: " . $stmt->error);
        }
        $stmt->close();
    }

    $conn->commit();
    file_put_contents($log_file, "All schedules processed successfully\n", FILE_APPEND);
    echo json_encode(["status" => "success", "message" => "Schedule saved successfully."]);
    
} catch (Exception $e) {
    $conn->rollback();
    file_put_contents($log_file, "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "An error occurred: " . $e->getMessage()]);
} finally {
    $conn->autocommit(TRUE); // Restore autocommit mode
}
?>
<?php ob_end_flush(); // At the end of file ?>