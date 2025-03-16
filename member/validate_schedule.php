<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["conflict" => true, "message" => "User not authenticated."]);
    exit;
}

$member_id = $_SESSION['member_id'];  // Fetch it directly from the session

$user_id = $_SESSION['user_id'];
$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

if (!$member_id) {
    echo json_encode(["conflict" => true, "message" => "Member ID not found for user."]);
    exit;
}

// Ensure POST values exist
if (!isset($_POST['date']) || !isset($_POST['equipment'])) {
    echo json_encode(["conflict" => true, "message" => "Missing date or equipment."]);
    exit;
}

$date = $_POST['date'];  // Using 'date' as the correct column name
$equipment = json_decode($_POST['equipment'], true);
$response = ["conflict" => false];

// Date validation - Can't be earlier than today
$date_today = date("Y-m-d");
if ($date < $date_today) {
    echo json_encode(["conflict" => true, "message" => "The selected date can't be earlier than today."]);
    exit;
}

foreach ($equipment as $item) {
    $inventory_id = $item["id"];
    $start_time = strtotime($item["start"]);
    $end_time = strtotime($item["end"]);

    // Store these results in variables before using them in bind_param
    $start_time_str = date("H:i:s", $start_time);
    $end_time_str = date("H:i:s", $end_time);

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
              AND date = ? 
              AND (
                  (start_time < ? AND end_time > ?) OR  
                  (start_time < ? AND end_time > ?) OR  
                  (start_time >= ? AND end_time <= ?)   
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssss", $member_id, $date,  
                      $end_time_str, $start_time_str, 
                      $start_time_str, $start_time_str, 
                      $start_time_str, $end_time_str);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["conflict" => true, "message" => "Schedule conflict! Member is already using another equipment during this time."]);
        exit;
    }

    // ✅ 4. Check if Equipment is Already Booked
    $equipment_query = "SELECT * FROM schedules 
                        WHERE inventory_id = ? 
                        AND date = ? 
                        AND status != 'cancelled'
                        AND (
                            (start_time < ? AND end_time > ?) OR  
                            (start_time < ? AND end_time > ?) OR  
                            (start_time >= ? AND end_time <= ?)   
                        )";

    $stmt = $conn->prepare($equipment_query);
    $stmt->bind_param("isssssss", $inventory_id, $date,  
                      $end_time_str, $start_time_str, 
                      $start_time_str, $start_time_str, 
                      $start_time_str, $end_time_str);
    $stmt->execute();
    $equipment_result = $stmt->get_result();

    if ($equipment_result->num_rows > 0) {
        echo json_encode(["conflict" => true, "message" => "Schedule conflict! Equipment ID: " . $inventory_id . " is already booked at this time."]);
        exit;
    }
}

echo json_encode(["conflict" => false, "message" => "Schedule is valid."]);
?>
