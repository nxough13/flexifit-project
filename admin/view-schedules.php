<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

include '../includes/header.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo "Error: Access restricted to admins only.";
    exit;
}

$query = "
    SELECT s.schedule_id, s.date, s.start_time, s.end_time, ei.inventory_id, ei.identifier, e.name AS equipment_name,
           t.trainer_id, t.first_name, t.last_name, s.status, m.user_id, u.first_name AS member_first_name, u.last_name AS member_last_name
    FROM schedules s
    JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    LEFT JOIN schedule_trainer st ON s.schedule_id = st.schedule_id
    LEFT JOIN trainers t ON st.trainer_id = t.trainer_id
    JOIN members m ON s.member_id = m.member_id
    JOIN users u ON m.user_id = u.user_id
    ORDER BY s.date DESC, s.start_time
";

$result = $conn->query($query);
$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Schedules</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #111; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border: 1px solid #FFC107; }
        th { background-color: #333; color: #FFC107; }
        td { background-color: #222; }
        tr:nth-child(even) { background-color: #333; }
        tr:hover { background-color: #555; }
        .status { font-weight: bold; padding: 6px 10px; border-radius: 5px; }
        .pending { background-color: orange; color: black; }
        .approved { background-color: green; color: white; }
        .cancelled { background-color: red; color: white; }
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        .operations-dropdown { width: 80px; background-color: #333; color: white; border: 1px solid #FFC107; border-radius: 5px; }
        .save-button { background-color: #FFC107; color: black; border: none; cursor: pointer; padding: 6px 12px; margin-left: 5px; }
        .save-button:hover { background-color: #e0a800; }
    </style>
</head>
<body>

<div class="container">
    <h2>Admin - View Schedules</h2>
    <table>
        <thead>
            <tr>
                <th>Schedule ID</th>
                <th>Member Name</th>
                <th>Equipment</th>
                <th>Trainer</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Operations</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td><?php echo $schedule['schedule_id']; ?></td>
                    <td><?php echo $schedule['member_first_name'] . ' ' . $schedule['member_last_name']; ?></td>
                    <td><?php echo $schedule['equipment_name'] . ' (' . $schedule['identifier'] . ')'; ?></td>
                    <td><?php echo $schedule['first_name'] . ' ' . $schedule['last_name']; ?></td>
                    <td><?php echo $schedule['date']; ?></td>
                    <td><?php echo $schedule['start_time']; ?></td>
                    <td><?php echo $schedule['end_time']; ?></td>
                    <td>
                        <span class="operations">
                            <?php if ($schedule['status'] == 'pending'): ?>
                                <select class="operations-dropdown" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">
                                    <option value="pending" selected>Pending</option>
                                    <option value="approved">Approve</option>
                                </select>
                                <button class="save-button" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">Save</button>
                            <?php elseif ($schedule['status'] == 'approved'): ?>
                                <span class="approved">Approved</span>
                            <?php elseif ($schedule['status'] == 'cancelled'): ?>
                                <span class="cancelled">Cancelled</span>
                            <?php elseif ($schedule['status'] == 'completed'): ?>
                                <span class="approved">Completed</span>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(".save-button").on("click", function () {
        const scheduleId = $(this).data("schedule-id");
        const newStatus = $(this).closest("td").find(".operations-dropdown").val();

        if (newStatus == 'approved') {
            $.ajax({
                url: "update_schedule_status.php",
                method: "POST",
                data: {
                    schedule_id: scheduleId,
                    status: newStatus
                },
                success: function(response) {
                    alert(response.message);
                    location.reload();
                }
            });
        }
    });
</script>

</body>
</html>
