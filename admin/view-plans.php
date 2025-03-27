<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all membership plans
$plansQuery = "SELECT * FROM membership_plans ORDER BY plan_id ASC";
$plansResult = $conn->query($plansQuery);
$plans = [];
while ($row = $plansResult->fetch_assoc()) {
    $plans[] = $row;
}

// Get data for charts
$statusQuery = "SELECT status, COUNT(*) as count FROM membership_plans GROUP BY status";
$statusResult = $conn->query($statusQuery);
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['status']] = $row['count'];
}

$popularityQuery = "SELECT mp.name, COUNT(m.plan_id) as member_count 
                   FROM membership_plans mp
                   LEFT JOIN members m ON mp.plan_id = m.plan_id
                   GROUP BY mp.name";
$popularityResult = $conn->query($popularityQuery);
$popularityData = [];
while ($row = $popularityResult->fetch_assoc()) {
    $popularityData[$row['name']] = $row['member_count'];
}

// Get unique durations for filter
$durationQuery = "SELECT DISTINCT duration_days FROM membership_plans ORDER BY duration_days";
$durationResult = $conn->query($durationQuery);
$uniqueDurations = [];
while ($row = $durationResult->fetch_assoc()) {
    $uniqueDurations[] = $row['duration_days'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans | FlexiFit Admin</title>
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
            padding: 20px;
            border: 1px solid var(--primary);
        }

        .chart-title {
            font-size: 18px;
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

        .price-cell {
            font-weight: 600;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
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

        .chart-card {
            height: 300px;
            /* Reduced height */
            padding: 15px;
        }

        table th:first-child,
        table td:first-child {
            width: 70px;
            text-align: center;
        }

        /* Adjust column widths */
        table th:nth-child(2),
        table td:nth-child(2) {
            width: 50px;
        }

        /* Make sure other columns don't get too wide */
        table th,
        table td {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-card {
            height: 320px;
            /* Slightly taller to accommodate labels */
            padding: 15px;
            position: relative;
        }

        .chart-container {
            height: calc(100% - 40px);
            /* Reserve space for title */
            width: 100%;
            position: relative;
        }

        canvas {
            max-height: 100%;
            max-width: 100%;
        }

        .chart-title {
            margin-bottom: 25px;
            /* More space below title */
            font-size: 16px;
            /* Slightly smaller title */
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-id-card"></i> Membership Plans</h1>
        </div>

        <!-- Charts Section -->
        <div class="charts-container">
            <div class="chart-card">
                <h3 class="chart-title">Plan Status Distribution</h3>
                <canvas id="statusChart" height="250"></canvas>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">Plan Popularity</h3>
                <canvas id="popularityChart" height="250"></canvas>
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
                <label class="filter-label">Duration</label>
                <select class="filter-select" id="durationFilter">
                    <option value="all">All Durations</option>
                    <?php foreach ($uniqueDurations as $duration): ?>
                        <option value="<?php echo $duration; ?>"><?php echo $duration; ?> Days</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="apply-filters" id="applyFilters">Apply Filters</button>
        </div>

        <a href="create-plan.php" class="add-btn">
            <i class="fas fa-plus"></i> Add New Plan
        </a>

        <!-- Plans Table -->
        <div class="table-container">
            <table id="plansTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Plan Name</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Free Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr data-status="<?php echo $plan['status']; ?>"
                            data-duration="<?php echo $plan['duration_days']; ?>">
                            <td>
                                <?php if (!empty($plan['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($plan['image']); ?>"
                                        alt="<?php echo htmlspecialchars($plan['name']); ?>"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: var(--secondary); display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                        <i class="fas fa-image" style="color: var(--primary);"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $plan['plan_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($plan['name']); ?></strong></td>
                            <td><?php echo $plan['duration_days']; ?> days</td>
                            <td class="price-cell">$<?php echo number_format($plan['price'], 2); ?></td>
                            <td><?php echo $plan['free_training_session']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $plan['status']; ?>">
                                    <?php echo ucfirst($plan['status']); ?>
                                </span>
                            </td>
                            <td>
    <div class="action-group">
        <?php if ($plan['status'] == "active"): ?>
            <a href="edit-plan.php?id=<?php echo $plan['plan_id']; ?>" 
               class="action-btn edit-btn">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button onclick="disablePlan(<?php echo $plan['plan_id']; ?>)" 
                    class="action-btn delete-btn">
                <i class="fas fa-ban"></i> Disable
            </button>
        <?php else: ?>
            <span class="action-btn edit-btn disabled" 
                  style="pointer-events: none; cursor: not-allowed;">
                <i class="fas fa-edit"></i> Edit
            </span>
            <button onclick="enablePlan(<?php echo $plan['plan_id']; ?>)" 
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

        <script>
            // Status Chart (with fixed label positioning)
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
                    cutout: '60%', // Makes the doughnut thinner
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#fff',
                                padding: 20, // Adds space around labels
                                boxWidth: 15, // Makes color boxes smaller
                                font: {
                                    size: 12 // Smaller font size
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
                            top: 20,
                            bottom: 40 // Extra space at bottom for labels
                        }
                    }
                }
            });

            // Popularity Chart (with adjusted spacing)
            const popularityCtx = document.getElementById('popularityChart').getContext('2d');
            const popularityChart = new Chart(popularityCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($popularityData)); ?>,
                    datasets: [{
                        label: 'Number of Members',
                        data: <?php echo json_encode(array_values($popularityData)); ?>,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#fff',
                                stepSize: 1,
                                padding: 10 // Adds space between labels and axis
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#fff',
                                padding: 5 // Adds space between labels and axis
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 10
                        }
                    },
                    barPercentage: 0.6 // Makes bars thinner
                }
            });

            // Filter functionality
            $(document).ready(function() {
                $('#applyFilters').click(function() {
                    const statusFilter = $('#statusFilter').val();
                    const durationFilter = $('#durationFilter').val();

                    $('#plansTable tbody tr').each(function() {
                        const rowStatus = $(this).data('status');
                        const rowDuration = $(this).data('duration');
                        let showRow = true;

                        // Status filter
                        if (statusFilter !== 'all' && rowStatus !== statusFilter) {
                            showRow = false;
                        }

                        // Duration filter
                        if (durationFilter !== 'all' && rowDuration != durationFilter) {
                            showRow = false;
                        }

                        $(this).toggle(showRow);
                    });
                });
            });

            function disablePlan(planId) {
                if (confirm("Are you sure you want to disable this plan?\n\nCurrent members will keep their benefits until their membership expires.")) {
                    window.location.href = "disable-plan.php?id=" + planId;
                }
            }

            function enablePlan(planId) {
                if (confirm("Are you sure you want to enable this plan?\n\nIt will become available for new members immediately.")) {
                    window.location.href = "enable-plan.php?id=" + planId;
                }
            }
        </script>

</body>

</html>