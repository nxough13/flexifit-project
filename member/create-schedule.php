<?php
session_start();
require_once '../includes/config.php'; // Database connection

// // // Check if user is logged in as a member
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'member') {
//     die("Access Denied. Please log in as a member.");
// }

// $member_id = $_SESSION['member_id']; // Get member ID from session

$member_id = 1;
$message = "";
// Fetch available equipment
$equipmentQuery = "SELECT * FROM equipment WHERE availability_status = 'available'";
$equipmentResult = mysqli_query($conn, $equipmentQuery);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST['equipment_id'];
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Check for schedule conflicts
    $conflictQuery = "SELECT * FROM schedules 
                      WHERE equipment_id = ? 
                      AND session_date = ? 
                      AND (
                          (start_time < ? AND end_time > ?) OR 
                          (start_time < ? AND end_time > ?) OR 
                          (start_time >= ? AND end_time <= ?)
                      )";
    $stmt = mysqli_prepare($conn, $conflictQuery);
    mysqli_stmt_bind_param($stmt, "isssssss", $equipment_id, $session_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    mysqli_stmt_execute($stmt);
    $conflictResult = mysqli_stmt_get_result($stmt);

    // If no conflicts, auto-approve; otherwise, set as pending
    $status = (mysqli_num_rows($conflictResult) == 0) ? 'approved' : 'pending';

    // Insert schedule
    $insertQuery = "INSERT INTO schedules (member_id, equipment_id, session_date, start_time, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "iissss", $member_id, $equipment_id, $session_date, $start_time, $end_time, $status);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Schedule successfully created! Status: " . ucfirst($status);
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; }
        .container { width: 50%; margin: auto; background: white; padding: 20px; border-radius: 5px; }
        select, input { width: 100%; padding: 8px; margin: 10px 0; }
        button { background: #28a745; color: white; padding: 10px; border: none; cursor: pointer; }
        button:hover { background: #218838; }
        .message { color: green; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Schedule</h2>
    <?php if ($message): ?>
        <p class="message"><?= $message; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <label for="equipment_id">Select Equipment:</label>
        <select name="equipment_id" required>
            <?php while ($row = mysqli_fetch_assoc($equipmentResult)) { ?>
                <option value="<?= $row['equipment_id']; ?>"><?= $row['name']; ?> (Available: <?= $row['quantity']; ?>)</option>
            <?php } ?>
        </select>

        <label for="session_date">Select Date:</label>
        <input type="date" name="session_date" required>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" required>

        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" required>

        <button type="submit">Create Schedule</button>
    </form>
</div>

</body>
</html>
