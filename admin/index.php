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

// Fetch all users for the table
$users_query = "SELECT user_id, first_name, last_name, email, user_type, image FROM users ORDER BY user_id DESC";
$users_result = mysqli_query($conn, $users_query);

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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #222;
            color: #FFD700;
            text-align: center;
        }
        .container {
            width: 80%;
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
        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            background-color: #333;
            color: #FFD700;
        }
        th, td {
            padding: 10px;
            border: 1px solid #FFD700;
        }
        th {
            background-color: #444;
        }
        td img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Admin Dashboard</h1>

        <div class="top-buttons">
            <a href="view-trainers.php">Trainers Catalog</a>
            <a href="content.php">Content Catalog</a>
            <a href="view-users.php">Members Catalog</a>
            <a href="view-equipments.php">Equipment Catalog</a>
        </div>

        <div class="dashboard-box">
            <!-- Latest Member -->
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

            <!-- Admin Info -->
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

        <!-- Users Table -->
        <h2>Users Catalog</h2>
        <table>
            <thead>
                <tr>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($users_result)): ?>
                    <tr>
                        <td><img src="<?= getImagePath($row['image']) ?>" alt="User Image"></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['user_type']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
