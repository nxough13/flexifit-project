<?php
ob_start(); // Turn on output buffering
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "flexifit_db";
include '../includes/header.php';
require_once('../vendor/autoload.php'); // For TCPDF
use TCPDF as TCPDF;
// Create connection
$conn = new mysqli($servername, $username, $password, $database);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['user_type'] == 'guest') {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['user_type'] == 'member') {
    header("Location: ../member/index.php");
    exit();
}

// Fetch latest active member
$latest_member_query = "
    SELECT u.first_name, u.last_name, u.image, mp.name AS plan_name 
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    JOIN membership_plans mp ON m.plan_id = mp.plan_id
    WHERE m.membership_status = 'active'
    ORDER BY m.start_date DESC
    LIMIT 1
";
$latest_member = mysqli_fetch_assoc(mysqli_query($conn, $latest_member_query));

// Fetch logged-in admin details
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT first_name, last_name, image, email FROM users WHERE user_id = '$admin_id' AND user_type = 'admin'";
$admin = mysqli_fetch_assoc(mysqli_query($conn, $admin_query));

// Fetch total number of members and non-members
$total_members_query = "SELECT COUNT(*) AS total_members FROM members";
$total_members_result = mysqli_query($conn, $total_members_query);
$total_members = mysqli_fetch_assoc($total_members_result)['total_members'];

$non_members_query = "SELECT COUNT(*) AS non_members FROM users WHERE user_type = 'non-member'";
$non_members_result = mysqli_query($conn, $non_members_query);
$non_members = mysqli_fetch_assoc($non_members_result)['non_members'];

// Fetch membership status distribution
$status_distribution_query = "SELECT membership_status, COUNT(*) AS count FROM members GROUP BY membership_status";
$status_distribution_result = mysqli_query($conn, $status_distribution_query);
$status_distribution = [];
while ($row = mysqli_fetch_assoc($status_distribution_result)) {
    $status_distribution[$row['membership_status']] = $row['count'];
}

// Fetch gender distribution (male, female, other)
$gender_distribution_query = "SELECT gender, COUNT(*) AS count FROM users WHERE user_type = 'member' GROUP BY gender";
$gender_distribution_result = mysqli_query($conn, $gender_distribution_query);
$gender_distribution = ['male' => 0, 'female' => 0, 'other' => 0];
while ($row = mysqli_fetch_assoc($gender_distribution_result)) {
    $gender_distribution[$row['gender']] = $row['count'];
}

// Function to get image path
function getImagePath($image)
{
    $uploadPath = "../images/";
    return !empty($image) && file_exists($uploadPath . $image)
        ? htmlspecialchars($uploadPath . $image)
        : htmlspecialchars($uploadPath . "placeholder.png");
}

// Query to fetch Most Booked Equipment
$most_booked_equipment_query = "
    SELECT e.name, COUNT(s.inventory_id) AS bookings_count
    FROM schedules s
    JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
    JOIN equipment e ON ei.equipment_id = e.equipment_id
    GROUP BY s.inventory_id
    ORDER BY bookings_count DESC
    LIMIT 5
";
$most_booked_equipment_result = mysqli_query($conn, $most_booked_equipment_query);
$most_booked_equipment = mysqli_fetch_all($most_booked_equipment_result, MYSQLI_ASSOC);

// Query to fetch Highest Rated Trainer
$highest_rated_trainer_query = "
    SELECT t.trainer_id, CONCAT(t.first_name, ' ', t.last_name) AS trainer_name, 
           AVG(tr.rating) AS avg_rating, COUNT(tr.trainer_id) AS total_reviews
    FROM trainer_reviews tr
    JOIN trainers t ON tr.trainer_id = t.trainer_id
    GROUP BY tr.trainer_id
    HAVING COUNT(tr.trainer_id) >= 3
    ORDER BY avg_rating DESC
    LIMIT 5
";
$highest_rated_trainer_result = mysqli_query($conn, $highest_rated_trainer_query);
$highest_rated_trainer = mysqli_fetch_all($highest_rated_trainer_result, MYSQLI_ASSOC);

