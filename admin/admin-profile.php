<?php
// neo
session_start();
include '../includes/header.php';
include '../includes/config.php';


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Check if database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get user details from database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);


if (!$stmt) {
    die("Database query failed: " . $conn->error);
}


$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Check if user exists
if (!$user) {
    die("User not found.");
}


// Store user_type in session (if not already set)
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = $user['user_type'];
}


// Check if the user is an admin
// if ($_SESSION['user_type'] !== 'admin') {
//     header("Location: ../index.php"); // Redirect non-admins to homepage
//     exit();
// }


// Default profile picture if none is uploaded
$profile_image = !empty($user['image']) ? "../images/" . htmlspecialchars($user['image']) : "../images/default.png";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile | FlexiFit Gym</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            margin: 0;
            padding: 0;
        }
        .profile-header {
            position: relative;
            width: 100%;
            height: 300px;
            background: url('../images/background.jpg') center/cover no-repeat;
        }
        .profile-info {
            background: yellow;
            padding: 50px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            color: black;
            width: 100%;
        }
        .profile-pic {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 5px solid black;
            background: white;
            position: absolute;
            top: -90px;
            left: 5%;
        }
        .user-info {
            margin-left: 220px;
            text-align: left;
        }
        .user-info h2 {
            margin: 5px 0;
            font-size: 24px;
        }
        .user-info p {
            font-size: 18px;
            font-weight: bold;
        }
        .about-section {
            flex: 1;
            text-align: center;
            padding-right: 5%;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            background: black;
            padding: 40px 5%;
            width: 100%;
        }
        .detail-box label {
            font-weight: bold;
            color: yellow;
            display: block;
        }
        .detail-box input {
            width: 100%;
            padding: 10px;
            background: #333;
            border: none;
            color: white;
            text-align: center;
            border-radius: 5px;
        }
        .edit-btn-container {
            text-align: center;
            padding: 20px;
        }
        .edit-button {
            background: yellow;
            color: black;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            font-size: 16px;
        }
        .edit-button:hover {
            background: orange;
        }
    </style>
</head>
<body>


    <div class="profile-header"></div>


    <div class="profile-info">
        <img src="<?php echo $profile_image; ?>" alt="Profile Picture" class="profile-pic">
       
        <div class="user-info">
            <h2><?php echo strtoupper(htmlspecialchars($user['first_name'] . ' ' . $user['last_name'])); ?></h2>
            <p>ROLE: <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></p>
        </div>


        <div class="about-section">
            <p><strong>About Me:</strong> <?php echo !empty($user['description']) ? htmlspecialchars($user['description']) : "No description available..."; ?></p>
        </div>
    </div>


    <div class="profile-details">
        <div class="detail-box">
            <label>Username:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Email:</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Phone No.:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['phone_number']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Age:</label>
            <input type="number" value="<?php echo htmlspecialchars($user['age']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Gender:</label>
            <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['gender'])); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Height:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['height']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Weight:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['weight']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Weight Goal:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['weight_goal']); ?>" readonly>
        </div>
    </div>


    <div class="edit-btn-container">
        <a href="admin-profile-edit.php" class="edit-button">EDIT</a>
    </div>


    <footer>
        <p style="text-align: center; padding: 10px;">&copy; 2025 FlexiFit Gym. All rights reserved.</p>
    </footer>


</body>
</html>