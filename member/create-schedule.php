<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
include "../includes/header.php";

// Fetch all available equipment without filtering by existing schedules
$date = $_POST['date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

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
    </style>
</head>
<body>

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
        <h3>Select Trainer (Optional)</h3>
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

<script>
$(document).ready(function() {
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
        let scheduleDate = $("#date").val();
        let selectedTrainer = $("#selected_trainer").val();
        let equipmentData = [];
        let hasError = false;

        $(".error-msg").hide();

        $(".equipment-box").each(function () {
            let equipmentId = $(this).find(".equipment-select").val();
            let startTime = $(this).find(".start-time").val();
            let endTime = $(this).find(".end-time").val();

            if (!equipmentId || !startTime || !endTime) {
                $(this).find(".error-msg").text("All fields are required.").show();
                hasError = true;
            }

            if (!hasError) {
                equipmentData.push({ id: equipmentId, start: startTime, end: endTime });
            }
        });

        if (hasError) return;

        $.post("validate_schedule.php", { schedule_date: scheduleDate, equipment: equipmentData }, function(response) {
            let res = JSON.parse(response);
            if (res.conflict) {
                alert(res.message);
            } else {
                alert("Schedule submitted successfully!");
                location.reload();
            }
        });
    });
});
</script>

</body>
</html>