// Query to fetch Day with Most Schedules
$most_scheduled_day_query = "
    SELECT DATE(date) AS schedule_date, COUNT(*) AS schedule_count
    FROM schedules
    GROUP BY DATE(date)
    ORDER BY schedule_count DESC
    LIMIT 1
";
$most_scheduled_day_result = mysqli_query($conn, $most_scheduled_day_query);
$most_scheduled_day = mysqli_fetch_all($most_scheduled_day_result, MYSQLI_ASSOC);

// Query to fetch Highest Rated Content
$highest_rated_content_query = "
    SELECT c.content_id, c.title, 
           AVG(r.rating) AS avg_rating, COUNT(r.review_id) AS review_count
    FROM content c
    LEFT JOIN reviews r ON c.content_id = r.content_id
    GROUP BY c.content_id
    HAVING COUNT(r.review_id) >= 3
    ORDER BY avg_rating DESC
    LIMIT 5
";
$highest_rated_content_result = mysqli_query($conn, $highest_rated_content_query);
$highest_rated_content = mysqli_fetch_all($highest_rated_content_result, MYSQLI_ASSOC);

// [Add these new queries after your existing queries]

// Get daily member registration data (for line graph)
$daily_members_query = "
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM users 
    WHERE user_type = 'member'
    GROUP BY DATE(created_at)
    ORDER BY date
";
$daily_members = mysqli_fetch_all(mysqli_query($conn, $daily_members_query), MYSQLI_ASSOC);

// Get daily non-member registration data
$daily_non_members_query = "
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM users 
    WHERE user_type = 'non-member'
    GROUP BY DATE(created_at)
    ORDER BY date
";
$daily_non_members = mysqli_fetch_all(mysqli_query($conn, $daily_non_members_query), MYSQLI_ASSOC);

// Get trainers count over time
$daily_trainers_query = "
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM trainers 
    GROUP BY DATE(created_at)
    ORDER BY date
";
$daily_trainers = mysqli_fetch_all(mysqli_query($conn, $daily_trainers_query), MYSQLI_ASSOC);

// Get equipment count over time
$daily_equipment_query = "
    SELECT DATE(added_date) AS date, COUNT(*) AS count 
    FROM equipment_inventory 
    GROUP BY DATE(added_date)
    ORDER BY date
";
$daily_equipment = mysqli_fetch_all(mysqli_query($conn, $daily_equipment_query), MYSQLI_ASSOC);

// Get content count over time
$daily_content_query = "
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM content 
    GROUP BY DATE(created_at)
    ORDER BY date
";
$daily_content = mysqli_fetch_all(mysqli_query($conn, $daily_content_query), MYSQLI_ASSOC);

// Get reviews count over time
$daily_reviews_query = "
    SELECT DATE(review_date) AS date, COUNT(*) AS count 
    FROM reviews 
    GROUP BY DATE(review_date)
    ORDER BY date
";
$daily_reviews = mysqli_fetch_all(mysqli_query($conn, $daily_reviews_query), MYSQLI_ASSOC);

// Get feedback count over time
$daily_feedback_query = "
    SELECT DATE(feedback_date) AS date, COUNT(*) AS count 
    FROM feedback 
    GROUP BY DATE(feedback_date)
    ORDER BY date
";
$daily_feedback = mysqli_fetch_all(mysqli_query($conn, $daily_feedback_query), MYSQLI_ASSOC);

// Get schedules count over time
$daily_schedules_query = "
    SELECT DATE(date) AS date, COUNT(*) AS count 
    FROM schedules 
    GROUP BY DATE(date)
    ORDER BY date
";
$daily_schedules = mysqli_fetch_all(mysqli_query($conn, $daily_schedules_query), MYSQLI_ASSOC);

