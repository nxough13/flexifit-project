<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all trainers
$trainersQuery = "SELECT t.trainer_id, t.first_name, t.last_name, t.email, t.age, t.gender, 
                 t.image, t.status, t.availability_status,
                 GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties
                 FROM trainers t
                 LEFT JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
                 LEFT JOIN specialty s ON ts.specialty_id = s.specialty_id
                 GROUP BY t.trainer_id ORDER BY t.trainer_id ASC";
$trainersResult = $conn->query($trainersQuery);
$trainers = [];
while ($row = $trainersResult->fetch_assoc()) {
    $trainers[] = $row;
}

// Get data for charts
$statusQuery = "SELECT status, COUNT(*) as count FROM trainers GROUP BY status";
$statusResult = $conn->query($statusQuery);
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['status']] = $row['count'];
}

// Get most booked trainers data
$bookedQuery = "SELECT t.trainer_id, CONCAT(t.first_name, ' ', t.last_name) as trainer_name, 
               COUNT(st.schedule_id) as booking_count
               FROM trainers t
               LEFT JOIN schedule_trainer st ON t.trainer_id = st.trainer_id
               GROUP BY t.trainer_id
               ORDER BY booking_count DESC
               LIMIT 5";
$bookedResult = $conn->query($bookedQuery);
$bookedTrainers = [];
$bookingCounts = [];
while ($row = $bookedResult->fetch_assoc()) {
    $bookedTrainers[] = $row['trainer_name'];
    $bookingCounts[] = $row['booking_count'];
}

