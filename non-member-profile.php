<?php
ob_start(); // Turn on output buffering
session_start();
include 'includes/header.php';
include 'includes/config.php';


// Check if database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
}


// Get user details
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


if (!$user) {
    die("User not found.");
}


// Get membership details if user is a member
$membership = null;
if ($user['user_type'] === 'member') {
    $membership_query = "SELECT m.*, mp.name as plan_name, mp.duration_days
                         FROM members m
                         JOIN membership_plans mp ON m.plan_id = mp.plan_id
                         WHERE m.user_id = ?";
    $membership_stmt = $conn->prepare($membership_query);
    $membership_stmt->bind_param("i", $user_id);
    $membership_stmt->execute();
    $membership_result = $membership_stmt->get_result();
    $membership = $membership_result->fetch_assoc();
}


// Default profile picture if none is uploaded
$profile_image = !empty($user['image']) ? "images/" . htmlspecialchars($user['image']) : "images/default.png";


// Check if medical certificate exists
$medical_certificate = !empty($user['medical_certificate']) ? "../uploads/medical_certificates/" . htmlspecialchars($user['medical_certificate']) : null;
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
            background: url('images/background.jpg') center/cover no-repeat;
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
        .detail-box input, .detail-box textarea {
            width: 100%;
            padding: 10px;
            background: #333;
            border: none;
            color: white;
            text-align: center;
            border-radius: 5px;
        }
        .detail-box textarea {
            min-height: 100px;
            resize: vertical;
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
        .medical-section {
            background: #222;
            padding: 20px 5%;
            margin: 20px 0;
        }
        .medical-certificate {
            max-width: 100%;
            height: auto;
            border: 2px solid yellow;
            margin-top: 10px;
        }
        .no-certificate {
            color: #999;
            font-style: italic;
        }
        .membership-section {
            background: #333;
            padding: 20px 5%;
            margin: 20px 0;
            border-left: 5px solid yellow;
        }
        .membership-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-active {
            background: green;
            color: white;
        }
        .status-expired {
            background: red;
            color: white;
        }
        .status-pending {
            background: orange;
            color: black;
        }
        .certificate-container {
            margin-top: 20px;
        }
        .certificate-info {
            margin-bottom: 10px;
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
            <?php if ($membership): ?>
                <p>MEMBERSHIP: <?php echo htmlspecialchars($membership['plan_name']); ?></p>
            <?php endif; ?>
        </div>


        <div class="about-section">
            <p><strong>About Me:</strong> <?php echo !empty($user['description']) ? htmlspecialchars($user['description']) : "No description available..."; ?></p>
        </div>
    </div>


    <?php if ($membership): ?>
    <div class="membership-section">
        <h2>Membership Details</h2>
        <div class="detail-box">
            <label>Membership Status:</label>
            <span class="membership-status status-<?php echo strtolower($membership['membership_status']); ?>">
                <?php echo ucfirst($membership['membership_status']); ?>
            </span>
        </div>
        <div class="detail-box">
            <label>Plan Name:</label>
            <input type="text" value="<?php echo htmlspecialchars($membership['plan_name']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Start Date:</label>
            <input type="text" value="<?php echo date('F j, Y', strtotime($membership['start_date'])); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>End Date:</label>
            <input type="text" value="<?php echo date('F j, Y', strtotime($membership['end_date'])); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Remaining Free Training Sessions:</label>
            <input type="text" value="<?php echo htmlspecialchars($membership['free_training_session']); ?>" readonly>
        </div>
    </div>
    <?php endif; ?>


    <div class="profile-details">
        <div class="detail-box">
            <label>Username:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Email:</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Phone Number:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['phone_number']); ?>" readonly>
        </div>
        <div class="detail-box">
            <label>Birthdate:</label>
            <input type="text" value="<?php echo date('F j, Y', strtotime($user['birthdate'])); ?>" readonly>
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
            <label>Address:</label>
            <input type="text" value="<?php echo htmlspecialchars($user['address']); ?>" readonly>
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


    <!-- Medical Information Section -->
    <div class="medical-section">
        <h2>Medical Information</h2>
       
        <div class="detail-box">
            <label>Medical Conditions:</label>
            <textarea readonly><?php echo !empty($user['medical_conditions']) ? htmlspecialchars($user['medical_conditions']) : 'No medical conditions reported'; ?></textarea>
        </div>
       
        <div class="certificate-container">
            <div class="certificate-info">
                <label>Medical Certificate:</label>
                <?php if ($medical_certificate): ?>
                    <p>You have uploaded a medical certificate.</p>
                    <a href="<?php echo $medical_certificate; ?>" target="_blank" class="edit-button" style="display: inline-block; margin-top: 10px;">View Certificate</a>
                <?php else: ?>
                    <p class="no-certificate">No medical certificate uploaded</p>
                <?php endif; ?>
            </div>
           
            <?php if ($medical_certificate): ?>
                <div style="margin-top: 20px;">
                    <img src="<?php echo $medical_certificate; ?>" alt="Medical Certificate Preview" class="medical-certificate">
                    <p style="margin-top: 10px;">Certificate preview - click "View Certificate" above to see full version</p>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <div class="edit-btn-container">
        <a href="non-member-profile-edit.php" class="edit-button">EDIT PROFILE</a>
    </div>


    <footer>
        <p style="text-align: center; padding: 10px;">&copy; 2025 FlexiFit Gym. All rights reserved.</p>
    </footer>


</body>
</html>
<?php ob_end_flush(); // At the end of file ?>