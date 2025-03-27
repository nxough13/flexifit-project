<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Start output buffering to prevent header issues
ob_start();
include "../includes/header.php";

// Fetch all available equipment without filtering by existing schedules
$date = $_POST['date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    echo "Error: User not authenticated. Session ID: " . session_id();
    exit;
}

$member_query = "SELECT member_id FROM members WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($member_id);
$stmt->fetch();
$stmt->close();

if ($member_id) {
    $_SESSION['member_id'] = $member_id;
} else {
    echo "Error: Member ID not found for user ID $user_id.";
    exit;
}

// Equipment query
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

// Fixed trainer query - get all specialties for each trainer
$trainers_query = "SELECT t.trainer_id, t.first_name, t.last_name, 
                   GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties, 
                   t.availability_status, t.image 
                   FROM trainers t
                   JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
                   JOIN specialty s ON ts.specialty_id = s.specialty_id
                   WHERE t.status = 'active' AND t.availability_status = 'available'
                   GROUP BY t.trainer_id";

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
    <title>Schedule Equipment | FlexiFit</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FFA000;
            --secondary: #212121;
            --dark: #000000;
            --light: #f8f9fa;
            --danger: #dc3545;
            --bg-dark: #111111;
            --bg-light: #1e1e1e;
            --border-color: #333333;
            --card-shadow: 0 4px 8px rgba(255, 193, 7, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--light);
            margin: 0;
            padding: 20px;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
        }

        .content-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .equipment-column {
            flex: 1;
            min-width: 300px;
        }

        .trainer-column {
            flex: 1;
            min-width: 300px;
        }

        .box {
            background: var(--bg-light);
            padding: 25px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--primary);
            transition: all 0.3s ease;
            height: 100%;
        }

        @media (max-width: 768px) {
            .content-row {
                flex-direction: column;
            }
            
            .equipment-column, .trainer-column {
                width: 100%;
            }
        }

        .box:hover {
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        h2, h3, h4 {
            color: var(--primary);
            margin-top: 0;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            color: var(--primary);
            font-weight: 600;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--primary);
            border-radius: 5px;
            background-color: var(--bg-dark);
            color: var(--light);
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
        }

        button {
            padding: 12px 20px;
            background-color: var(--primary);
            border: none;
            color: var(--dark);
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .trainer-card {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background-color: var(--bg-dark);
            border-radius: 8px;
            border: 1px solid var(--primary);
            cursor: pointer;
            transition: all 0.3s;
        }

        .trainer-card:hover {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .trainer-card.selected {
            background-color: var(--primary);
            color: var(--dark);
        }

        .trainer-card.selected h4,
        .trainer-card.selected p {
            color: var(--dark);
        }

        .trainer-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--primary);
        }

        .trainer-info {
            flex: 1;
        }

        .trainer-info h4 {
            margin: 0 0 5px;
            color: var(--primary);
        }

        .trainer-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .specialty-badge {
            display: inline-block;
            background-color: rgba(255, 193, 7, 0.2);
            color: var(--primary);
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .selected .specialty-badge {
            background-color: rgba(0, 0, 0, 0.2);
        }

        .equipment-box {
            background-color: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }

        .remove-equipment {
            background-color: var(--danger);
            color: white;
            width: 100%;
            margin-top: 10px;
        }

        .remove-equipment:hover {
            background-color: #c82333;
        }

        #error-message {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--danger);
            color: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        #error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            margin-top: 20px;
        }

        .no-trainers {
            text-align: center;
            padding: 20px;
            color: var(--primary);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .trainer-card {
                flex-direction: column;
                text-align: center;
            }
            
            .trainer-card img {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
        #membership-period {
    padding: 10px;
    background-color: rgba(255, 193, 7, 0.1);
    border-left: 3px solid var(--primary);
    margin: 10px 0;
    border-radius: 4px;
}

#membership-period i {
    margin-right: 8px;
    color: var(--primary);
}

.date-restricted {
    color: #ccc !important;
    background-color: #f8f8f8 !important;
}

/* Error Popup Styles */
.error-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80%;
    max-width: 500px;
    background-color: #dc3545;
    color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    display: none;
}

.error-popup h3 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.error-popup-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.error-popup ul {
    padding-left: 20px;
    margin-bottom: 0;
}

