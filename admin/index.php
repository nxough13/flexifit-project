<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "flexifit_db";
include '../includes/header.php';

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

// Fetch new memberships in the past week
$new_members_week_query = "SELECT COUNT(*) AS new_members_week FROM members WHERE start_date >= CURDATE() - INTERVAL 1 WEEK";
$new_members_week_result = mysqli_query($conn, $new_members_week_query);
$new_members_week = mysqli_fetch_assoc($new_members_week_result)['new_members_week'];

// Fetch new memberships in the past month
$new_members_month_query = "SELECT COUNT(*) AS new_members_month FROM members WHERE start_date >= CURDATE() - INTERVAL 1 MONTH";
$new_members_month_result = mysqli_query($conn, $new_members_month_query);
$new_members_month = mysqli_fetch_assoc($new_members_month_result)['new_members_month'];

// Fetch membership status distribution
$status_distribution_query = "SELECT membership_status, COUNT(*) AS count FROM members GROUP BY membership_status";
$status_distribution_result = mysqli_query($conn, $status_distribution_query);
$status_distribution = [];
while ($row = mysqli_fetch_assoc($status_distribution_result)) {
    $status_distribution[$row['membership_status']] = $row['count'];
}

// New Analytics
// Fetch members with medical condition (yes/no)
$medical_condition_query = "SELECT COUNT(*) AS medical_condition_yes FROM users WHERE medical_condition = 'yes'";
$medical_condition_result = mysqli_query($conn, $medical_condition_query);
$medical_condition_yes = mysqli_fetch_assoc($medical_condition_result)['medical_condition_yes'];

// Fetch members without medical condition (no)
$medical_condition_no_query = "SELECT COUNT(*) AS medical_condition_no FROM users WHERE medical_condition = 'no'";
$medical_condition_no_result = mysqli_query($conn, $medical_condition_no_query);
$medical_condition_no = mysqli_fetch_assoc($medical_condition_no_result)['medical_condition_no'];

// Fetch gender distribution (male, female, other)
$gender_distribution_query = "SELECT gender, COUNT(*) AS count FROM users WHERE user_type = 'member' GROUP BY gender";
$gender_distribution_result = mysqli_query($conn, $gender_distribution_query);
$gender_distribution = ['male' => 0, 'female' => 0, 'other' => 0];
while ($row = mysqli_fetch_assoc($gender_distribution_result)) {
    $gender_distribution[$row['gender']] = $row['count'];
}

