<?php
session_start();
include '../includes/header.php';




// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Default profile picture if none is uploaded
$profile_image = !empty($user['image']) ? "../images/" . $user['image'] : "images/default-profile.png";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile | FlexiFit Gym</title>
   
    <style>
        /* Fix header at the top */
        header {
    position: fixed;
    top: 0;
    left: 0;
    width: 97%;
    height: 60px; 
    background: black;
    z-index: 1000;
    padding: 10px 20px;
    display: flex;
    align-items: center; 
}


/* Adjust body to prevent content from hiding under header */
body {
    padding-top: 90px; /* Adjust based on header height */
}


        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }


        .profile-container {
    display: flex;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
    width: 96%; /* Increased width */
    max-width: 1080px; /* 20% larger than 900px */
    box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.2);
}


        /* Left Side (Image & Text) */
        .profile-left {
            flex: 1;
            background: url('../images/background.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }


        .profile-left h2 {
            font-size: 24px;
            font-weight: bold;
            color: yellow;
            margin-top: 15px;
        }


        .profile-left h2 span {
            color: white;
        }


        /* Right Side (Profile Info) */
        .profile-right {
            flex: 1;
            background: #222;
            padding: 55px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }


        .profile-picture img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 3px solid yellow;
            margin-bottom: 20px;
        }


        .profile-details label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: yellow;
            margin-bottom: 5px;
        }


        .profile-details input {
            width: 100%;
            padding: 8px;
            background: #333;
            border: none;
            color: white;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }


        .edit-button {
            display: inline-block;
            text-align: center;
            width: 100%;
            padding: 10px;
            background: yellow;
            color: black;
            text-transform: uppercase;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
            transition: 0.3s ease;
        }


        .edit-button:hover {
            background: orange;
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }


            .profile-left {
                padding: 30px;
            }


            .profile-right {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Left Section -->
        <div class="profile-left">
            <h2>BE FIT <br> <span>BE STRONGER</span></h2>
        </div>


        <!-- Right Section -->
        <div class="profile-right">
            <div class="profile-picture">
                <img src="<?php echo $profile_image; ?>" alt="Profile Picture">
            </div>
            <div class="profile-details">
                <label>USERNAME</label>
                <input type="text" value="<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>" readonly>
               
                <label>AGE</label>
                <input type="number" value="<?php echo $user['age']; ?>" readonly>
               
                <label>GENDER</label>
                <input type="text" value="<?php echo ucfirst($user['gender']); ?>" readonly>
               
                <label>EMAIL</label>
                <input type="email" value="<?php echo $user['email']; ?>" readonly>
               
                <label>PHONE NO.</label>
                <input type="text" value="<?php echo $user['phone_number']; ?>" readonly>
               
                <a href="member-profile-edit.php" class="edit-button">EDIT</a>
            </div>
        </div>
    </div>
  
</body>

<?php 
    include '../includes/footer.php';
?>
</html>
