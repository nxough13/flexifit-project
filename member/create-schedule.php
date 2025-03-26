<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
include "../includes/header.php";

// header("Content-Type: application/json");
// Fetch all available equipment without filtering by existing schedules
$date = $_POST['date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];  // Retrieve user_id from session
} else {
    echo "Error: User not authenticated. Session ID: " . session_id();  // Debug session ID if no user_id in session
    exit;  // Exit if the user is not authenticated
}

$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

// Check if we got a member_id
if ($member_id) {
    // Store member_id in session
    $_SESSION['member_id'] = $member_id;
} else {
    // Handle the case where member_id is not found (user might not be a member)
    echo "Error: Member ID not found for user ID $user_id.";
    exit;
}

// Debugging: Check if member_id is set in session
file_put_contents("schedule_debug.log", "Session member_id: " . $_SESSION['member_id'] . "\n", FILE_APPEND);



$query = "
    SELECT ei.inventory_id, e.name, ei.identifier 
    FROM equipment_inventory ei
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    WHERE ei.active_status = 'active'
    AND ei.inventory_id NOT IN (
        SELECT s.inventory_id 
        FROM schedules s 
        WHERE s.date = ?
        AND (
            (s.start_time <= ? AND s.end_time > ?) OR 
            (s.start_time < ? AND s.end_time >= ?)
        )
    )";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $date, $start_time, $start_time, $end_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();
$options = "";

