<?php
ob_start(); // Turn on output buffering
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

include '../includes/header.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get schedules data
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

// Get data for charts
$statusQuery = "SELECT status, COUNT(*) as count FROM schedules GROUP BY status";
$statusResult = $conn->query($statusQuery);
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['status']] = $row['count'];
}

$equipmentQuery = "SELECT e.name, COUNT(s.schedule_id) as count 
                  FROM schedules s
                  JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
                  JOIN equipment e ON ei.equipment_id = e.equipment_id
                  GROUP BY e.name";
$equipmentResult = $conn->query($equipmentQuery);
$equipmentData = [];
while ($row = $equipmentResult->fetch_assoc()) {
    $equipmentData[$row['name']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Schedules | FlexiFit Admin</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary: #FFC107; /* Yellow */
        --primary-dark: #FFA000; /* Darker yellow */
        --secondary: #212121; /* Dark gray/black */
        --dark: #000000; /* Pure black */
        --light: #f8f9fa; /* Light gray */
        --danger: #dc3545; /* Red */
        --success: #28a745; /* Green */
        --warning: #fd7e14; /* Orange */
        --info: #17a2b8; /* Blue */
        --text-light: #ffffff; /* White text */
        --text-dark: #212121; /* Dark text */
        --bg-dark: #111111; /* Dark background */
        --bg-light: #1e1e1e; /* Lighter dark background */
        --border-color: #333333; /* Border color */
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-light);
        margin: 0;
        padding: 0;
    }
    
    .container {
        max-width: 1400px;
        margin: 20px auto;
        padding: 20px;
        background-color: var(--bg-light);
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .page-title {
        font-size: 28px;
        color: var(--primary);
        margin: 0;
    }
    
    .charts-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .chart-card {
        background: var(--bg-light);
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        padding: 20px;
        border: 1px solid var(--border-color);
    }
    
    .chart-title {
        font-size: 18px;
        margin-top: 0;
        margin-bottom: 15px;
        color: var(--primary);
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 14px;
    }
    
    th {
        background-color: var(--secondary);
        color: var(--primary);
        padding: 12px 15px;
        text-align: left;
        position: sticky;
        top: 0;
        border: 1px solid var(--border-color);
    }
    
    td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-light);
    }
    
    tr {
        background-color: var(--bg-light);
    }
    
    tr:hover {
        background-color: var(--secondary);
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .status-pending {
        background-color: var(--warning);
        color: var(--text-dark);
    }
    
    .status-approved {
        background-color: var(--success);
        color: var(--text-light);
    }
    
    .status-cancelled {
        background-color: var(--danger);
        color: var(--text-light);
    }
    
    .status-completed {
        background-color: var(--info);
        color: var(--text-light);
    }
    
    .action-dropdown {
        padding: 6px 10px;
        border-radius: 4px;
        border: 1px solid var(--border-color);
        background-color: var(--bg-light);
        color: var(--text-light);
        cursor: pointer;
    }
    
    .save-btn {
        background-color: var(--primary);
        color: var(--text-dark);
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s;
    }
    
    .save-btn:hover {
        background-color: var(--primary-dark);
    }
    
    .no-trainer {
        color: var(--secondary);
        font-style: italic;
    }
    
    .filter-container {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        margin-bottom: 5px;
        font-weight: 600;
        font-size: 14px;
        color: var(--primary);
    }
    
    .filter-select {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid var(--border-color);
        min-width: 180px;
        background-color: var(--bg-light);
        color: var(--text-light);
    }
    
    .apply-filters {
        background-color: var(--primary);
        color: var(--text-dark);
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        align-self: flex-end;
        font-weight: 600;
    }
    
    .apply-filters:hover {
        background-color: var(--primary-dark);
    }
    
    small {
        color: #888;
    }
    
    /* Chart.js canvas background */
    canvas {
        background-color: var(--bg-light);
        border-radius: 4px;
        padding: 10px;
    }
    
    @media (max-width: 768px) {
        .charts-container {
            grid-template-columns: 1fr;
        }
        
        .filter-container {
            flex-direction: column;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Schedule Management</h1>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-container">
        <div class="chart-card">
            <h3 class="chart-title">Schedule Status Distribution</h3>
            <canvas id="statusChart" height="250"></canvas>
        </div>
        <div class="chart-card">
            <h3 class="chart-title">Equipment Usage</h3>
            <canvas id="equipmentChart" height="250"></canvas>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="filter-container">
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Equipment</label>
            <select class="filter-select" id="equipmentFilter">
                <option value="all">All Equipment</option>
                <?php foreach ($equipmentData as $equipment => $count): ?>
                    <option value="<?php echo htmlspecialchars($equipment); ?>"><?php echo htmlspecialchars($equipment); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Date Range</label>
            <select class="filter-select" id="dateFilter">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="future">Upcoming</option>
                <option value="past">Past</option>
            </select>
        </div>
        <button class="apply-filters" id="applyFilters">Apply Filters</button>
    </div>
    
    <!-- Schedules Table -->
    <div class="table-container">
        <table id="schedulesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Equipment</th>
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): 
                    $start = new DateTime($schedule['start_time']);
                    $end = new DateTime($schedule['end_time']);
                    $duration = $start->diff($end);
                    $durationStr = $duration->format('%hh %im');
                ?>
                    <tr data-status="<?php echo $schedule['status']; ?>" 
                        data-equipment="<?php echo htmlspecialchars($schedule['equipment_name']); ?>" 
                        data-date="<?php echo $schedule['date']; ?>">
                        <td><?php echo $schedule['schedule_id']; ?></td>
                        <td><?php echo htmlspecialchars($schedule['member_first_name'] . ' ' . htmlspecialchars($schedule['member_last_name'])); ?></td>
                        <td><?php echo htmlspecialchars($schedule['equipment_name']); ?><br><small><?php echo htmlspecialchars($schedule['identifier']); ?></small></td>
                        <td>
                            <?php if ($schedule['trainer_id']): ?>
                                <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                            <?php else: ?>
                                <span class="no-trainer">No trainer</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($schedule['date'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($schedule['start_time'])); ?></td>
                        <td><?php echo $durationStr; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $schedule['status']; ?>">
                                <?php echo ucfirst($schedule['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($schedule['status'] == 'pending'): ?>
                                <select class="action-dropdown" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">
                                    <option value="pending" selected>Pending</option>
                                    <option value="approved">Approve</option>
                                    <option value="cancelled">Cancel</option>
                                </select>
                                <button class="save-btn" data-schedule-id="<?php echo $schedule['schedule_id']; ?>">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            <?php else: ?>
                                <span class="status-badge status-<?php echo $schedule['status']; ?>">
                                    <?php echo ucfirst($schedule['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Cancelled', 'Completed'],
            datasets: [{
                data: [
                    <?php echo $statusData['pending'] ?? 0; ?>,
                    <?php echo $statusData['approved'] ?? 0; ?>,
                    <?php echo $statusData['cancelled'] ?? 0; ?>,
                    <?php echo $statusData['completed'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#fd7e14',
                    '#28a745',
                    '#dc3545',
                    '#17a2b8'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw + ' (' + Math.round(context.parsed * 100 / context.dataset.data.reduce((a, b) => a + b, 0)) + '%)';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Equipment Chart
    const equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
    const equipmentChart = new Chart(equipmentCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($equipmentData)); ?>,
            datasets: [{
                label: 'Number of Bookings',
                data: <?php echo json_encode(array_values($equipmentData)); ?>,
                backgroundColor: '#FFC107',
                borderColor: '#e0a800',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Filter functionality
    $(document).ready(function() {
        $('#applyFilters').click(function() {
            const statusFilter = $('#statusFilter').val();
            const equipmentFilter = $('#equipmentFilter').val();
            const dateFilter = $('#dateFilter').val();
            const today = new Date().toISOString().split('T')[0];
            
            $('#schedulesTable tbody tr').each(function() {
                const rowStatus = $(this).data('status');
                const rowEquipment = $(this).data('equipment');
                const rowDate = $(this).data('date');
                let showRow = true;
                
                // Status filter
                if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                    showRow = false;
                }
                
                // Equipment filter
                if (equipmentFilter !== 'all' && rowEquipment !== equipmentFilter) {
                    showRow = false;
                }
                
                // Date filter
                if (dateFilter !== 'all') {
                    const scheduleDate = new Date(rowDate);
                    const todayDate = new Date(today);
                    
                    if (dateFilter === 'today' && rowDate !== today) {
                        showRow = false;
                    } else if (dateFilter === 'week') {
                        const weekStart = new Date(todayDate);
                        weekStart.setDate(todayDate.getDate() - todayDate.getDay());
                        const weekEnd = new Date(weekStart);
                        weekEnd.setDate(weekStart.getDate() + 6);
                        
                        if (scheduleDate < weekStart || scheduleDate > weekEnd) {
                            showRow = false;
                        }
                    } else if (dateFilter === 'month') {
                        if (scheduleDate.getMonth() !== todayDate.getMonth() || 
                            scheduleDate.getFullYear() !== todayDate.getFullYear()) {
                            showRow = false;
                        }
                    } else if (dateFilter === 'future' && scheduleDate < todayDate) {
                        showRow = false;
                    } else if (dateFilter === 'past' && scheduleDate >= todayDate) {
                        showRow = false;
                    }
                }
                
                $(this).toggle(showRow);
            });
        });
        
        // Save button functionality
        $(".save-btn").on("click", function() {
            const scheduleId = $(this).data("schedule-id");
            const newStatus = $(this).closest("td").find(".action-dropdown").val();
            
            if (confirm(`Are you sure you want to change this schedule to "${newStatus}"?`)) {
                $.ajax({
                    url: "update_schedule_status.php",
                    method: "POST",
                    data: {
                        schedule_id: scheduleId,
                        status: newStatus
                    },
                    success: function(response) {
                        alert("Schedule status updated successfully!");
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert("Error updating schedule: " + error);
                    }
                });
            }
        });
    });
</script>

</body>
</html>
<?php ob_end_flush(); // At the end of file ?>