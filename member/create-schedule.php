<?php
// Start session & connect to database
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in-----------------------------
// if (!isset($_SESSION["user_id"])) {
//     die("Access denied. Please log in.");
// }

// Get logged-in member's ID----------------------------
// $user_id = $_SESSION["user_id"];
// $member_query = $conn->query("SELECT member_id FROM members WHERE user_id = $user_id LIMIT 1");

// if ($member_query->num_rows == 0) {
//     die("You are not a registered member.");
// }
// $member = $member_query->fetch_assoc();
// $member_id = $member["member_id"];
$member_id = 1;

// Fetch available equipment
$equipment_query = $conn->query("SELECT * FROM equipment WHERE availability_status = 'available'");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST["equipment_id"];
    $session_date = $_POST["session_date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    // Check for scheduling conflicts
    $conflict_query = $conn->query("SELECT * FROM schedules 
                                    WHERE equipment_id = $equipment_id 
                                    AND session_date = '$session_date' 
                                    AND ((start_time <= '$start_time' AND end_time > '$start_time') 
                                    OR (start_time < '$end_time' AND end_time >= '$end_time'))");

    if ($conflict_query->num_rows > 0) {
        $status = 'pending'; // Conflict exists, needs admin review
        $message = "⚠️ Conflict detected! Your request is pending approval.";
    } else {
        $status = 'approved'; // No conflicts, auto-approved
        $message = "✅ Schedule created successfully!";
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO schedules (member_id, equipment_id, session_date, start_time, end_time, status) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $member_id, $equipment_id, $session_date, $start_time, $end_time, $status);

    if ($stmt->execute()) {
        echo "<script>alert('$message'); window.location.href = 'view-schedules.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: Could not create schedule.');</script>";
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
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 500px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: auto;
        }
        h2 {
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        label {
            font-weight: bold;
            text-align: left;
        }
        select, input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .submit-btn {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            font-size: 18px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create a Schedule</h2>
    <form method="POST">
        <label for="session_date">Select Date:</label>
        <input type="date" name="session_date" id="session_date" required min="<?php echo date('Y-m-d'); ?>">

        <label for="equipment_id">Select Equipment:</label>
        <select name="equipment_id" id="equipment_id" required>
            <option value="" disabled selected>Choose an equipment</option>
            <?php while ($row = $equipment_query->fetch_assoc()): ?>
                <option value="<?php echo $row["equipment_id"]; ?>">
                    <?php echo $row["name"]; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" id="start_time" required>

        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" id="end_time" required>

        <button type="submit" class="submit-btn">Book Schedule</button>
    </form>
</div>

</body>
</html>
