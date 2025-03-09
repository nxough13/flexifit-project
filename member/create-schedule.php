<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
include '../includes/header.php';
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// Fetch available equipment
$options = "";
$query = "SELECT ei.inventory_id, e.name, ei.identifier, ei.status 
          FROM equipment_inventory ei
          JOIN equipment e ON ei.equipment_id = e.equipment_id
          WHERE ei.status = 'available'";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['inventory_id']}'>{$row['name']} (ID: {$row['inventory_id']})</option>";
}

// Fetch available trainers and their specialties
$trainers_query = "SELECT t.trainer_id, t.first_name, t.last_name, s.name AS specialty, t.availability_status, t.image 
                   FROM trainers t
                   JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
                   JOIN specialty s ON ts.specialty_id = s.specialty_id
                   WHERE t.status = 'active' AND t.availability_status = 'available'";

$trainers_result = mysqli_query($conn, $trainers_query);
$trainers = [];
while ($trainer = mysqli_fetch_assoc($trainers_result)) {
    $trainers[] = $trainer;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Equipment</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Your CSS here as before */
        body { font-family: Arial, sans-serif; background-color: #111; color: white; }
        .container { display: flex; justify-content: space-between; max-width: 900px; margin: auto; }
        .box { width: 48%; padding: 20px; background-color: #222; border-radius: 10px; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
        label { display: block; font-weight: bold; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #FFC107; border-radius: 5px; background-color: #333; color: white; }
        .equipment-box { border: 1px solid #FFC107; padding: 10px; margin-bottom: 10px; background-color: #333; border-radius: 5px; }
        button { padding: 10px; width: 100%; background-color: #FFC107; border: none; color: black; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .trainer-card { border: 1px solid #FFC107; padding: 10px; width: 90%; margin: 10px 0; background-color: #222; border-radius: 5px; }
        .trainer-card:hover { background-color: #FFC107; color: black; }
        .trainer-card img { max-width: 80px; border-radius: 5px; }
        .selected-trainer { background-color: #f0f8ff; }
        #equipment-container { margin-bottom: 10px; }
        #add-equipment { background-color: #fcd100; color: black; padding: 12px; font-weight: bold; width: 100%; cursor: pointer; }
        #add-equipment:hover { background-color: #e0a800; }
    </style>
</head>
<body>
<div class="container">
    <!-- Left Box (Equipment Scheduling) -->
    <div class="box">
        <h2>Schedule Equipment</h2>
        <label for="schedule_date">Select Date:</label>
        <input type="date" id="schedule_date" name="schedule_date" required>
        <div id="equipment-container">
            <div class="equipment-box">
                <label>Equipment:</label>
                <select name="equipment[]" class="equipment-select">
                    <option value="">Select Equipment</option>
                    <?php echo $options; ?>
                </select>
                <label>Start Time:</label>
                <input type="time" name="start_time[]" class="start-time" required>
                <label>End Time:</label>
                <input type="time" name="end_time[]" class="end-time" required>
                <button type="button" class="remove-equipment">Remove</button>
            </div>
        </div>
        <button type="button" id="add-equipment">Add Equipment</button>
    </div>

    <!-- Right Box (Trainer Scheduling) -->
    <div class="box">
        <h3>Select Trainer (Optional)</h3>
        <div id="trainer-list">
            <?php foreach ($trainers as $trainer): ?>
                <div class="trainer-card" data-id="<?php echo $trainer['trainer_id']; ?>">
                    <img src="../admin/uploads/<?php echo $trainer['image']; ?>" alt="Trainer Image">
                    <h4><?php echo $trainer['first_name'] . " " . $trainer['last_name']; ?></h4>
                    <p>Specialty: <?php echo $trainer['specialty']; ?></p>
                    <p>Available: <?php echo $trainer['availability_status']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="selected_trainer" id="selected_trainer">
    </div>
</div>

<!-- Submit Button -->
<div style="text-align: center; margin-top: 20px;">
    <button type="button" id="submit-schedule">Submit Schedule</button>
</div>

<script>
$(document).ready(function() {
    let equipmentOptions = <?php echo json_encode($options); ?>;

    // Add new equipment input
    $("#add-equipment").click(function() {
        let newBox = `
            <div class="equipment-box">
                <label>Equipment:</label>
                <select name="equipment[]" class="equipment-select">
                    <option value="">Select Equipment</option>
                    ${equipmentOptions}
                </select>

                <label>Start Time:</label>
                <input type="time" name="start_time[]" class="start-time" required>

                <label>End Time:</label>
                <input type="time" name="end_time[]" class="end-time" required>

                <button type="button" class="remove-equipment">Remove</button>
            </div>`;

        $("#equipment-container").append(newBox);
    });

    // Remove equipment input
    $(document).on("click", ".remove-equipment", function() {
        $(this).closest(".equipment-box").remove();
    });

    // Time validation for each added equipment
    $(document).on("blur", "input[name='start_time[]'], input[name='end_time[]']", function() {
        validateTime();
    });

    function validateTime() {
        let valid = true;
        let equipmentSchedules = [];

        $("input[name='start_time[]']").each(function(index) {
            let startTime = $(this).val();
            let endTime = $("input[name='end_time[]']").eq(index).val();

            // Ensure the start and end times are provided
            if (startTime && endTime) {
                let start = new Date("1970-01-01T" + startTime + "Z");
                let end = new Date("1970-01-01T" + endTime + "Z");

                // Check if the start time is earlier than the end time
                if (start >= end) {
                    alert("End time must be later than start time.");
                    valid = false;
                    return false;
                }

                // Check if the time duration is between 5 and 40 minutes
                let duration = (end - start) / (1000 * 60); // Convert milliseconds to minutes
                if (duration < 5 || duration > 40) {
                    alert("Equipment usage must be between 5 to 40 minutes.");
                    valid = false;
                    return false;
                }

                // Check for overlapping schedules
                let currentSchedule = { start: start, end: end };
                for (let existingSchedule of equipmentSchedules) {
                    if (
                        (currentSchedule.start < existingSchedule.end && currentSchedule.end > existingSchedule.start)
                    ) {
                        alert("Your equipment time overlaps with another schedule. Please adjust the timings.");
                        valid = false;
                        return false;
                    }
                }
                equipmentSchedules.push(currentSchedule);
            }
        });

        return valid;
    }

    // Submit schedule
    $("#submit-schedule").click(function () {
        let scheduleDate = $("#schedule_date").val();
        let selectedTrainer = $("#selected_trainer").val();
        let equipmentData = [];

        $(".equipment-box").each(function () {
            let equipmentId = $(this).find(".equipment-select").val();
            let startTime = $(this).find(".start-time").val();
            let endTime = $(this).find(".end-time").val();

            if (equipmentId && startTime && endTime) {
                equipmentData.push({
                    id: equipmentId,
                    start: startTime,
                    end: endTime
                });
            }
        });

        if (equipmentData.length === 0) {
            alert("Please add at least one equipment schedule.");
            return;
        }

        if (!validateTime()) {
            return;  // If time validation fails, stop the submission
        }

        $.ajax({
            url: "submit_schedule.php",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                schedule_date: scheduleDate,
                equipment: equipmentData,
                trainer_id: selectedTrainer
            }),
            success: function (response) {
                let res = JSON.parse(response);
                alert(res.message);
                if (res.status === "success") {
                    location.reload();
                }
            }
        });
    });
});
</script>


</body>
</html>
