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
           t.trainer_id, t.first_name, t.last_name, t.image AS trainer_image, s.status
    FROM schedules s
    JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    LEFT JOIN schedule_trainer st ON s.schedule_id = st.schedule_id
    LEFT JOIN trainers t ON st.trainer_id = t.trainer_id
    WHERE s.member_id = ? AND s.status != 'cancelled'
    ORDER BY s.date ASC, s.start_time ASC
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
    <title>My Schedules</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #121212;
            --accent: #2c2c2c;
            --card-bg: #1e1e1e;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --pending: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header-divider {
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            border: none;
            margin: 0 auto 30px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--accent);
        }
        
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .schedule-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary);
            position: relative;
        }
        
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .schedule-card.pending {
            border-left-color: var(--pending);
        }
        
        .schedule-card.approved {
            border-left-color: var(--success);
        }
        
        .schedule-card.completed {
            border-left-color: var(--text-secondary);
        }
        
        .schedule-header {
            padding: 20px;
            border-bottom: 1px solid var(--accent);
            position: relative;
        }
        
        .schedule-date {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .schedule-time {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .schedule-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: var(--pending);
            color: white;
        }
        
        .status-approved {
            background-color: var(--success);
            color: white;
        }
        
        .status-completed {
            background-color: var(--text-secondary);
            color: var(--secondary);
        }
        
        .schedule-body {
            padding: 20px;
        }
        
        .schedule-equipment {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .equipment-icon {
            font-size: 1.8rem;
            color: var(--primary);
            margin-right: 15px;
        }
        
        .equipment-info h3 {
            margin: 0 0 5px;
            font-size: 1.2rem;
        }
        
        .equipment-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .schedule-trainer {
            display: flex;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--accent);
        }
        
        .trainer-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--primary);
        }
        
        .trainer-info h4 {
            margin: 0 0 5px;
            font-size: 1.1rem;
        }
        
        .trainer-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
        
        .no-trainer {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .schedule-actions {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid var(--accent);
            background: rgba(255, 215, 0, 0.05);
        }
        
        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .edit-btn {
            background-color: var(--primary);
            color: var(--secondary);
        }
        
        .edit-btn:hover {
            background-color: #e6c200;
        }
        
        .delete-btn {
            background-color: var(--danger);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        .edit-form-container {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
        }
        
        .edit-form-container.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: var(--secondary);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .form-actions button {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .update-btn {
            background: var(--primary);
            color: var(--secondary);
            border: none;
        }
        
        .update-btn:hover {
            background: #e6c200;
        }
        
        .cancel-btn {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--accent);
        }
        
        .cancel-btn:hover {
            background: var(--accent);
        }
        
        .no-schedules {
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 15px;
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                border-bottom: none;
                border-left: 3px solid transparent;
            }
            
            .tab.active {
                border-bottom: none;
                border-left-color: var(--primary);
            }
        }

        
.membership-period-banner {
    background-color: rgba(255, 215, 0, 0.1);
    padding: 12px 20px;
    border-radius: 8px;
    margin: 0 auto 30px;
    text-align: center;
    max-width: 80%;
    border-left: 4px solid var(--primary);
    color: var(--text);
    font-size: 0.95rem;
}

.membership-period-banner i {
    color: var(--primary);
    margin-right: 8px;
}

.membership-period-banner strong {
    color: var(--primary);
}
    </style>
</head>
<body>

<div class="page-container">
    <div class="page-header">
        <h1>My Schedules</h1>
        <div class="header-divider"></div>
        <!-- Add this right after the header divider in your HTML -->
<div class="membership-period-banner">
    <i class="fas fa-calendar-alt"></i>
    Your membership is valid from 
    <strong id="membership-start-date">Loading...</strong> to 
    <strong id="membership-end-date">Loading...</strong>
</div>
    </div>
    
    <div class="tabs">
        <div class="tab active" data-tab="upcoming">Upcoming</div>
        <div class="tab" data-tab="past">Past Sessions</div>
    </div>
    
    <div class="tab-content active" id="upcoming-schedules">
        <?php if (empty($schedules)): ?>
            <div class="no-schedules">
                <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 20px; color: var(--primary);"></i>
                <h3>No Upcoming Schedules</h3>
                <p>You don't have any upcoming scheduled sessions.</p>
            </div>
        <?php else: ?>
            <div class="schedule-grid">
                <?php 
                $today = date('Y-m-d');
                foreach ($schedules as $schedule): 
                    if ($schedule['date'] >= $today): 
                        $status_class = strtolower($schedule['status']);
                ?>
                    <div class="schedule-card <?= $status_class ?>">
                        <div class="schedule-header">
                            <div class="schedule-date">
                                <?= date('F j, Y', strtotime($schedule['date'])) ?>
                            </div>
                            <div class="schedule-time">
                                <?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                            </div>
                            <span class="schedule-status status-<?= $status_class ?>">
                                <?= ucfirst($schedule['status']) ?>
                            </span>
                        </div>
                        
                        <div class="schedule-body">
                            <div class="schedule-equipment">
                                <div class="equipment-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="equipment-info">
                                    <h3><?= htmlspecialchars($schedule['equipment_name']) ?></h3>
                                    <p>ID: <?= htmlspecialchars($schedule['identifier']) ?></p>
                                </div>
                            </div>
                            
                            <div class="schedule-trainer">
                                <?php if ($schedule['trainer_id']): ?>
                                    <img src="<?= !empty($schedule['trainer_image']) ? '../admin/uploads/' . $schedule['trainer_image'] : '../admin/uploads/default-trainer.jpg' ?>" 
                                         alt="Trainer" class="trainer-image">
                                    <div class="trainer-info">
                                        <h4><?= htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']) ?></h4>
                                        <p>Your Trainer</p>
                                    </div>
                                <?php else: ?>
                                    <div class="no-trainer">
                                        <i class="fas fa-user-slash"></i> No trainer assigned
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="schedule-actions">
                            <button class="action-btn edit-btn" data-schedule-id="<?= $schedule['schedule_id'] ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" data-schedule-id="<?= $schedule['schedule_id'] ?>">
                                <i class="fas fa-trash-alt"></i> Cancel
                            </button>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="tab-content" id="past-schedules">
        <?php 
        $has_past = false;
        foreach ($schedules as $schedule): 
            if ($schedule['date'] < $today): 
                $has_past = true;
                $status_class = strtolower($schedule['status']);
        ?>
            <div class="schedule-card <?= $status_class ?>">
                <!-- Same card structure as upcoming schedules -->
                <!-- Omitted for brevity, but should be the same as above -->
            </div>
        <?php 
            endif;
        endforeach; 
        
        if (!$has_past): 
        ?>
            <div class="no-schedules">
                <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 20px; color: var(--primary);"></i>
                <h3>No Past Sessions</h3>
                <p>You don't have any past training sessions.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="edit-form-container" id="edit-form">
        <h2>Edit Schedule</h2>
        <form id="update-schedule-form">
            <input type="hidden" name="schedule_id" id="schedule-id">
            
            <div class="form-group">
                <label for="edit-date">Date:</label>
                <input type="date" name="edit_date" id="edit-date" required>
            </div>
            
            <div class="form-group">
                <label for="edit-start-time">Start Time:</label>
                <input type="time" name="edit_start_time" id="edit-start-time" required>
            </div>
            
            <div class="form-group">
                <label for="edit-end-time">End Time:</label>
                <input type="time" name="edit_end_time" id="edit-end-time" required>
            </div>
            
            <div class="form-group">
                <label for="edit-equipment">Equipment:</label>
                <select name="edit_equipment" id="edit-equipment" required></select>
            </div>
            
            <div class="form-group">
                <label for="edit-trainer">Trainer (optional):</label>
                <select name="edit_trainer" id="edit-trainer">
                    <option value="">-- None --</option>
                    <?php foreach ($trainers as $t): ?>
                        <option value="<?= $t['trainer_id']; ?>"><?= $t['first_name'] . ' ' . $t['last_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="update-btn">Update Schedule</button>
                <button type="button" class="cancel-btn" id="cancel-edit">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    let membershipStartDate = '';
    let membershipEndDate = '';
    let currentScheduleId = '';
    let originalEquipmentId = '';

    // Fetch membership dates
    function fetchMembershipDates() {
        return $.ajax({
            url: "get_membership_dates.php",
            method: "GET",
            dataType: "json"
        });
    }

    // ... existing code ...
    
    // Fetch and display membership dates
    function fetchAndDisplayMembershipDates() {
        $.ajax({
            url: "get_membership_dates.php",
            method: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success && response.start_date && response.end_date) {
                    membershipStartDate = response.start_date;
                    membershipEndDate = response.end_date;
                    
                    // Format dates for display
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    const startDate = new Date(membershipStartDate).toLocaleDateString(undefined, options);
                    const endDate = new Date(membershipEndDate).toLocaleDateString(undefined, options);
                    
                    // Update the banner
                    $('#membership-start-date').text(startDate);
                    $('#membership-end-date').text(endDate);
                    
                    // Set date picker limits
                    $('#edit-date').attr('min', membershipStartDate);
                    $('#edit-date').attr('max', membershipEndDate);
                } else {
                    $('#membership-period-banner').html(
                        '<i class="fas fa-exclamation-triangle"></i> ' + 
                        (response.error || "Could not load membership information")
                    ).css({
                        'background-color': 'rgba(220, 53, 69, 0.1)',
                        'border-left-color': 'var(--danger)'
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#membership-period-banner').html(
                    '<i class="fas fa-exclamation-triangle"></i> Error loading membership dates'
                ).css({
                    'background-color': 'rgba(220, 53, 69, 0.1)',
                    'border-left-color': 'var(--danger)'
                });
                console.error("Error fetching membership dates:", error);
            }
        });
    }
    
    // Call this when page loads
    fetchAndDisplayMembershipDates();
    

    // Initialize date picker and membership validation
    function initializeDateValidation() {
        fetchMembershipDates().then(function(response) {
            if (response.success && response.start_date && response.end_date) {
                membershipStartDate = response.start_date;
                membershipEndDate = response.end_date;
                
                $('#edit-date').attr('min', membershipStartDate);
                $('#edit-date').attr('max', membershipEndDate);
            } else {
                showErrorPopup([response.error || "Invalid membership data"]);
                $('#update-btn').prop('disabled', true);
            }
        }).fail(function(xhr, status, error) {
            showErrorPopup([`Server error: ${error}`]);
            console.error("AJAX Error:", status, error);
            $('#update-btn').prop('disabled', true);
        });
    }

    // Helper function to validate date
    function isValidDate(d) {
        return d instanceof Date && !isNaN(d);
    }

    // Edit button click handler
    $('.edit-btn').on('click', function() {
        currentScheduleId = $(this).data('schedule-id');
        const scheduleCard = $(this).closest('.schedule-card');
        
        // Get schedule details from the card
        const date = scheduleCard.find('.schedule-date').text().trim();
        const timeRange = scheduleCard.find('.schedule-time').text().trim();
        const [startTime, endTime] = timeRange.split(' - ');
        originalEquipmentId = scheduleCard.data('equipment-id');
        
        // Format date for input
        const formattedDate = new Date(date).toISOString().split('T')[0];
        
        // Set form values
        $('#schedule-id').val(currentScheduleId);
        $('#edit-date').val(formattedDate);
        $('#edit-start-time').val(startTime);
        $('#edit-end-time').val(endTime);
        
        // Initialize date validation
        initializeDateValidation();
        
        // Get available equipment
        checkEquipmentAvailability(formattedDate, startTime, endTime, originalEquipmentId);
        
        // Show the edit form
        $('#edit-form').addClass('active');
        $('html, body').animate({
            scrollTop: $('#edit-form').offset().top - 20
        }, 500);
    });

    // Check equipment availability
    function checkEquipmentAvailability(date, startTime, endTime, currentEquipmentId) {
        $.post("fetch-available-equipment.php", {
            schedule_date: date,
            start_time: startTime,
            end_time: endTime,
            current_equipment_id: currentEquipmentId,
            schedule_id: currentScheduleId // Include schedule ID to exclude current booking from conflict check
        }, function(data) {
            if (!data.conflict) {
                let options = '';
                data.equipment.forEach(function(item) {
                    options += `<option value="${item.inventory_id}">${item.name} (${item.identifier})</option>`;
                });
                $('#edit-equipment').html(options);
                $('#edit-equipment').val(currentEquipmentId);
            } else {
                showErrorPopup([data.message]);
                $('#edit-form').removeClass('active');
            }
        }, "json");
    }

    // Form submission handler
    $('#update-schedule-form').on('submit', function(e) {
        e.preventDefault();
        
        const scheduleDate = $('#edit-date').val();
        const startTime = $('#edit-start-time').val();
        const endTime = $('#edit-end-time').val();
        const equipmentId = $('#edit-equipment').val();
        const trainerId = $('#edit-trainer').val();
        const errorMessages = [];
        let hasError = false;

        // Date validation
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(scheduleDate);
        
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

        // Time validation
        if (!startTime) {
            errorMessages.push("Start time is required");
            hasError = true;
        }
        
        if (!endTime) {
            errorMessages.push("End time is required");
            hasError = true;
        }
        
        if (startTime && endTime) {
            const start = new Date(`1970-01-01T${startTime}`);
            const end = new Date(`1970-01-01T${endTime}`);
            
            if (start >= end) {
                errorMessages.push("End time must be after start time");
                hasError = true;
            }
            
            const duration = (end - start) / (1000 * 60);
            if (duration < 5) {
                errorMessages.push("Minimum duration is 5 minutes");
                hasError = true;
            }
            if (duration > 40) {
                errorMessages.push("Maximum duration is 40 minutes");
                hasError = true;
            }
        }

        // Equipment validation
        if (!equipmentId) {
            errorMessages.push("Please select equipment");
            hasError = true;
        }

        if (hasError) {
            showErrorPopup(errorMessages);
            return;
        }

        // Validate schedule conflicts
        validateScheduleUpdate();
    });

    // Validate schedule before updating
    function validateScheduleUpdate() {
        const scheduleDate = $('#edit-date').val();
        const startTime = $('#edit-start-time').val();
        const endTime = $('#edit-end-time').val();
        const equipmentId = $('#edit-equipment').val();
        const trainerId = $('#edit-trainer').val();
        
        $.ajax({
            url: "validate_schedule.php",
            method: "POST",
            data: {
                date: scheduleDate,
                start_time: startTime,
                end_time: endTime,
                equipment_id: equipmentId,
                member_id: "<?php echo $member_id; ?>",
                schedule_id: currentScheduleId, // Include current schedule ID to exclude from conflict check
                action: 'update'
            },
            dataType: "json",
            success: function(res) {
                if (res.conflict) {
                    showErrorPopup([res.message]);
                } else {
                    submitScheduleUpdate();
                }
            },
            error: function(xhr) {
                showErrorPopup(["Error validating schedule"]);
                console.error("Error:", xhr.responseText);
            }
        });
    }

    // Submit the schedule update
    function submitScheduleUpdate() {
        const formData = $('#update-schedule-form').serialize();
        
        $.ajax({
            url: "update_schedule.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    showSuccessPopup(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorPopup([response.message]);
                }
            },
            error: function(xhr) {
                showErrorPopup(["Error updating schedule"]);
                console.error("Error:", xhr.responseText);
            }
        });
    }

    // Cancel edit
    $('#cancel-edit').on('click', function() {
        $('#edit-form').removeClass('active');
    });

    // Delete button click
    $('.delete-btn').on('click', function() {
        if (confirm("Are you sure you want to cancel this schedule?")) {
            const scheduleId = $(this).data('schedule-id');
            $.post("delete_schedule.php", { schedule_id: scheduleId }, function(res) {
                if (res.status === "success") {
                    showSuccessPopup(res.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorPopup([res.message]);
                }
            }, "json");
        }
    });

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
    function showSuccessPopup(message) {
        $('#success-popup-content').html(message);
        $('#success-popup, #success-backdrop').fadeIn(300);
        
        $('#success-close, #success-backdrop').off().on('click', function() {
            $('#success-popup, #success-backdrop').fadeOut(300);
        });
    }

    // Initialize time pickers
    $('input[type="time"]').each(function() {
        $(this).attr('step', '300'); // 5 minute increments
    });

    // Tab switching
    $('.tab').on('click', function() {
        $('.tab').removeClass('active');
        $(this).addClass('active');
        
        const tabId = $(this).data('tab');
        $('.tab-content').removeClass('active');
        $(`#${tabId}-schedules`).addClass('active');
    });

    
    

});
</script>

</body>
</html>