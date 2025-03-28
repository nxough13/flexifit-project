<?php
ob_start(); // Turn on output buffering
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

// ====== MODIFIED: Check if at least date is provided ======
if (!isset($_POST['date'])) {
    echo json_encode(["conflict" => true, "message" => "Please select a date."]);
    exit;
}

$date = $_POST['date'];

// ====== MODIFIED: Make equipment optional ======
$equipment = isset($_POST['equipment']) ? json_decode($_POST['equipment'], true) : null;

// Date validation - Can't be earlier than today
$date_today = date("Y-m-d");
if ($date < $date_today) {
    echo json_encode(["conflict" => true, "message" => "The selected date can't be earlier than today."]);
    exit;
}

// ====== NEW: Check if member is trying to book overlapping equipment times ======
if ($equipment && count($equipment) > 1) {
    $timeSlots = [];
    foreach ($equipment as $item) {
        $start = strtotime($item["start"]);
        $end = strtotime($item["end"]);
        
        // Check for overlaps with already selected equipment
        foreach ($timeSlots as $slot) {
            if ($start < $slot['end'] && $end > $slot['start']) {
                echo json_encode([
                    "conflict" => true, 
                    "message" => "You cannot use multiple equipment at the same time. Please adjust your schedule."
                ]);
                exit;
            }
        }
        
        $timeSlots[] = ['start' => $start, 'end' => $end];
    }
}

// ====== MODIFIED: Only validate equipment if provided ======
if ($equipment) {
    foreach ($equipment as $item) {
        $inventory_id = $item["id"];
        $start_time = strtotime($item["start"]);
        $end_time = strtotime($item["end"]);

        $start_time_str = date("H:i:s", $start_time);
        $end_time_str = date("H:i:s", $end_time);

        // 1. Ensure End Time is After Start Time
        if ($end_time <= $start_time) {
            echo json_encode(["conflict" => true, "message" => "End time must be after start time"]);
            exit;
        }

        // 2. Enforce Duration Limits
        $duration = ($end_time - $start_time) / 60;
        if ($duration < 5 || $duration > 40) {
            echo json_encode(["conflict" => true, "message" => "Duration must be between 5 and 40 minutes"]);
            exit;
        }

        // 3. Check if Member is Already Booked at This Time (for any equipment)
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
            echo json_encode(["conflict" => true, "message" => "You already have a booking during this time"]);
            exit;
        }

        // 4. Check if Equipment is Available
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
            echo json_encode(["conflict" => true, "message" => "This equipment is already booked at your selected time"]);
            exit;
        }
    }
}

// Check membership dates
$membership_query = "SELECT start_date, end_date FROM members WHERE member_id = ?";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$membership_result = $stmt->get_result();

if ($membership_result->num_rows === 0) {
    echo json_encode(["conflict" => true, "message" => "No active membership found"]);
    exit;
}

$membership = $membership_result->fetch_assoc();
if ($date < $membership['start_date'] || $date > $membership['end_date']) {
    echo json_encode([
        "conflict" => true, 
        "message" => sprintf(
            "Selected date is outside your membership period (%s to %s)",
            $membership['start_date'],
            $membership['end_date']
        )
    ]);
    exit;
}

// ====== NEW: Check if at least equipment or trainer is selected ======
if ((!$equipment || count($equipment) === 0) && !isset($_POST['trainer_id'])) {
    echo json_encode(["conflict" => true, "message" => "Please select at least one equipment or trainer"]);
    exit;
}

echo json_encode(["conflict" => false, "message" => "Schedule is valid."]);
?>
<?php ob_end_flush(); // At the end of file ?>