.error-popup-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

/* Success Popup Styles */
.success-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80%;
    max-width: 500px;
    background-color: #28a745;
    color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    display: none;
}

.success-popup h3 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
    </style>
</head>
<body>

<div class="container">
    <div id="error-message">
        <ul></ul>
    </div>

    <form id="schedule-form" action="submit_schedule.php" method="POST">
        <div class="content-row">
            <!-- Equipment Column (Left Side) -->
            <div class="equipment-column">
                <div class="box">
                    <h2><i class="fas fa-dumbbell"></i> Schedule Equipment</h2>
                    
                    <label for="date"><i class="far fa-calendar-alt"></i> Select Date:</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    <div class="error-msg" id="date-error"></div>
                    <div id="membership-period" style="display: none;"></div>

                    <div id="equipment-container">
                        <div class="equipment-box">
                            <label><i class="fas fa-tools"></i> Equipment:</label>
                            <select name="equipment[]" class="equipment-select">
                                <option value="">Select Equipment</option>
                                <?php echo $options; ?>
                            </select>
                            <div class="error-msg"></div>

                            <label><i class="far fa-clock"></i> Start Time:</label>
                            <input type="time" name="start_time[]" class="start-time" required>
                            <div class="error-msg"></div>

                            <label><i class="far fa-clock"></i> End Time:</label>
                            <input type="time" name="end_time[]" class="end-time" required>
                            <div class="error-msg"></div>

                            <button type="button" class="remove-equipment"><i class="fas fa-trash-alt"></i> Remove</button>
                        </div>
                    </div>

                    <button type="button" id="add-equipment"><i class="fas fa-plus"></i> Add Equipment</button>
                </div>
            </div>

            <!-- Trainer Column (Right Side) -->
            <div class="trainer-column">
                <div class="box">
                    <h3><i class="fas fa-user-tie"></i> Select Trainer (Optional)</h3>
                    <div id="trainer-list">
                        <?php if (count($trainers) > 0): ?>
                            <?php foreach ($trainers as $trainer): ?>
                                <div class="trainer-card" data-id="<?php echo $trainer['trainer_id']; ?>">
                                    <img src="../admin/uploads/<?php echo $trainer['image'] ?: 'default-trainer.jpg'; ?>" alt="Trainer Image">
                                    <div class="trainer-info">
                                        <h4><?php echo $trainer['first_name'] . " " . $trainer['last_name']; ?></h4>
                                        <p>
                                            <?php 
                                            $specialties = explode(', ', $trainer['specialties']);
                                            foreach ($specialties as $specialty) {
                                                echo '<span class="specialty-badge">' . $specialty . '</span>';
                                            }
                                            ?>
                                        </p>
                                        <p><i class="fas fa-circle" style="color: <?php echo $trainer['availability_status'] === 'available' ? '#28a745' : '#dc3545'; ?>"></i> 
                                            <?php echo ucfirst($trainer['availability_status']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-trainers">
                                <i class="fas fa-exclamation-circle"></i> No available trainers found
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="selected_trainer" id="selected_trainer">
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
    <button type="button" id="clear-all" class="submit-btn" style="background-color: #dc3545;">
        <i class="fas fa-undo"></i> Clear All
    </button>
        <button type="button" id="submit-schedule" class="submit-btn"><i class="fas fa-calendar-check"></i> Submit Schedule</button>
    </form>
</div>

<script>
$(document).ready(function() {
    let membershipStartDate = '';
    let membershipEndDate = '';

    // Fetch membership dates immediately when page loads
    $.ajax({
        url: "get_membership_dates.php",
        method: "GET",
        dataType: "json",
        success: function(response) {
            if (response.success && response.start_date && response.end_date) {
                membershipStartDate = response.start_date;
                membershipEndDate = response.end_date;
                
                $('#date').attr('min', membershipStartDate);
                $('#date').attr('max', membershipEndDate);
                
                $('#membership-period').html(`
                    <i class="fas fa-calendar-check"></i> 
                    Your membership is valid from 
                    <strong>${formatDate(membershipStartDate)}</strong> to 
                    <strong>${formatDate(membershipEndDate)}</strong>
                `).show();
            } else {
                showErrorPopup([response.error || "Invalid membership data"]);
                $('#submit-schedule').prop('disabled', true);
            }
        },
        error: function(xhr, status, error) {
            showErrorPopup([`Server error: ${error}`]);
            console.error("AJAX Error:", status, error);
            $('#submit-schedule').prop('disabled', true);
        }
    });

    // Set minimum date for the date picker
    function setMinDate() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        
        today = yyyy + '-' + mm + '-' + dd;
        $('#date').attr('min', today);
    }
    
    // Call the function on page load
    setMinDate();

    // Add equipment box
    $("#add-equipment").click(function() {
        let newBox = $(".equipment-box:first").clone();
        newBox.find("select, input").val("");
        $("#equipment-container").append(newBox);
    });

    // Remove equipment box
    $(document).on("click", ".remove-equipment", function() {
        if ($(".equipment-box").length > 1) {
            $(this).closest(".equipment-box").remove();
        }
    });

    // Select trainer
    $(document).on("click", ".trainer-card", function() {
        $(".trainer-card").removeClass("selected");
        $(this).addClass("selected");
        $("#selected_trainer").val($(this).data("id"));
    });

    // Helper function to validate date
    function isValidDate(d) {
        return d instanceof Date && !isNaN(d);
    }

    // Form submission
    $("#submit-schedule").click(function() {
    let scheduleDate = $("#date").val();
    let selectedTrainer = $("#selected_trainer").val();
    let equipmentData = [];
    let errorMessages = [];
    let hasError = false;

    $(".error-msg").hide();

        // Enhanced Date validation
        let today = new Date();
        today.setHours(0, 0, 0, 0);
        let selectedDate = new Date(scheduleDate);
        
        if (!scheduleDate) {
            errorMessages.push("Please select a date");
            hasError = true;
        } else if (!isValidDate(selectedDate)) {
            errorMessages.push("Invalid date format");
            hasError = true;
        } else if (selectedDate < today) {
            errorMessages.push("Cannot select a past date");
            hasError = true;
        } else if (membershipStartDate && membershipEndDate) {
            const startDate = new Date(membershipStartDate);
            const endDate = new Date(membershipEndDate);
            
            if (selectedDate < startDate || selectedDate > endDate) {
                errorMessages.push("Selected date is outside your membership period");
                hasError = true;
            }
        }

         // Only validate equipment if any equipment fields are filled
    let hasEquipmentSelection = false;
    $(".equipment-box").each(function() {
        let $box = $(this);
        let equipmentId = $box.find(".equipment-select").val();
        let startTime = $box.find(".start-time").val();
        let endTime = $box.find(".end-time").val();

        // Skip if all equipment fields are empty
        if (!equipmentId && !startTime && !endTime) return;

        hasEquipmentSelection = true;
        
        let equipmentText = $box.find(".equipment-select option:selected").text();
        
        if (!equipmentId) {
            errorMessages.push("Please select equipment for all slots");
            hasError = true;
            return;
        }
        if (!startTime) {
            errorMessages.push("Start time is required for all slots");
            hasError = true;
            return;
        }
        if (!endTime) {
            errorMessages.push("End time is required for all slots");
            hasError = true;
            return;
        }

        let start = new Date(`1970-01-01T${startTime}`);
        let end = new Date(`1970-01-01T${endTime}`);
        
        if (start >= end) {
            errorMessages.push(`End time must be after start time for ${equipmentText}`);
            hasError = true;
            return;
        }

        let duration = (end - start) / (1000 * 60);
        if (duration < 5) {
            errorMessages.push(`Minimum duration is 5 minutes for ${equipmentText}`);
            hasError = true;
            return;
        }
        if (duration > 40) {
            errorMessages.push(`Maximum duration is 40 minutes for ${equipmentText}`);
            hasError = true;
            return;
        }

        equipmentData.push({ 
            id: equipmentId, 
            name: equipmentText,
            start: startTime, 
            end: endTime 
        });
    });

    // Check for time conflicts only if booking equipment
    if (equipmentData.length > 1) {
        for (let i = 0; i < equipmentData.length; i++) {
            for (let j = i + 1; j < equipmentData.length; j++) {
                let start1 = new Date(`1970-01-01T${equipmentData[i].start}`);
                let end1 = new Date(`1970-01-01T${equipmentData[i].end}`);
                let start2 = new Date(`1970-01-01T${equipmentData[j].start}`);
                let end2 = new Date(`1970-01-01T${equipmentData[j].end}`);
                
                if (start1 < end2 && end1 > start2) {
                    errorMessages.push(`Time conflict: You cannot use ${equipmentData[i].name} and ${equipmentData[j].name} at the same time`);
                    hasError = true;
                }
            }
        }
    }

    // Check if at least one equipment OR trainer is selected
    if (!hasEquipmentSelection && !selectedTrainer) {
        errorMessages.push("Please select at least one equipment or trainer");
        hasError = true;
    }

    if (hasError) {
        showErrorPopup(errorMessages);
        return;
    }

        // Submit data
        let memberId = "<?php echo $_SESSION['member_id']; ?>";
        
        $.ajax({
            url: "validate_schedule.php",
            method: "POST",
            data: {
                date: scheduleDate,
                equipment: equipmentData.length > 0 ? JSON.stringify(equipmentData) : null,
                member_id: memberId
            },
            dataType: "json",
            success: function(res) {
                if (res.conflict) {
                    showErrorPopup([res.message]);
                } else {
                    $.ajax({
                        url: "submit_schedule.php",
                        method: "POST",
                        data: {
                            date: scheduleDate,
                            equipment: equipmentData.length > 0 ? JSON.stringify(equipmentData) : null,
                            trainer_id: selectedTrainer || null,
                            member_id: memberId
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === "success") {
                                showSuccessPopup(response.message, "view-schedules.php");
                            } else {
                                showErrorPopup([response.message]);
                            }
                        },
                        error: function(xhr) {
                            showErrorPopup(["Error submitting schedule"]);
                            console.error("Error:", xhr.responseText);
                        }
                    });
                }
            },
            error: function(xhr) {
                showErrorPopup(["Error validating schedule"]);
                console.error("Error:", xhr.responseText);
            }
        });
    });

    // Add Clear All button functionality
    $("#clear-all").click(function() {
        // Reset date field
        $("#date").val("");
        
        // Reset equipment selections
        $(".equipment-box").each(function() {
            $(this).find(".equipment-select").val("");
            $(this).find(".start-time").val("");
            $(this).find(".end-time").val("");
        });
        
        // Remove all additional equipment boxes except the first one
        $(".equipment-box:not(:first)").remove();
        
        // Reset trainer selection
        $(".trainer-card").removeClass("selected");
        $("#selected_trainer").val("");
        
        // Clear any error messages
        $(".error-msg").hide();
    });

    // Helper function to format dates
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // Error popup function
    function showErrorPopup(messages) {
        const content = Array.isArray(messages) 
            ? `<ul>${messages.map(msg => `<li>${msg}</li>`).join('')}</ul>`
            : messages;
        
        $('#error-popup-content').html(content);
        $('#error-popup, #error-backdrop').fadeIn(300);
        
        // Close when clicking backdrop or X button
        $('#error-close, #error-backdrop').off().on('click', function() {
            $('#error-popup, #error-backdrop').fadeOut(300);
        });
    }

    // Success popup function
    function showSuccessPopup(message, redirectUrl) {
        $('#success-popup-content').html(message);
        $('#success-popup, #success-backdrop').fadeIn(300);
        
        $('#success-close, #success-backdrop').off().on('click', function() {
            $('#success-popup, #success-backdrop').fadeOut(300);
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        });
    }
});
</script>
<div class="error-popup-backdrop" id="error-backdrop"></div>
<div class="error-popup" id="error-popup">
    <button class="error-popup-close" id="error-close">&times;</button>
    <h3><i class="fas fa-exclamation-circle"></i> Error</h3>
    <div id="error-popup-content"></div>
</div>

<!-- Success Popup (optional) -->
<div class="success-popup-backdrop" id="success-backdrop"></div>
<div class="success-popup" id="success-popup">
    <button class="success-popup-close" id="success-close">&times;</button>
    <h3><i class="fas fa-check-circle"></i> Success</h3>
    <div id="success-popup-content"></div>
</div>
</body>
</html>
<?php
ob_end_flush();
?>