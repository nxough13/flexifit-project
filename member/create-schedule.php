<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Fetch equipment options for JavaScript
$options = "";
$query = "SELECT ei.inventory_id, e.name, ei.serial_number, ei.status 
          FROM equipment_inventory ei
          JOIN equipment e ON ei.equipment_id = e.equipment_id";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['inventory_id']}'>{$row['name']} (ID: {$row['inventory_id']})</option>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Equipment</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            margin: 20px;
        }

        .container {
            display: flex;
            justify-content: space-between;
            max-width: 900px;
            margin: auto;
        }

        .box {
            width: 48%;
            padding: 15px;
            border-radius: 8px;
            background-color: #222;
            box-shadow: 0px 0px 10px rgba(255, 193, 7, 0.8);
        }

        h2, h3 {
            color: #FFC107;
            text-align: center;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #FFC107;
            border-radius: 5px;
            background-color: #333;
            color: white;
        }

        .equipment-box {
            border: 1px solid #FFC107;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #333;
            border-radius: 5px;
        }

        button {
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            background-color: #FFC107;
            color: black;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover {
            background-color: #ffdb58;
        }

        #trainer-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .trainer-card {
            border: 1px solid #FFC107;
            padding: 10px;
            width: 90%;
            margin: 10px 0;
            text-align: center;
            cursor: pointer;
            background-color: #222;
            border-radius: 5px;
            transition: 0.3s;
        }

        .trainer-card img {
            max-width: 80px;
            height: auto;
            border-radius: 5px;
        }

        .trainer-card:hover {
            background-color: #FFC107;
            color: black;
        }

        .trainer-card.selected-trainer {
            border: 3px solid #FFC107;
            background-color: #444;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Left Box: Equipment Selection -->
    <div class="box">
        <h2>Schedule Equipment</h2>
        
        <!-- Date Selection -->
        <label for="schedule_date">Select Date:</label>
        <input type="date" id="schedule_date" name="schedule_date" required>

        <!-- Equipment Selection -->
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

    <!-- Right Box: Trainer Selection -->
    <div class="box">
        <h3>Select Trainer (Optional)</h3>
        <div id="trainer-list">
            <?php
            $trainer_query = "SELECT trainer_id, first_name, last_name, specialty, availability_status, image FROM trainers WHERE status = 'active'";
            $trainer_result = mysqli_query($conn, $trainer_query);
            while ($trainer = mysqli_fetch_assoc($trainer_result)) {
                $full_name = $trainer['first_name'] . " " . $trainer['last_name'];
                echo "
                    <div class='trainer-card' data-id='{$trainer['trainer_id']}'>
                        <img src='../uploads/trainers/{$trainer['image']}' alt='{$full_name}'>
                        <h4>{$full_name}</h4>
                        <p>Specialty: {$trainer['specialty']}</p>
                        <p>Available: {$trainer['availability_status']}</p>
                    </div>
                ";
            }
            ?>
        </div>
        <input type="hidden" name="selected_trainer" id="selected_trainer">
    </div>
</div>

<!-- Submit Button -->
<div style="text-align: center; margin-top: 20px;">
    <button type="submit" id="submit-schedule">Submit Schedule</button>
</div>

<script>
$(document).ready(function() {
    let equipmentOptions = `<?php echo $options; ?>`;

    // Add Equipment Box
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

    // Remove Equipment
    $(document).on("click", ".remove-equipment", function() {
        $(this).closest(".equipment-box").remove();
    });

    // Trainer Selection
    $(document).on("click", ".trainer-card", function () {
        let trainerId = $(this).data("id");

        $(".trainer-card").removeClass("selected-trainer");
        $(this).addClass("selected-trainer");
        $("#selected_trainer").val(trainerId);
    });
});
</script>

</body>
</html>
