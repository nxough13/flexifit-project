<?php
ob_start(); // Turn on output buffering
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include "../includes/header.php";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all equipment from the equipment_inventory table
$sql = "SELECT ei.inventory_id, e.name AS equipment_name, e.description, ei.identifier, ei.status, ei.active_status, e.image
        FROM equipment_inventory ei
        JOIN equipment e ON ei.equipment_id = e.equipment_id";
$result = $conn->query($sql);

// Get stats for the dashboard
$stats = [
    'total_equipment' => 0,
    'available' => 0,
    'in_use' => 0,
    'maintenance' => 0,
    'active' => 0,
    'disabled' => 0
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['total_equipment']++;
        $stats[$row['status']]++;
        $stats[$row['active_status'] == 'active' ? 'active' : 'disabled']++;
    }
    $result->data_seek(0); // Reset pointer for the main display
}

// Fetch most booked equipment
$bookedQuery = "SELECT 
                ei.identifier, 
                e.name AS equipment_name,
                COUNT(s.schedule_id) AS booking_count
                FROM schedules s
                JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
                JOIN equipment e ON ei.equipment_id = e.equipment_id
                GROUP BY ei.identifier, e.name
                ORDER BY booking_count DESC
                LIMIT 5";
$bookedResult = $conn->query($bookedQuery);

$bookedLabels = [];
$bookedData = [];
$bookedColors = ['#FFD700', '#5cb85c', '#f0ad4e', '#5bc0de', '#d9534f']; // Gold, Green, Orange, Blue, Red

if ($bookedResult->num_rows > 0) {
    while ($row = $bookedResult->fetch_assoc()) {
        $bookedLabels[] = $row['equipment_name'] . " (" . $row['identifier'] . ")";
        $bookedData[] = $row['booking_count'];
    }
}

