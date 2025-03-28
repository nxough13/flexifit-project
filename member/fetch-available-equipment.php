<?php
ob_start(); // Turn on output buffering
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Ensure POST values exist
if (!isset($_POST['schedule_date']) || !isset($_POST['start_time']) || !isset($_POST['end_time'])) {
    echo json_encode(["conflict" => true, "message" => "Missing required data."]);
    exit;
}

$schedule_date = $_POST['schedule_date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// Fetch available equipment based on time conflicts
$query = "
    SELECT ei.inventory_id, e.name, ei.identifier 
    FROM equipment_inventory ei
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    WHERE ei.status = 'available'
    AND NOT EXISTS (
        SELECT 1 
        FROM equipment_usage eu
        WHERE eu.inventory_id = ei.inventory_id
        AND eu.status != 'cancelled' 
        AND (
            (eu.start_time < ? AND eu.end_time > ?) OR 
            (eu.start_time < ? AND eu.end_time > ?)
        )
    )
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $schedule_date, $start_time, $end_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();

$availableEquipment = [];
while ($row = mysqli_fetch_assoc($result)) {
    $availableEquipment[] = $row;
}

echo json_encode(["conflict" => false, "equipment" => $availableEquipment]);
$stmt->close();
?>
<?php ob_end_flush(); // At the end of file ?>