// Get current totals for summary cards
$total_trainers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM trainers"))['count'];
$total_equipment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM equipment_inventory"))['count'];
$total_content = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM content"))['count'];
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM reviews"))['count'];
$total_feedback = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM feedback"))['count'];
$total_schedules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM schedules"))['count'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FlexiFit</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #e0a800;
            --dark: #222;
            --darker: #111;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark);
            color: var(--primary);
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: 0 auto;
            padding: 20px 0;
        }

        h1,
        h2,
        h3 {
            color: var(--primary);
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }

        .top-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .top-buttons a {
            padding: 12px 0;
            background-color: var(--primary);
            color: var(--dark);
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
        }

        .top-buttons a:hover {
            background-color: var(--dark);
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(255, 215, 0, 0.2);
        }

        .dashboard-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .box {
            padding: 20px;
            background-color: rgba(51, 51, 51, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.1);
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.2);
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: block;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .analytics-card {
            padding: 20px;
            background-color: rgba(51, 51, 51, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.2);
        }

        .analytics-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary);
        }

        canvas {
            width: 100% !important;
            height: auto !important;
            max-height: 300px;
            margin: 0 auto;
        }

        .note {
            font-size: 12px;
            color: var(--gray);
            margin-top: 10px;
            font-style: italic;
        }

        @media (max-width: 768px) {

            .dashboard-box,
            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .top-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .top-buttons {
                grid-template-columns: 1fr;
            }
        }

        .report-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .report-modal-content {
            background-color: var(--darker);
            margin: 5% auto;
            padding: 20px;
            border: 1px solid var(--primary);
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
        }

        .report-option {
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(51, 51, 51, 0.5);
            border-radius: 5px;
        }

        .date-range-selector {
            margin: 15px 0;
            padding: 10px;
            background-color: rgba(51, 51, 51, 0.5);
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Comprehensive Metrics Styles */
        .comprehensive-metrics {
            margin-top: 40px;
            padding: 20px;
            background-color: rgba(51, 51, 51, 0.8);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--primary);
        }

        .summary-card h3 {
            margin-top: 0;
            font-size: 1rem;
            color: var(--primary);
        }

        .summary-value {
            font-size: 2rem;
            font-weight: bold;
            color: white;
        }

        .growth-section {
            margin-bottom: 30px;
        }

        .growth-section h3 {
            border-bottom: 1px solid var(--primary);
            padding-bottom: 10px;
        }

        .detailed-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .metric-chart {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .metric-chart h3 {
            margin-top: 0;
            font-size: 1rem;
            color: var(--primary);
        }

        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .detailed-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Welcome to Admin Dashboard</h1>

        <div class="top-buttons">
            <a href="view-trainers.php">Trainers Catalog</a>
            <a href="content.php">Content Catalog</a>
            <a href="view-users.php">Users Catalog</a>
            <a href="view-equipments.php">Equipment Catalog</a>
            <a href="view-plans.php">Plans Catalog</a>
            <a href="view-schedules.php">View Schedules</a>
        </div>

        <!-- Latest Registered Member -->
        <div class="dashboard-box">
            <div class="box">
                <h2>Latest Registered Member</h2>
                <?php if ($latest_member): ?>
                    <img src="<?= getImagePath($latest_member['image']) ?>" class="profile-img" alt="Member Image">
                    <p><strong><?= $latest_member['first_name'] . ' ' . $latest_member['last_name'] ?></strong></p>
                    <p>Plan: <?= $latest_member['plan_name'] ?></p>
                <?php else: ?>
                    <p>No recent active members.</p>
                <?php endif; ?>
            </div>

            <!-- Admin Profile -->
            <div class="box">
                <h2>Admin Profile</h2>
                <?php if ($admin): ?>
                    <img src="<?= getImagePath($admin['image']) ?>" class="profile-img" alt="Admin Image">
                    <p><strong><?= $admin['first_name'] . ' ' . $admin['last_name'] ?></strong></p>
                    <p>Email: <?= $admin['email'] ?></p>
                <?php else: ?>
                    <p>Admin details not found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Membership Overview Analytics -->
        <div class="membership-overview-container">
            <h2>Membership Overview</h2>

            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Members and Non-Members</h3>
                    <canvas id="totalMembersNonMembersChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>Membership Status</h3>
                    <canvas id="statusDistributionChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>Gender Distribution</h3>
                    <canvas id="genderDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Trend Analytics -->
        <div class="trend-analytics-container">
            <h2>Trend Analytics</h2>

            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Most Booked Equipment</h3>
                    <canvas id="mostBookedEquipmentChart"></canvas>
                    <p class="note">Top 5 most booked equipment</p>
                </div>

                <div class="analytics-card">
                    <h3>Highest Rated Trainer</h3>
                    <canvas id="highestRatedTrainerChart"></canvas>
                    <p class="note">Trainers with highest average ratings (min. 3 reviews)</p>
                </div>

                <div class="analytics-card">
                    <h3>Day with Most Schedules</h3>
                    <canvas id="mostScheduledDayChart"></canvas>
                    <p class="note">Day with highest number of scheduled bookings</p>
                </div>

                <div class="analytics-card">
                    <h3>Highest Rated Content</h3>
                    <canvas id="highestRatedContentChart"></canvas>
                    <p class="note">Content with highest average ratings (min. 3 reviews)</p>
                </div>
            </div>
        </div>
    </div>

    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <h2>Generate Analytics Report</h2>

            <form id="reportForm" action="generate_report.php" method="post">
                <div class="form-group">
                    <label for="reportTitle">Report Title</label>
                    <input type="text" class="form-control" id="reportTitle" name="reportTitle" required>
                </div>

                <h3>Select Analytics to Include</h3>

                <div class="report-option">
                    <input type="checkbox" id="includeMembers" name="analytics[]" value="members" checked>
                    <label for="includeMembers">Membership Overview</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeStatus" name="analytics[]" value="status" checked>
                    <label for="includeStatus">Membership Status</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeGender" name="analytics[]" value="gender" checked>
                    <label for="includeGender">Gender Distribution</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeEquipment" name="analytics[]" value="equipment">
                    <label for="includeEquipment">Most Booked Equipment</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeTrainers" name="analytics[]" value="trainers">
                    <label for="includeTrainers">Highest Rated Trainers</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeContent" name="analytics[]" value="content">
                    <label for="includeContent">Highest Rated Content</label>
                </div>

                <!-- Add these new checkboxes to your report modal form -->
                <div class="report-option">
                    <input type="checkbox" id="includeTrainerGrowth" name="analytics[]" value="trainer_growth">
                    <label for="includeTrainerGrowth">Trainer Growth</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeEquipmentGrowth" name="analytics[]" value="equipment_growth">
                    <label for="includeEquipmentGrowth">Equipment Additions</label>
                </div>

                <div class="report-option">
                    <input type="checkbox" id="includeContentGrowth" name="analytics[]" value="content_growth">
                    <label for="includeContentGrowth">Content Additions</label>
                </div>

                <div class="date-range-selector">
                    <h3>Date Range Filter</h3>
                    <div class="form-row">
                        <div class="col">
                            <label for="startDate">From</label>
                            <input type="text" class="form-control datepicker" id="startDate" name="startDate">
                        </div>
                        <div class="col">
                            <label for="endDate">To</label>
                            <input type="text" class="form-control datepicker" id="endDate" name="endDate">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reportNotes">Additional Notes</label>
                    <textarea class="form-control" id="reportNotes" name="reportNotes" rows="3"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Comprehensive Metrics Section -->
    <div class="comprehensive-metrics">
        <h2>System-Wide Metrics</h2>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Trainers</h3>
                <div class="summary-value"><?= $total_trainers ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Equipment</h3>
                <div class="summary-value"><?= $total_equipment ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Content</h3>
                <div class="summary-value"><?= $total_content ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Reviews</h3>
                <div class="summary-value"><?= $total_reviews ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Feedback</h3>
                <div class="summary-value"><?= $total_feedback ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Schedules</h3>
                <div class="summary-value"><?= $total_schedules ?></div>
            </div>
        </div>

        <!-- Growth Over Time Section -->
        <div class="growth-section">
            <h3>Growth Over Time</h3>
            <div class="chart-container">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

        <!-- Detailed Metrics Section -->
        <div class="detailed-metrics">
            <div class="metric-chart">
                <h3>Member Registrations</h3>
                <canvas id="memberGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Non-Member Registrations</h3>
                <canvas id="nonMemberGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Trainer Additions</h3>
                <canvas id="trainerGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Equipment Additions</h3>
                <canvas id="equipmentGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Content Additions</h3>
                <canvas id="contentGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Daily Reviews</h3>
                <canvas id="reviewGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Trainer Feedback</h3>
                <canvas id="feedbackGrowthChart"></canvas>
            </div>
            <div class="metric-chart">
                <h3>Scheduled Sessions</h3>
                <canvas id="scheduleGrowthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Add report buttons to each analytics section -->
    <div class="report-buttons" style="margin: 20px 0; text-align: center;">
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-file-pdf"></i> Generate Full Report
        </button>
    </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        // Modal functions
        function openModal() {
            document.getElementById('reportModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        // Initialize datepicker
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('reportModal')) {
                closeModal();
            }
        }
    </script>
    <script>
        // Total Members and Non-Members - Bar Chart
        var totalMembersNonMembersChart = new Chart(document.getElementById("totalMembersNonMembersChart"), {
            type: 'bar',
            data: {
                labels: ['Members', 'Non-Members'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $total_members; ?>, <?php echo $non_members; ?>],
                    backgroundColor: 'rgba(255, 215, 0, 0.7)',
                    borderColor: 'rgba(255, 215, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Membership Status (Active, Expired, Pending) - Pie Chart
        var statusDistributionChart = new Chart(document.getElementById("statusDistributionChart"), {
            type: 'pie',
            data: {
                labels: ['Active', 'Expired', 'Pending'],
                datasets: [{
                    data: [
                        <?php echo isset($status_distribution['active']) ? $status_distribution['active'] : 0; ?>,
                        <?php echo isset($status_distribution['expired']) ? $status_distribution['expired'] : 0; ?>,
                        <?php echo isset($status_distribution['pending']) ? $status_distribution['pending'] : 0; ?>
                    ],
                    backgroundColor: ['#17a2b8', '#e83e8c', '#FFD700'],
                    borderColor: ['#111', '#111', '#111'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Gender Distribution - Pie Chart
        var genderDistributionChart = new Chart(document.getElementById("genderDistributionChart"), {
            type: 'pie',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [
                        <?php echo $gender_distribution['male']; ?>,
                        <?php echo $gender_distribution['female']; ?>,
                        <?php echo $gender_distribution['other']; ?>
                    ],
                    backgroundColor: ['#007bff', '#28a745', '#6f42c1'],
                    borderColor: ['#111', '#111', '#111'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Most Booked Equipment
        var mostBookedEquipmentData = <?php echo json_encode($most_booked_equipment); ?>;
        var mostBookedEquipmentChart = new Chart(document.getElementById("mostBookedEquipmentChart"), {
            type: 'bar',
            data: {
                labels: mostBookedEquipmentData.map(item => item.name),
                datasets: [{
                    label: 'Bookings',
                    data: mostBookedEquipmentData.map(item => item.bookings_count),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Highest Rated Trainer
        var highestRatedTrainerData = <?php echo json_encode($highest_rated_trainer); ?>;
        var highestRatedTrainerChart = new Chart(document.getElementById("highestRatedTrainerChart"), {
            type: 'bar',
            data: {
                labels: highestRatedTrainerData.map(item => item.trainer_name),
                datasets: [{
                    label: 'Average Rating',
                    data: highestRatedTrainerData.map(item => item.avg_rating),
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF',
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const trainer = highestRatedTrainerData[context.dataIndex];
                                return `Reviews: ${trainer.total_reviews}`;
                            }
                        }
                    }
                }
            }
        });

        // Day with Most Schedules
        var mostScheduledDayData = <?php echo json_encode($most_scheduled_day); ?>;
        var mostScheduledDayChart = new Chart(document.getElementById("mostScheduledDayChart"), {
            type: 'bar',
            data: {
                labels: mostScheduledDayData.map(item => item.schedule_date),
                datasets: [{
                    label: 'Schedules',
                    data: mostScheduledDayData.map(item => item.schedule_count),
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Highest Rated Content
        var highestRatedContentData = <?php echo json_encode($highest_rated_content); ?>;
        var highestRatedContentChart = new Chart(document.getElementById("highestRatedContentChart"), {
            type: 'bar',
            data: {
                labels: highestRatedContentData.map(item => item.title),
                datasets: [{
                    label: 'Average Rating',
                    data: highestRatedContentData.map(item => item.avg_rating),
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF',
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const content = highestRatedContentData[context.dataIndex];
                                return `Reviews: ${content.review_count}`;
                            }
                        }
                    }
                }
            }


        });

        // Combined Growth Chart
        var growthCtx = document.getElementById('growthChart').getContext('2d');
        var growthChart = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($daily_members, 'date')) ?>,
                datasets: [{
                        label: 'Members',
                        data: <?= json_encode(array_column($daily_members, 'count')) ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'Non-Members',
                        data: <?= json_encode(array_column($daily_non_members, 'count')) ?>,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'Trainers',
                        data: <?= json_encode(array_column($daily_trainers, 'count')) ?>,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: true,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'System Growth Over Time',
                        color: '#FFD700'
                    },
                    legend: {
                        labels: {
                            color: '#FFF'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#FFF'
                        }
                    }
                }
            }
        });

        // Individual Metric Charts
        function createMetricChart(elementId, label, dates, counts, color) {
            var ctx = document.getElementById(elementId).getContext('2d');
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: label,
                        data: counts,
                        backgroundColor: color,
                        borderColor: color.replace('0.2', '1'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#FFF'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#FFF'
                            }
                        }
                    }
                }
            });
        }

        // Create all individual charts
        createMetricChart(
            'memberGrowthChart',
            'Member Registrations',
            <?= json_encode(array_column($daily_members, 'date')) ?>,
            <?= json_encode(array_column($daily_members, 'count')) ?>,
            'rgba(75, 192, 192, 0.7)'
        );

        createMetricChart(
            'nonMemberGrowthChart',
            'Non-Member Registrations',
            <?= json_encode(array_column($daily_non_members, 'date')) ?>,
            <?= json_encode(array_column($daily_non_members, 'count')) ?>,
            'rgba(153, 102, 255, 0.7)'
        );

        createMetricChart(
            'trainerGrowthChart',
            'Trainer Additions',
            <?= json_encode(array_column($daily_trainers, 'date')) ?>,
            <?= json_encode(array_column($daily_trainers, 'count')) ?>,
            'rgba(255, 159, 64, 0.7)'
        );

        createMetricChart(
            'equipmentGrowthChart',
            'Equipment Additions',
            <?= json_encode(array_column($daily_equipment, 'date')) ?>,
            <?= json_encode(array_column($daily_equipment, 'count')) ?>,
            'rgba(54, 162, 235, 0.7)'
        );

        createMetricChart(
            'contentGrowthChart',
            'Content Additions',
            <?= json_encode(array_column($daily_content, 'date')) ?>,
            <?= json_encode(array_column($daily_content, 'count')) ?>,
            'rgba(255, 99, 132, 0.7)'
        );

        createMetricChart(
            'reviewGrowthChart',
            'Content Reviews',
            <?= json_encode(array_column($daily_reviews, 'date')) ?>,
            <?= json_encode(array_column($daily_reviews, 'count')) ?>,
            'rgba(255, 206, 86, 0.7)'
        );

        createMetricChart(
            'feedbackGrowthChart',
            'Trainer Feedback',
            <?= json_encode(array_column($daily_feedback, 'date')) ?>,
            <?= json_encode(array_column($daily_feedback, 'count')) ?>,
            'rgba(75, 192, 192, 0.7)'
        );

        createMetricChart(
            'scheduleGrowthChart',
            'Scheduled Sessions',
            <?= json_encode(array_column($daily_schedules, 'date')) ?>,
            <?= json_encode(array_column($daily_schedules, 'count')) ?>,
            'rgba(153, 102, 255, 0.7)'
        );
    </script>
</body>

</html>
<?php ob_end_flush(); // At the end of file ?>