while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['inventory_id']}'>{$row['name']} (ID: {$row['identifier']})</option>";
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
        body { font-family: Arial, sans-serif; background-color: #111; color: white; }
        .container { display: flex; justify-content: space-between; max-width: 1200px; margin: auto; }
        .box { width: 48%; padding: 25px; background-color: #222; border-radius: 10px; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); margin-top: 20px; }
        label { display: block; font-weight: bold; margin-top: 10px; }
        input, select { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #FFC107; border-radius: 5px; background-color: #333; color: white; font-size: 16px; }
        button { padding: 15px; width: 100%; background-color: #FFC107; border: none; color: black; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        button:hover { background-color: #e0a800; }
        .error-msg { color: red; font-size: 14px; margin-top: 5px; display: none; }
        .trainer-card { display: flex; align-items: center; border: 1px solid #FFC107; padding: 15px; width: 100%; margin: 10px 0; background-color: #222; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .trainer-card:hover, .trainer-card.selected { background-color: #FFC107; color: black; }
        .trainer-card img { width: 120px; height: 120px; border-radius: 50%; margin-right: 20px; }
        #error-message {
    color: red;
    font-size: 18px;
    margin: 20px auto;
    width: 90%;
    display: none;
    padding: 15px;
    border: 2px solid red;  /* Border around the error message */
    border-radius: 10px;  /* Rounded corners */
    background-color: #f8d7da;  /* Light red background */
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);  /* Subtle shadow for the box */
    text-align: left;  /* Align text to the left */
}

#error-message ul {
    list-style-type: disc;
    padding-left: 20px;
    margin: 0;  /* Remove default margin from <ul> */
}
    </style>
</head>
<body>

<div id="error-message">
    <ul style="list-style-type: disc; padding-left: 20px;">
        <!-- Error messages will be dynamically inserted here -->
    </ul>
</div>

<form id="schedule-form" action="submit_schedule.php" method="POST">
<div class="container">
    <div class="box">
        <h2>Schedule Equipment</h2>
        
        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required>
        <div class="error-msg" id="date-error"></div>

        <div id="equipment-container">
            <div class="equipment-box">
                <label>Equipment:</label>
                <select name="equipment[]" class="equipment-select">
                    <option value="">Select Equipment</option>
                    <?php echo $options; ?>
                </select>
                <div class="error-msg"></div>

                <label>Start Time:</label>
                <input type="time" name="start_time[]" class="start-time" required>
                <div class="error-msg"></div>

                <label>End Time:</label>
                <input type="time" name="end_time[]" class="end-time" required>
                <div class="error-msg"></div>

                <button type="button" class="remove-equipment">Remove</button>
            </div>
        </div>

        <button type="button" id="add-equipment">Add Equipment</button>
    </div>

    <div class="box">
        <h3>Select Trainer (Optional: based on trainer's Specialty)</h3>
        <div id="trainer-list">
            <?php foreach ($trainers as $trainer): ?>
                <div class="trainer-card" data-id="<?php echo $trainer['trainer_id']; ?>">
                    <img src="../admin/uploads/<?php echo $trainer['image']; ?>" alt="Trainer Image">
                    <div>
                        <h4><?php echo $trainer['first_name'] . " " . $trainer['last_name']; ?></h4>
                        <p>Specialty: <?php echo $trainer['specialty']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="selected_trainer" id="selected_trainer">
    </div>
</div>

<div id="error-message" style="color: red; display: none;"></div>
<div style="text-align: center; margin-top: 20px;">
    <button type="button" id="submit-schedule">Submit Schedule</button>
</div>
            </form>
<script>
$(document).ready(function() {

    console.log("jQuery is ready!");


    $("#add-equipment").click(function () {
        let newBox = $(".equipment-box:first").clone();
        newBox.find("select, input").val(""); // Reset cloned inputs
        $("#equipment-container").append(newBox);
    });

    $(document).on("click", ".remove-equipment", function() {
        if ($(".equipment-box").length > 1) {
            $(this).closest(".equipment-box").remove();
        }
    });

    $(".trainer-card").click(function() {
        $(".trainer-card").removeClass("selected");
        $(this).addClass("selected");
        $("#selected_trainer").val($(this).data("id"));
    });

    $("#submit-schedule").click(function () {
    // Debugging log to confirm that the button is being clicked
    // alert("Button clicked!");

    let scheduleDate = $("#date").val();
    let selectedTrainer = $("#selected_trainer").val();
    let equipmentData = [];
    let errorMessages = []; // Array to store all error messages
    let hasError = false;

    // Hide all previous error messages
    $(".error-msg").hide();
    $("#error-message").hide();  // Hide error message section initially

    // Validate Date (Ensure it's not before today)
    let today = new Date();
    let formattedToday = today.getFullYear() + '-' + 
                         String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(today.getDate()).padStart(2, '0');
    
    if (scheduleDate < formattedToday) {
        errorMessages.push("The selected date can't be earlier than today.");
        hasError = true;
    }

    // Validate if at least one equipment or trainer is selected
    let atLeastOneSelected = false;
    $(".equipment-box").each(function () {
        if ($(this).find(".equipment-select").val()) {
            atLeastOneSelected = true;
        }
    });

    if (!scheduleDate || (!selectedTrainer && !atLeastOneSelected)) {
        errorMessages.push("At least one equipment or one trainer must be selected.");
        hasError = true;
    }

    $(".equipment-box").each(function () {
        let equipmentId = $(this).find(".equipment-select").val();
        let startTime = $(this).find(".start-time").val();
        let endTime = $(this).find(".end-time").val();
        if (!equipmentId) return;
        // Clear previous errors
        $(this).find(".error-msg").text("").hide();

        // Validate Equipment, Start Time, End Time
        if (!equipmentId) {
            $(this).find(".error-msg").eq(0).text("Please select equipment.").show();
            hasError = true;
        }
        if (!startTime) {
            $(this).find(".error-msg").eq(1).text("Start time is required.").show();
            hasError = true;
        }
        if (!endTime) {
            $(this).find(".error-msg").eq(2).text("End time is required.").show();
            hasError = true;
        }

        // Validate that end time is not earlier than start time
        if (startTime && endTime) {
            let start = new Date(`1970-01-01T${startTime}`);
            let end = new Date(`1970-01-01T${endTime}`);
            if (start >= end) {
                $(this).find(".error-msg").eq(3).text("End time must be after start time.").show();
                errorMessages.push("End time must be after start time.");
                hasError = true;
            }

            // Duration must be between 5 and 40 minutes
            let duration = (end - start) / (1000 * 60);
            if (duration < 5) {
                $(this).find(".error-msg").eq(4).text("Duration must be at least 5 minutes.").show();
                errorMessages.push("Duration must be at least 5 minutes.");
                hasError = true;
            }
            if (duration > 40) {
                $(this).find(".error-msg").eq(5).text("Duration cannot exceed 40 minutes.").show();
                errorMessages.push("Duration cannot exceed 40 minutes.");
                hasError = true;
            }
        }

        // Collect data if no errors
        if (!hasError) {
            equipmentData.push({ id: equipmentId, start: startTime, end: endTime });
        }
    });

    if (hasError) {
        // Display all errors as a single message
        let errorMessage = "Input invalid. Issues may be:\n\n" + errorMessages.join("\n");
        $("#error-message ul").empty();  // Clear any previous errors
        errorMessages.forEach(msg => {
            $("#error-message ul").append(`<li>${msg}</li>`);
        });
        $("#error-message").show();
        return; // Stop the function if there are errors
    }

    // Debugging log before sending AJAX request
    console.log("Sending data to backend");
    let memberId = "<?php echo $_SESSION['member_id']; ?>"
    // Validate the schedule with the backend
    $.ajax({
        url: "validate_schedule.php",
        method: "POST",
        data: {
            date: scheduleDate,
            equipment: JSON.stringify(equipmentData),
            member_id: memberId
        },
        dataType: "json",  // Expect JSON response
        success: function(res) {
            if (res.conflict) {
                alert(res.message);
            } else {
                // Now submit the schedule
                $.ajax({
                    url: "submit_schedule.php",
                    method: "POST",
                    data: {
                        date: scheduleDate,
                        equipment: JSON.stringify(equipmentData),
                        trainer_id: selectedTrainer,
                        member_id: memberId
                    },
                    dataType: "json",  // Expect JSON response
                    success: function(response) {
                        console.log("Response received:", response);
                        alert(response.message);
                        if (response.status === "success") {
                            location.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error submitting schedule:", error);
                        console.error("Response:", xhr.responseText);
                        alert("Error submitting schedule: " + error);
                    }
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Validation error:", error);
            console.error("Response:", xhr.responseText);
            alert("Error validating schedule: " + error);
        }
    });
});
});
</script>

</body>
</html>
