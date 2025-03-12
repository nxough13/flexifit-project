<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
include "../includes/header.php";

if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
}

// Fetch available equipment, ensuring no time conflict
$options = "";
$query = "
    SELECT ei.inventory_id, e.name, ei.identifier, ei.status 
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
while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['inventory_id']}'>{$row['name']} (ID: {$row['inventory_id']})</option>";
}
$stmt->close();

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
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
           
        }

        .container {
            display: flex;
            justify-content: space-between;
            max-width: 1400px;
            margin: auto;
        }

        #submit-schedule {
    width: auto; /* Change width from 100% to auto */
    padding: 15px 30px; /* Adjust padding for a more compact button */
    background-color: #FFC107;
    border: none;
    color: black;
    font-weight: bold;
    cursor: pointer;
    font-size: 16px;
    border-radius: 5px;
    margin-top: 20px;
    display: block;
    margin-left: auto;
    margin-right: auto; /* Center the button horizontally */
}

#submit-schedule:hover {
    background-color: #e0a800;
}

        .box {
            width: 55%;
            padding: 25px;
            background-color: #222;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.8);
            margin-top: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }

        input,
        select {
            width: 90%;
            padding: 12px;
            margin-top: 10px;
            border: 1px solid #FFC107;
            border-radius: 5px;
            background-color: #333;
            color: white;
            font-size: 16px;
        }

        button {
            padding: 15px;
            width: 55%;
            background-color: #FFC107;
            border: none;
            color: black;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
        }

        button:hover {
            background-color: #e0a800;
        }

        .error-message {
            color: red;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .trainer-card {
            display: flex;
            align-items: center;
            border: 1px solid #FFC107;
            padding: 15px;
            width: 90%;
            margin: 10px 0;
            background-color: #222;
            border-radius: 5px;
            font-size: 16px;
        }

        .trainer-card:hover {
            background-color: #FFC107;
            color: black;
        }

        .trainer-card.selected {
            background-color: #FFC107;
        }

        .trainer-card h4 {
            font-size: 22px;
            margin: 0 0 5px 0;
        }

        .trainer-card .trainer-info {
            display: block;
        }

        .trainer-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-right: 20px;
        }

        .trainer-card p {
            font-size: 17px;
            margin: 5px 0;
        }

        #equipment-container {
            margin-bottom: 15px;
        }

        #add-equipment {
            background-color: #fcd100;
            color: black;
            padding: 15px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
        }

        #add-equipment:hover {
            background-color: #e0a800;
        }

        h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: #FFC107;
        }
    </style>
</head>
<body>
    <br>
<div class="container">
    <!-- Left Box (Equipment Scheduling) -->
    <div class="box">
        <h2>Schedule Equipment</h2>
        <div class="error-message" id="error-message" style="display: none;"></div>
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
                    <div class="trainer-info">
                        <h4><?php echo $trainer['first_name'] . " " . $trainer['last_name']; ?></h4>
                        <p>Specialty: <?php echo $trainer['specialty']; ?></p>
                        <p>Availability: <?php echo $trainer['availability_status']; ?></p>
                    </div>
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
    // Add new equipment input
    $("#add-equipment").click(function() {
        let newBox = `
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
            </div>`;
        $("#equipment-container").append(newBox);
    });

    // Remove equipment input
    $(document).on("click", ".remove-equipment", function() {
        $(this).closest(".equipment-box").remove();
    });

    // Trainer selection functionality
    $(".trainer-card").click(function() {
        $(".trainer-card").removeClass("selected");
        $(this).addClass("selected");
        let trainerId = $(this).data("id");
        $("#selected_trainer").val(trainerId);
    });

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

    if (!scheduleDate) {
        $("#error-message").text("Please select a date.").show();
        return;
    }

    if (equipmentData.length === 0) {
        $("#error-message").text("Please add at least one equipment schedule.").show();
        return;
    }

    if (!validateTime()) {
        return;
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
            if (response.status === "success") {
                alert(response.message);
                window.location.href = "index.php"; // Redirect on success
            } else {
                $("#error-message").text(response.message).show();
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
            $("#error-message").text("Something went wrong. Please try again.").show();
        }
    });
});


    // Time validation
    function validateTime(equipmentData) {
        let valid = true;
        let equipmentSchedules = [];
        $("#error-message").hide();

        $("input[name='start_time[]']").each(function(index) {
            let startTime = $(this).val();
            let endTime = $("input[name='end_time[]']").eq(index).val();

            if (startTime && endTime) {
                let start = new Date("1970-01-01T" + startTime + "Z");
                let end = new Date("1970-01-01T" + endTime + "Z");

                if (start >= end) {
                    $("#error-message").text("End time must be later than start time.").show();
                    valid = false;
                    return false;
                }

                let duration = (end - start) / (1000 * 60); // Convert to minutes
                if (duration < 5 || duration > 40) {
                    $("#error-message").text("Equipment usage must be between 5 to 40 minutes.").show();
                    valid = false;
                    return false;
                }

                let currentSchedule = { start: start, end: end };
                for (let existingSchedule of equipmentSchedules) {
                    if (currentSchedule.start < existingSchedule.end && currentSchedule.end > existingSchedule.start) {
                        $("#error-message").text("Your equipment time overlaps with another schedule. Please adjust the timings.").show();
                        valid = false;
                        return false;
                    }
                }
                equipmentSchedules.push(currentSchedule);
            }
        });

        return valid;
    }
});
</script>
<br>
</body>
</html>

 <?php include '../includes/footer.php'; ?> 