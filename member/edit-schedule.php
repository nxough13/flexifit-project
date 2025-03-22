<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
include "../includes/header.php";

if (!isset($_SESSION['user_id'])) {
    echo "Error: User not authenticated.";
    exit;
}

$user_id = $_SESSION['user_id'];
$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

if (!$member_id) {
    echo "Error: Member ID not found.";
    exit;
}

// Fetch member schedules
$schedules_query = "
    SELECT s.schedule_id, s.date, s.start_time, s.end_time, ei.inventory_id, ei.identifier, e.name AS equipment_name,
           t.trainer_id, t.first_name, t.last_name
    FROM schedules s
    JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    LEFT JOIN schedule_trainer st ON s.schedule_id = st.schedule_id
    LEFT JOIN trainers t ON st.trainer_id = t.trainer_id
    WHERE s.member_id = ? AND s.status != 'cancelled'
    ORDER BY s.date DESC
";
$stmt = $conn->prepare($schedules_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

// Fetch available trainers
$trainers_result = $conn->query("
    SELECT t.trainer_id, t.first_name, t.last_name, s.name AS specialty, t.image
    FROM trainers t
    JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
    JOIN specialty s ON ts.specialty_id = s.specialty_id
    WHERE t.status = 'active' AND t.availability_status = 'Available'
");
$trainers = [];
while ($trainer = $trainers_result->fetch_assoc()) {
    $trainers[] = $trainer;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #111; color: white; }
        .container { max-width: 900px; margin: auto; padding: 20px; }
        .box { background-color: #222; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
        label { display: block; font-weight: bold; margin-top: 10px; }
        input, select { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #FFC107; border-radius: 5px; background-color: #333; color: white; font-size: 16px; }
        button { padding: 15px; width: 100%; background-color: #FFC107; border: none; color: black; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        button:hover { background-color: #e0a800; }
        #delete-schedule { background-color: crimson; }
        #delete-schedule:hover { background-color: #a80000; }
        #edit-form { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container">
    <div class="box">
        <h2>Edit Your Schedule</h2>

        <label for="schedule-select">Select a Schedule to Edit:</label>
        <select id="schedule-select">
            <option value="">-- Select --</option>
            <?php foreach ($schedules as $s): ?>
                <option value="<?= $s['schedule_id']; ?>"
                    data-date="<?= $s['date']; ?>"
                    data-start="<?= $s['start_time']; ?>"
                    data-end="<?= $s['end_time']; ?>"
                    data-equipment-id="<?= $s['inventory_id']; ?>"
                    data-trainer-id="<?= $s['trainer_id']; ?>">
                    <?= $s['date'] ?> | <?= $s['equipment_name'] ?> (<?= $s['identifier'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div id="edit-form" style="display:none;">
            <form id="update-schedule-form">
                <input type="hidden" name="schedule_id" id="schedule-id">

                <label>Date:</label>
                <input type="date" name="edit_date" id="edit-date" required>

                <label>Start Time:</label>
                <input type="time" name="edit_start_time" id="edit-start-time" required>

                <label>End Time:</label>
                <input type="time" name="edit_end_time" id="edit-end-time" required>

                <label>Equipment:</label>
                <select name="edit_equipment" id="edit-equipment" required></select>

                <label>Trainer (optional):</label>
                <select name="edit_trainer" id="edit-trainer">
                    <option value="">-- None --</option>
                    <?php foreach ($trainers as $t): ?>
                        <option value="<?= $t['trainer_id']; ?>"><?= $t['first_name'] . ' ' . $t['last_name']; ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Update Schedule</button>
                <button type="button" id="delete-schedule">Delete Schedule</button>
            </form>
        </div>
    </div>
</div>

<script>
$("#schedule-select").on("change", function () {
    const selected = $(this).find("option:selected");

    if (!selected.val()) {
        $("#edit-form").hide();
        return;
    }

    const scheduleId = selected.val();
    const date = selected.data("date");
    const start = selected.data("start");
    const end = selected.data("end");
    const equipmentId = selected.data("equipment-id");
    const trainerId = selected.data("trainer-id");

    $("#schedule-id").val(scheduleId);
    $("#edit-date").val(date);
    $("#edit-start-time").val(start);
    $("#edit-end-time").val(end);
    $("#edit-trainer").val(trainerId);

    // Get available equipment
    $.post("fetch-available-equipment.php", {
        schedule_date: date,
        start_time: start,
        end_time: end,
        current_equipment_id: equipmentId
    }, function (data) {
        if (!data.conflict) {
            let options = '';
            data.equipment.forEach(function (item) {
                options += `<option value="${item.inventory_id}">${item.name} (${item.identifier})</option>`;
            });
            $("#edit-equipment").html(options);
            $("#edit-equipment").val(equipmentId);  // Set current equipment
            $("#edit-form").show();
        } else {
            alert(data.message);
        }
    }, "json");
});

$("#update-schedule-form").on("submit", function (e) {
    e.preventDefault();
    $.ajax({
        url: "update_schedule.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (res) {
            alert(res.message);
            if (res.status === "success") location.reload();
        }
    });
});

$("#delete-schedule").on("click", function () {
    const id = $("#schedule-id").val();
    if (confirm("Are you sure you want to delete this schedule?")) {
        $.post("delete_schedule.php", { schedule_id: id }, function (res) {
            alert(res.message);
            if (res.status === "success") location.reload();
        }, "json");
    }
});
</script>

</body>
</html>
