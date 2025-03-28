<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';

$conn = new mysqli("localhost", "root", "", "flexifit_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get content statistics
$stats = [
    'total_content' => 0,
    'guides' => 0,
    'tip' => 0,
    'announcements' => 0,
    'workout_plans' => 0,
    'other' => 0
];

// Get most viewed content
$popularQuery = "SELECT c.content_id, c.title, c.content_type, COUNT(r.review_id) as view_count 
                FROM content c
                LEFT JOIN reviews r ON c.content_id = r.content_id
                GROUP BY c.content_id, c.title, c.content_type
                ORDER BY view_count DESC
                LIMIT 5";
$popularResult = $conn->query($popularQuery);

$popularLabels = [];
$popularData = [];
$popularColors = ['#FFD700', '#5cb85c', '#f0ad4e', '#5bc0de', '#d9534f'];

if ($popularResult->num_rows > 0) {
    while ($row = $popularResult->fetch_assoc()) {
        $popularLabels[] = $row['title'];
        $popularData[] = $row['view_count'];
    }
}

// Fetch all content
$sql = "SELECT * FROM content ORDER BY content_id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['total_content']++;
        // Make sure the content type exists in our stats array
        if (array_key_exists($row['content_type'], $stats)) {
            $stats[$row['content_type']]++;
        } else {
            $stats['other']++; // Fallback for unexpected content types
        }
    }
    $result->data_seek(0); // Reset pointer for main display
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - FlexiFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --primary: #FFD700;
        --primary-dark: #FFC000;
        --dark: #121212;
        --darker: #0A0A0A;
        --light: #F5F5F5;
        --gray: #333333;
        --yellow-glow: 0 0 20px rgba(255, 215, 0, 0.3);
        --success: #5cb85c;
        --warning: #f0ad4e;
        --danger: #d9534f;
        --info: #5bc0de;
        --purple: #bb86fc;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark);
        color: var(--light);
        line-height: 1.6;
        min-height: 100vh;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
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
        color: var(--dark);
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

    /* Analytics Dashboard */
    .analytics-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--darker);
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease;
        border: 1px solid var(--gray);
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
        color: var(--light);
    }

    .stat-guide { color: var(--primary); }
    .stat-tip { color: var(--info); }
    .stat-announcement { color: var(--success); }
    .stat-workout_plan { color: var(--warning); }
    .stat-other { color: var(--purple); }

    /* Charts Section */
    .chart-wrapper {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-container canvas {
    width: 100% !important;
    height: 250px !important;
    display: block; /* Fixes canvas display issues */
    min-height: 250px;
    max-height: 250px;
}

.chart-container {
    flex: 1;
    background: var(--darker);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--gray);
    min-height: 300px;
    max-height: 350px; /* Added max-height constraint */
    position: relative;
    overflow: hidden; /* Prevents content from expanding container */
}

    .chart-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--primary);
    font-size: 18px;
    font-weight: 600;
    text-align: center;
}

    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .content-card {
        background: var(--darker);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        position: relative;
        border: 1px solid var(--gray);
    }

    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    }

    .content-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-bottom: 1px solid var(--gray);
    }

    .content-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
    }

    .badge-guide { background-color: var(--primary); }
    .badge-tip { background-color: var(--info); color: var(--dark); }
    .badge-announcement { background-color: var(--success); }
    .badge-workout_plan { background-color: var(--warning); color: var(--dark); }
    .badge-other { background-color: var(--purple); }

    .content-body {
        padding: 15px;
    }

    .content-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 8px 0;
        color: var(--primary);
    }

    .content-id {
        font-size: 12px;
        color: #aaa;
        margin-bottom: 8px;
    }

    .content-desc {
        font-size: 14px;
        color: #ccc;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .content-actions {
        display: flex;
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
        text-decoration: none;
    }

    .btn-edit {
        background-color: var(--primary);
        color: var(--dark);
    }

    .btn-edit:hover {
        background-color: var(--primary-dark);
    }

    .btn-delete {
        background-color: var(--danger);
        color: white;
    }

    .btn-delete:hover {
        background-color: #c9302c;
    }

    /* Table View */
    .table-container {
        background: var(--darker);
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        margin-bottom: 30px;
        border: 1px solid var(--gray);
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

    .content-table {
        width: 100%;
        border-collapse: collapse;
        color: var(--light);
    }

    .content-table th {
        background-color: var(--gray);
        color: var(--primary);
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid var(--primary);
    }

    .content-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--gray);
        vertical-align: middle;
    }

    .content-table tr:last-child td {
        border-bottom: none;
    }

    .content-table tr:hover {
        background-color: rgba(255, 215, 0, 0.05);
    }

    .table-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }

    .type-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .type-guide { background-color: var(--primary); color: var(--dark); }
    .type-tip { background-color: var(--info); color: var(--dark); }
    .type-announcement { background-color: var(--success); color: white; }
    .type-workout_plan { background-color: var(--warning); color: var(--dark); }
    .type-other { background-color: var(--purple); color: white; }

    .table-actions {
        display: flex;
        gap: 8px;
    }

    /* Responsive adjustments */
   /* Replace your existing chart-related CSS with this */