$available_count = $stats['available'] ?? 0;
$in_use_count = $stats['in_use'] ?? 0;
$maintenance_count = $stats['maintenance'] ?? 0;
$active_count = $stats['active'] ?? 0;
$disabled_count = $stats['disabled'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipments</title>
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            /* Gold/Yellow */
            --primary-dark: #e6c200;
            --secondary: #000000;
            /* Black */
            --secondary-light: #1a1a1a;
            --danger: #d9534f;
            --success: #5cb85c;
            --warning: #f0ad4e;
            --info: #5bc0de;
            --light: #f8f9fa;
            --dark: #212121;
            --text-light: #f8f8f8;
            --text-dark: #333;
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --card-bg: #1e1e1e;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            margin: 0;
            padding: 20px;
            color: var(--text-light);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h2 {
            color: var(--primary);
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            color: var(--secondary);
            cursor: pointer;
            background-color: var(--primary);
            border: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .btn i {
            font-size: 16px;
        }

        /* Analytics Dashboard */
        .analytics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            border: 1px solid var(--secondary-light);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .stat-card h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: var(--text-light);
        }

        .stat-available {
            color: var(--success);
        }

        .stat-in_use {
            color: var(--warning);
        }

        .stat-maintenance {
            color: var(--danger);
        }

        .stat-active {
            color: var(--primary);
        }

        .stat-disabled {
            color: #888;
        }

        .chart-container {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            border: 1px solid var(--secondary-light);
        }

        .chart-title {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 18px;
            font-weight: 600;
        }

        /* Equipment Grid */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .equipment-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid var(--secondary-light);
        }

        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .equipment-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--secondary-light);
        }

        .equipment-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--secondary);
        }

        .badge-available {
            background-color: var(--success);
        }

        .badge-in_use {
            background-color: var(--warning);
            color: var(--secondary);
        }

        .badge-maintenance {
            background-color: var(--danger);
        }

        .badge-disabled {
            background-color: #444;
        }

        .equipment-content {
            padding: 15px;
        }

        .equipment-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--primary);
        }

        .equipment-id {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 8px;
        }

        .equipment-desc {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .equipment-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #aaa;
            margin-bottom: 15px;
        }

        .equipment-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .action-btn i {
            font-size: 14px;
        }

        .btn-edit {
            background-color: var(--primary);
            color: var(--secondary);
        }

        .btn-edit:hover {
            background-color: var(--primary-dark);
        }

        .btn-disable {
            background-color: var(--danger);
            color: white;
        }

        .btn-disable:hover {
            background-color: #c9302c;
        }

        .btn-enable {
            background-color: var(--success);
            color: white;
        }

        .btn-enable:hover {
            background-color: #449d44;
        }

        /* Disabled state */
        .disabled-card {
            opacity: 0.7;
            filter: grayscale(70%);
        }

        .disabled-card:hover {
            transform: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Table View */
        .table-container {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            border: 1px solid var(--secondary-light);
            overflow-x: auto;
        }

        .table-title {
            color: var(--primary);
            font-size: 20px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-light);
        }

        .equipment-table th {
            background-color: var(--secondary);
            color: var(--primary);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
        }

        .equipment-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--secondary-light);
            vertical-align: middle;
        }

        .equipment-table tr:last-child td {
            border-bottom: none;
        }

        .equipment-table tr:hover {
            background-color: var(--secondary-light);
        }

        .table-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background-color: var(--success);
            color: white;
        }

        .status-in_use {
            background-color: var(--warning);
            color: var(--secondary);
        }

        .status-maintenance {
            background-color: var(--danger);
            color: white;
        }

        .status-disabled {
            background-color: #444;
            color: white;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .table-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .table-btn i {
            font-size: 12px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .analytics-dashboard {
                grid-template-columns: 1fr;
            }

            .equipment-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .equipment-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        .chart-wrapper {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            flex: 1;
            min-height: 300px;
            max-height: 350px;
            padding-bottom: 40px;
            /* Added space for labels */
            position: relative;
            /* For absolute positioning of legend */
        }

        /* Chart title styling */
        .chart-title {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }

        /* Chart canvas styling */
        .chart-container canvas {
            width: 100% !important;
            height: 250px !important;
        }

        /* Legend positioning */
        .chart-legend {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin: 5px;
            color: var(--text-light);
            font-size: 14px;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-wrapper {
                flex-direction: column;
            }

            .chart-container {
                padding-bottom: 60px;
                /* More space for stacked legends */
            }
        }

        .horizontal-bar-container {
            width: 100%;
            height: 250px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="top-bar">
            <h2><i class="fas fa-dumbbell"></i> Equipment Management</h2>
            <div style="display: flex; gap: 10px;">
                <a href="add-equipment.php" class="btn"><i class="fas fa-plus"></i> Add Equipment</a>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="analytics-dashboard">
            <div class="stat-card">
                <h3>Total Equipment</h3>
                <p class="stat-value"><?= $stats['total_equipment'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Available</h3>
                <p class="stat-value stat-available"><?= $stats['available'] ?></p>
            </div>
            <div class="stat-card">
                <h3>In Use</h3>
                <p class="stat-value stat-in_use"><?= $stats['in_use'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Maintenance</h3>
                <p class="stat-value stat-maintenance"><?= $stats['maintenance'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Active</h3>
                <p class="stat-value stat-active"><?= $stats['active'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Disabled</h3>
                <p class="stat-value stat-disabled"><?= $stats['disabled'] ?></p>
            </div>
        </div>

        <!-- Charts Section - Modified -->
        <div class="chart-wrapper">
            <div class="chart-container">
                <h3 class="chart-title">Equipment Status Distribution</h3>
                <canvas id="statusChart" height="250"></canvas>
                <div class="chart-legend" id="statusLegend"></div>
            </div>

            <div class="chart-container">
                <h3 class="chart-title">Most Booked Equipment</h3>
                <div class="horizontal-bar-container">
                    <canvas id="bookedChart" height="250"></canvas>
                </div>
                <div class="chart-legend" id="bookedLegend"></div>
            </div>
        </div>

        <!-- Equipment Grid View -->
        <div class="equipment-grid">
            <?php while ($row = $result->fetch_assoc()) : ?>
                <?php
                $is_disabled = ($row['active_status'] == 'disabled');
                $status_class = strtolower($row['status']);
                ?>

                <div class="equipment-card <?= $is_disabled ? 'disabled-card' : '' ?>">
                    <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image'])
                                    ? 'uploads/' . htmlspecialchars($row['image'])
                                    : 'https://via.placeholder.com/300x180?text=Equipment' ?>"
                        class="equipment-image"
                        alt="<?= htmlspecialchars($row['equipment_name']) ?>">

                    <span class="equipment-badge badge-<?= $is_disabled ? 'disabled' : $status_class ?>">
                        <?= $is_disabled ? 'Disabled' : ucfirst($row['status']) ?>
                    </span>

                    <div class="equipment-content">
                        <h3 class="equipment-title"><?= htmlspecialchars($row['equipment_name']) ?></h3>
                        <p class="equipment-id">ID: <?= htmlspecialchars($row['inventory_id']) ?></p>
                        <p class="equipment-desc"><?= htmlspecialchars($row['description']) ?></p>

                        <div class="equipment-meta">
                            <span><i class="fas fa-barcode"></i> <?= htmlspecialchars($row['identifier']) ?></span>
                            <span><i class="fas fa-power-off"></i> <?= ucfirst($row['active_status']) ?></span>
                        </div>

                        <div class="equipment-actions">
                            <?php if (!$is_disabled) : ?>
                                <a href="edit-equipment.php?inventory_id=<?= $row['inventory_id'] ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="disable-equipment.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                                    <button type="submit" class="action-btn btn-disable">
                                        <i class="fas fa-ban"></i> Disable
                                    </button>
                                </form>
                            <?php else : ?>
                                <button class="action-btn btn-edit" disabled>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="enable-equipment.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                                    <button type="submit" class="action-btn btn-enable">
                                        <i class="fas fa-check"></i> Enable
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Equipment Table View -->
        <div class="table-container">
            <h3 class="table-title"><i class="fas fa-table"></i> Equipment List</h3>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Identifier</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); // Reset pointer for table display 
                    ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <?php
                        $is_disabled = ($row['active_status'] == 'disabled');
                        $status_class = strtolower($row['status']);
                        ?>
                        <tr>
                            <td>
                                <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image'])
                                                ? 'uploads/' . htmlspecialchars($row['image'])
                                                : 'https://via.placeholder.com/50?text=No+Image' ?>"
                                    class="table-img"
                                    alt="<?= htmlspecialchars($row['equipment_name']) ?>">
                            </td>
                            <td><?= htmlspecialchars($row['inventory_id']) ?></td>
                            <td><?= htmlspecialchars($row['equipment_name']) ?></td>
                            <td><?= htmlspecialchars($row['identifier']) ?></td>
                            <td><?= htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : '') ?></td>
                            <td>
                                <span class="status-badge status-<?= $status_class ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $is_disabled ? 'status-disabled' : 'status-available' ?>">
                                    <?= ucfirst($row['active_status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php if (!$is_disabled) : ?>
                                        <a href="edit-equipment.php?inventory_id=<?= $row['inventory_id'] ?>" class="table-btn" style="background-color: var(--primary); color: var(--secondary);">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="disable-equipment.php" method="POST" style="margin:0;">
                                            <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                                            <button type="submit" class="table-btn" style="background-color: var(--danger); color: white;">
                                                <i class="fas fa-ban"></i> Disable
                                            </button>
                                        </form>
                                    <?php else : ?>
                                        <button class="table-btn" style="background-color: #555; color: white;" disabled>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="enable-equipment.php" method="POST" style="margin:0;">
                                            <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                                            <button type="submit" class="table-btn" style="background-color: var(--success); color: white;">
                                                <i class="fas fa-check"></i> Enable
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Status Distribution Chart (unchanged)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Use', 'Maintenance'],
                datasets: [{
                    data: [<?= $available_count ?>, <?= $in_use_count ?>, <?= $maintenance_count ?>],
                    backgroundColor: [
                        '#5cb85c', // Available - green
                        '#f0ad4e', // In Use - orange
                        '#d9534f'  // Maintenance - red
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                cutout: '60%'
            }
        });

        // Most Booked Equipment Chart (new)
        const bookedCtx = document.getElementById('bookedChart').getContext('2d');
        const bookedChart = new Chart(bookedCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($bookedLabels) ?>,
                datasets: [{
                    label: 'Booking Count',
                    data: <?= json_encode($bookedData) ?>,
                    backgroundColor: <?= json_encode(array_slice($bookedColors, 0, count($bookedData))) ?>,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Makes bars horizontal
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: '#f8f8f8',
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#f8f8f8'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
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
                                return context.parsed.x + ' booking' + (context.parsed.x !== 1 ? 's' : '');
                            }
                        }
                    }
                }
            }
        });

        // Custom legend implementation (modified to work with both charts)
        function createCustomLegend(chart, legendId) {
            const legendContainer = document.getElementById(legendId);
            legendContainer.innerHTML = '';
            
            if (chart.data.labels && chart.data.datasets) {
                chart.data.labels.forEach((label, i) => {
                    const legendItem = document.createElement('div');
                    legendItem.className = 'legend-item';
                    
                    const legendColor = document.createElement('div');
                    legendColor.className = 'legend-color';
                    legendColor.style.backgroundColor = chart.data.datasets[0].backgroundColor[i];
                    
                    const legendText = document.createElement('span');
                    legendText.textContent = label;
                    
                    legendItem.appendChild(legendColor);
                    legendItem.appendChild(legendText);
                    legendContainer.appendChild(legendItem);
                });
            }
        }

        // Create legends after charts are rendered
        createCustomLegend(statusChart, 'statusLegend');
        createCustomLegend(bookedChart, 'bookedLegend');
</script>
</body>

</html>

<?php $conn->close(); ?>
<?php ob_end_flush(); // At the end of file ?>