// Get unique specialties for filter
$specialtyQuery = "SELECT DISTINCT name FROM specialty ORDER BY name";
$specialtyResult = $conn->query($specialtyQuery);
$uniqueSpecialties = [];
while ($row = $specialtyResult->fetch_assoc()) {
    $uniqueSpecialties[] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Trainers | FlexiFit Admin</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FFA000;
            --secondary: #212121;
            --dark: #000000;
            --light: #f8f9fa;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #fd7e14;
            --info: #17a2b8;
            --text-light: #ffffff;
            --text-dark: #121212;
            --bg-dark: #111111;
            --bg-light: #1e1e1e;
            --border-color: #333333;
            --card-shadow: 0 4px 8px rgba(255, 193, 7, 0.1);
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
            box-shadow: var(--card-shadow);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary);
        }
        
        .page-title {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
            text-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
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
            box-shadow: var(--card-shadow);
            padding: 15px;
            border: 1px solid var(--primary);
            height: 300px; /* Fixed height */
        }
        
        .chart-wrapper {
            position: relative;
            height: calc(100% - 40px);
            width: 100%;
        }
        
        .chart-title {
            font-size: 16px;
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary);
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
            border: 1px solid var(--primary);
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
        
        .table-container {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th {
            background-color: var(--primary);
            color: var(--text-dark);
            padding: 14px 16px;
            text-align: left;
            position: sticky;
            top: 0;
            font-weight: 700;
        }
        
        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-light);
        }
        
        tr {
            background-color: var(--bg-light);
            transition: all 0.2s ease;
        }
        
        tr:hover {
            background-color: var(--secondary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: var(--primary);
            color: var(--text-dark);
            box-shadow: 0 0 8px rgba(255, 193, 7, 0.4);
        }
        
        .status-disabled {
            background-color: var(--danger);
            color: var(--text-light);
        }
        
        .availability-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .availability-available {
            background-color: var(--success);
            color: var(--text-light);
        }
        
        .availability-unavailable {
            background-color: var(--warning);
            color: var(--text-dark);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 4px;
            margin-right: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .edit-btn {
            background-color: var(--primary);
            color: var(--text-dark);
        }
        
        .edit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .edit-btn.disabled {
            background-color: var(--secondary);
            color: #666;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .delete-btn {
            background-color: var(--danger);
            color: var(--text-light);
        }
        
        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        
        .enable-btn {
            background-color: var(--success);
            color: var(--text-light);
        }
        
        .enable-btn:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        .add-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary);
            color: var(--text-dark);
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .add-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }
        
        .add-btn i {
            margin-right: 8px;
        }
        
        .action-group {
            display: flex;
            gap: 8px;
        }
        
        .trainer-image {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }
        
        .default-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary);
        }
        
        .default-image i {
            color: var(--primary);
            font-size: 20px;
        }
        
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                height: 280px;
            }
            
            .filter-container {
                flex-direction: column;
            }
            
            .action-group {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                margin-bottom: 6px;
                justify-content: flex-start;
            }
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-dumbbell"></i> Trainer Management</h1>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Charts Section -->
    <div class="charts-container">
        <div class="chart-card">
            <div class="chart-wrapper">
                <h3 class="chart-title">Trainer Status Distribution</h3>
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-wrapper">
                <h3 class="chart-title">Most Booked Trainers</h3>
                <canvas id="bookedChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="filter-container">
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Availability</label>
            <select class="filter-select" id="availabilityFilter">
                <option value="all">All Availability</option>
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Specialty</label>
            <select class="filter-select" id="specialtyFilter">
                <option value="all">All Specialties</option>
                <?php foreach ($uniqueSpecialties as $specialty): ?>
                    <option value="<?php echo htmlspecialchars($specialty); ?>"><?php echo htmlspecialchars($specialty); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="apply-filters" id="applyFilters">Apply Filters</button>
    </div>
    
    <a href="create-trainers.php" class="add-btn">
        <i class="fas fa-plus"></i> Add New Trainer
    </a>
    
    <!-- Trainers Table -->
    <div class="table-container">
        <table id="trainersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Specialties</th>
                    <th>Availability</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trainers as $trainer): ?>
                    <tr data-status="<?php echo $trainer['status']; ?>" 
                        data-availability="<?php echo $trainer['availability_status']; ?>"
                        data-specialties="<?php echo htmlspecialchars($trainer['specialties']); ?>">
                        <td><?php echo $trainer['trainer_id']; ?></td>
                        <td>
                            <?php if (!empty($trainer['image'])): ?>
                                <img src="uploads/?php echo htmlspecialchars($trainer['image']); ?>" 
                                     class="trainer-image" 
                                     alt="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>">
                            <?php else: ?>
                                <div class="default-image">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                        <td><?php echo $trainer['age'] ? $trainer['age'] : 'N/A'; ?></td>
                        <td><?php echo ucfirst($trainer['gender']); ?></td>
                        <td><?php echo $trainer['specialties'] ? htmlspecialchars($trainer['specialties']) : 'N/A'; ?></td>
                        <td>
                            <span class="availability-badge availability-<?php echo strtolower($trainer['availability_status']); ?>">
                                <?php echo ucfirst($trainer['availability_status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $trainer['status']; ?>">
                                <?php echo ucfirst($trainer['status']); ?>
                            </span>
                        </td>
                        <td>
    <div class="action-group">
        <?php if ($trainer['status'] == "active"): ?>
            <a href="edit-trainers.php?trainer_id=<?php echo $trainer['trainer_id']; ?>" 
               class="action-btn edit-btn">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button onclick="disableTrainer(<?php echo $trainer['trainer_id']; ?>)" 
                    class="action-btn delete-btn">
                <i class="fas fa-ban"></i> Disable
            </button>
        <?php else: ?>
            <span class="action-btn edit-btn disabled" 
                  style="pointer-events: none; cursor: not-allowed;">
                <i class="fas fa-edit"></i> Edit
            </span>
            <button onclick="enableTrainer(<?php echo $trainer['trainer_id']; ?>)" 
                    class="action-btn enable-btn">
                <i class="fas fa-check-circle"></i> Enable
            </button>
        <?php endif; ?>
    </div>
</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Status Chart (unchanged)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Disabled'],
            datasets: [{
                data: [
                    <?php echo $statusData['active'] ?? 0; ?>,
                    <?php echo $statusData['disabled'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff',
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
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
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 20
                }
            }
        }
    });

    // Most Booked Trainers Chart
    const bookedCtx = document.getElementById('bookedChart').getContext('2d');
    const bookedChart = new Chart(bookedCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($bookedTrainers); ?>,
            datasets: [{
                label: 'Number of Bookings',
                data: <?php echo json_encode($bookingCounts); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Makes it horizontal bar chart
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff',
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                        drawBorder: false
                    }
                },
                y: {
                    ticks: {
                        color: '#fff',
                        padding: 5
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Bookings: ${context.raw}`;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    left: 5,
                    right: 5,
                    top: 5,
                    bottom: 5
                }
            },
            barPercentage: 0.8
        }
    });

    // Filter functionality
    $(document).ready(function() {
        $('#applyFilters').click(function() {
            const statusFilter = $('#statusFilter').val();
            const availabilityFilter = $('#availabilityFilter').val();
            const specialtyFilter = $('#specialtyFilter').val();
            
            $('#trainersTable tbody tr').each(function() {
                const rowStatus = $(this).data('status');
                const rowAvailability = $(this).data('availability');
                const rowSpecialties = $(this).data('specialties');
                let showRow = true;
                
                // Status filter
                if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                    showRow = false;
                }
                
                // Availability filter
                if (availabilityFilter !== 'all' && rowAvailability !== availabilityFilter) {
                    showRow = false;
                }
                
                // Specialty filter
                if (specialtyFilter !== 'all' && (!rowSpecialties || !rowSpecialties.includes(specialtyFilter))) {
                    showRow = false;
                }
                
                $(this).toggle(showRow);
            });
        });
    });

    function disableTrainer(trainerId) {
        if (confirm("Are you sure you want to disable this trainer?\n\nCurrent appointments will be cancelled.")) {
            window.location.href = "disable-trainer.php?id=" + trainerId;
        }
    }

    function enableTrainer(trainerId) {
        if (confirm("Are you sure you want to enable this trainer?\n\nThey will become available for new appointments.")) {
            window.location.href = "enable-trainer.php?id=" + trainerId;
        }
    }
</script>

</body>
</html>