.chart-wrapper {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    flex: 1;
    background: var(--darker);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--gray);
    min-height: 300px;
    max-height: 350px; /* Added max-height constraint */
    position: relative;
    overflow: hidden; /* Prevents content from expanding container */
}

.chart-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--primary);
    font-size: 18px;
    font-weight: 600;
    text-align: center;
}

/* Fixed chart canvas sizing */
.chart-container canvas {
    width: 100% !important;
    height: 250px !important;
    display: block; /* Fixes canvas display issues */
    min-height: 250px;
    max-height: 250px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chart-wrapper {
        flex-direction: column;
    }
    
    .chart-container {
        min-height: 300px;
        max-height: 300px;
    }
    
    .chart-container canvas {
        height: 250px !important;
    }
}

        .content-grid {
            grid-template-columns: 1fr;
        }

        .top-bar {
            flex-direction: column;
            align-items: flex-start;
        }
    

    @media (max-width: 576px) {
        .analytics-dashboard {
            grid-template-columns: 1fr;
        }
        
        .content-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <h2><i class="fas fa-newspaper"></i> Content Management</h2>
            <div style="display: flex; gap: 10px;">
                <a href="create-content.php" class="btn"><i class="fas fa-plus"></i> Create Content</a>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="analytics-dashboard">
            <div class="stat-card">
                <h3>Total Content</h3>
                <p class="stat-value"><?= $stats['total_content'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Guides</h3>
                <p class="stat-value stat-guide"><?= $stats['guides'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Tips</h3>
                <p class="stat-value stat-tip"><?= $stats['tip'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Announcements</h3>
                <p class="stat-value stat-announcement"><?= $stats['announcements'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Workout Plans</h3>
                <p class="stat-value stat-workout_plan"><?= $stats['workout_plans'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Other</h3>
                <p class="stat-value stat-other"><?= $stats['other'] ?></p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-wrapper">
            <div class="chart-container">
                <h3 class="chart-title">Content Type Distribution</h3>
                <canvas id="typeChart"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="chart-title">Most Viewed Content</h3>
                <canvas id="popularChart"></canvas>
            </div>
        </div>

        <!-- Content Grid View -->
        <div class="content-grid">
            <?php while ($row = $result->fetch_assoc()) : ?>
                <?php
                $type_class = strtolower($row['content_type']);
                $image_path = !empty($row['image']) && file_exists('../uploads/content/' . $row['image']) 
                            ? '../uploads/content/' . htmlspecialchars($row['image']) 
                            : 'https://via.placeholder.com/300x180?text=No+Image';
                ?>
                <div class="content-card">
                    <img src="<?= $image_path ?>" class="content-image" alt="<?= htmlspecialchars($row['title']) ?>">
                    
                    <span class="content-badge badge-<?= $type_class ?>">
                        <?= ucfirst(str_replace('_', ' ', $row['content_type'])) ?>
                    </span>

                    <div class="content-body">
                        <h3 class="content-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="content-id">ID: <?= htmlspecialchars($row['content_id']) ?></p>
                        <p class="content-desc"><?= htmlspecialchars($row['description']) ?></p>

                        <div class="content-actions">
                            <a href="edit-content.php?id=<?= $row['content_id'] ?>" class="action-btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete-content.php?id=<?= $row['content_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this content?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Content Table View -->
        <div class="table-container">
            <h3 class="table-title"><i class="fas fa-table"></i> Content List</h3>
            <table class="content-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); // Reset pointer for table display ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <?php
                        $type_class = strtolower($row['content_type']);
                        $image_path = !empty($row['image']) && file_exists('../uploads/content/' . $row['image']) 
                                    ? '../uploads/content/' . htmlspecialchars($row['image']) 
                                    : 'https://via.placeholder.com/50?text=No+Image';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['content_id']) ?></td>
                            <td><img src="<?= $image_path ?>" class="table-img" alt="<?= htmlspecialchars($row['title']) ?>"></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td>
                                <span class="type-badge type-<?= $type_class ?>">
                                    <?= ucfirst(str_replace('_', ' ', $row['content_type'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : '') ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="edit-content.php?id=<?= $row['content_id'] ?>" class="action-btn btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete-content.php?id=<?= $row['content_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this content?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Type Distribution Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Guides', 'Tips', 'Announcements', 'Workout Plans', 'Other'],
                datasets: [{
                    data: [
                        <?= $stats['guides'] ?>,
                        <?= $stats['tip'] ?>,
                        <?= $stats['announcements'] ?>,
                        <?= $stats['workout_plans'] ?>,
                        <?= $stats['other'] ?>
                    ],
                    backgroundColor: [
                        '#FFD700', // Guide - gold
                        '#5bc0de', // Tip - blue
                        '#5cb85c', // Announcement - green
                        '#f0ad4e', // Workout Plan - orange
                        '#bb86fc'  // Other - purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#f8f8f8'
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // Popular Content Chart
        const popularCtx = document.getElementById('popularChart').getContext('2d');
        const popularChart = new Chart(popularCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($popularLabels) ?>,
                datasets: [{
                    label: 'Views',
                    data: <?= json_encode($popularData) ?>,
                    backgroundColor: <?= json_encode(array_slice($popularColors, 0, count($popularData))) ?>,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
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
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
<?php ob_end_flush(); // At the end of file ?>