// Function to get image path
function getImagePath($image) {
    $uploadPath = "../images/";
    return !empty($image) && file_exists($uploadPath . $image) 
        ? htmlspecialchars($uploadPath . $image) 
        : htmlspecialchars($uploadPath . "placeholder.png");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FlexiFit</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #222;
            color: #FFD700;
            text-align: center;
        }
        .container {
            width: 95%;
            margin: auto;
        }
        .top-buttons {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .top-buttons a {
            padding: 10px 20px;
            background-color: #FFD700;
            color: #222;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
        }
        .dashboard-box {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .box {
            width: 48%;
            padding: 20px;
            background-color: #333;
            border-radius: 10px;
            box-shadow: 3px 3px 10px rgba(255, 215, 0, 0.5);
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        canvas {
            max-width: 100% !important;
            height: 250px !important;  /* Adjusted height */
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.5);
        }

        /* Membership Overview Styles */
        .membership-overview-container {
            margin-top: 30px;
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 3px 3px 10px rgba(255, 215, 0, 0.5);
        }
        .first-row, .second-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        /* Payment Overview Styles */
        .payment-overview-container {
            margin-top: 30px;
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 3px 3px 10px rgba(255, 215, 0, 0.5);
        }
        .first-row, .second-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }
        .analytics-card {
            width: 48%;
            padding: 20px;
            background-color: #333;
            color: #FFD700;
            text-align: center;
            border-radius: 10px;
            box-shadow: 3px 3px 10px rgba(255, 215, 0, 0.5);
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

            <!-- First Row: Large Graphs -->
            <div class="first-row">
                <div class="analytics-card">
                    <h3>Total Members and Non-Members</h3>
                    <canvas id="totalMembersNonMembersChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>New Members (This Week/Month)</h3>
                    <canvas id="newMembersLineChart"></canvas>
                </div>
            </div>

            <!-- Second Row: Smaller Charts -->
            <div class="second-row">
                <div class="analytics-card">
                    <h3>Membership Status</h3>
                    <canvas id="statusDistributionChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>Member Medical Conditions</h3>
                    <canvas id="medicalConditionChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>Gender Distribution</h3>
                    <canvas id="genderDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Overview Analytics -->
        <div class="payment-overview-container">
            <h2>Payment Overview</h2>

            <!-- First Row -->
            <div class="first-row">
                <div class="analytics-card">
                    <h3>Total Revenue</h3>
                    <canvas id="totalRevenueChart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3>Payment Breakdown</h3>
                    <canvas id="paymentBreakdownChart"></canvas>
                </div>
            </div>

            <!-- Second Row -->
            <div class="second-row">
                <div class="analytics-card">
                    <h3>Most Popular Plan</h3>
                    <canvas id="mostPopularPlanChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Total Members and Non-Members - Bar Chart
        var totalMembersNonMembersChart = new Chart(document.getElementById("totalMembersNonMembersChart"), {
            type: 'bar',
            data: {
                labels: ['Members', 'Non-Members'],
                datasets: [{
                    label: 'Total Members vs Non-Members',
                    data: [<?php echo $total_members; ?>, <?php echo $non_members; ?>],
                    backgroundColor: 'rgba(255, 215, 0, 0.6)',
                    borderColor: 'rgba(255, 215, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // New Members (This Week/Month) - Line Chart
        var newMembersLineChart = new Chart(document.getElementById("newMembersLineChart"), {
            type: 'line',
            data: {
                labels: ['This Week', 'This Month'],
                datasets: [{
                    label: 'New Members',
                    data: [<?php echo $new_members_week; ?>, <?php echo $new_members_month; ?>],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
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
                    label: 'Membership Status',
                    data: [
                        <?php echo isset($status_distribution['active']) ? $status_distribution['active'] : 0; ?>,
                        <?php echo isset($status_distribution['expired']) ? $status_distribution['expired'] : 0; ?>,
                        <?php echo isset($status_distribution['pending']) ? $status_distribution['pending'] : 0; ?>
                    ],
                    backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 159, 64, 0.6)'],
                    borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)', 'rgba(255, 159, 64, 1)'],
                    borderWidth: 1
                }]
            }
        });

        // Member Medical Conditions - Pie Chart
        var medicalConditionChart = new Chart(document.getElementById("medicalConditionChart"), {
            type: 'pie',
            data: {
                labels: ['Medical Condition (Yes)', 'Medical Condition (No)'],
                datasets: [{
                    label: 'Medical Conditions',
                    data: [<?php echo $medical_condition_yes; ?>, <?php echo $medical_condition_no; ?>],
                    backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(153, 102, 255, 0.6)'],
                    borderColor: ['rgba(54, 162, 235, 1)', 'rgba(153, 102, 255, 1)'],
                    borderWidth: 1
                }]
            }
        });

        // Gender Distribution - Pie Chart
        var genderDistributionChart = new Chart(document.getElementById("genderDistributionChart"), {
            type: 'pie',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    label: 'Gender Distribution',
                    data: [
                        <?php echo $gender_distribution['male']; ?>,
                        <?php echo $gender_distribution['female']; ?>,
                        <?php echo $gender_distribution['other']; ?>
                    ],
                    backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)'],
                    borderColor: ['rgba(54, 162, 235, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
                    borderWidth: 1
                }]
            }
        });

        // Total Revenue - Line Chart
        var totalRevenueChart = new Chart(document.getElementById("totalRevenueChart"), {
            type: 'line',
            data: {
                labels: ['2025-03-16', '2025-03-17'],
                datasets: [{
                    label: 'Total Revenue',
                    data: [700, 1400],  // Example data, replace with dynamic data from DB
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    fill: true,
                }]
            }
        });

        // Payment Breakdown - Pie Chart
        var paymentBreakdownChart = new Chart(document.getElementById("paymentBreakdownChart"), {
            type: 'pie',
            data: {
                labels: ['Successful', 'Pending', 'Failed'],
                datasets: [{
                    label: 'Payment Breakdown',
                    data: [50, 1, 0],  // Example data, replace with dynamic data from DB
                    backgroundColor: ['rgba(0, 123, 255, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(255, 99, 132, 0.6)'],
                    borderColor: ['rgba(0, 123, 255, 1)', 'rgba(255, 159, 64, 1)', 'rgba(255, 99, 132, 1)'],
                    borderWidth: 1
                }]
            }
        });

        // Most Popular Plan - Donut Chart
        var mostPopularPlanChart = new Chart(document.getElementById("mostPopularPlanChart"), {
            type: 'doughnut',
            data: {
                labels: ['7-Days Plan'],  // Example, replace dynamically with most popular plan
                datasets: [{
                    label: 'Most Popular Plan',
                    data: [100],  // Example data, replace with dynamic data from DB
                    backgroundColor: ['rgba(255, 99, 132, 0.6)'],
                    borderColor: ['rgba(255, 99, 132, 1)'],
                    borderWidth: 1
                }]
            }
        });
    </script>
</body>
</html>