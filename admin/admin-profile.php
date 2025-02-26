<?php
session_start();
include '../includes/header.php';
include_once 'C:/xampp/htdocs/flexifit-project/includes/config.php';


// Debugging: Check if $conn exists
if (!isset($conn)) {
    die("Database connection is not established. Check your config file.");
}


// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}


$user_id = $_SESSION['user_id'];


// Fetch admin details from the database
$sql = "SELECT first_name, last_name, email, age, gender, phone_number, image, created_at FROM users WHERE user_id = ? AND user_type = 'admin'";
$stmt = $conn->prepare($sql);


if (!$stmt) {
    die("Database query failed: " . $conn->error);
}


$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();
$conn->close();


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <style>
        /* Fix header at the top */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: black;
            z-index: 1000;
            padding: 10px 20px;
        }


        /* Adjust body to prevent content from hiding under header */
        body {
            padding-top: 60px;
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
       
        body {
    min-height: 100vh; /* Ensure body takes full viewport height */
    display: flex;
    flex-direction: column;
}


.profile-container {
    flex: 1; /* Allows content to expand and push the footer down */
}




        .profile-container {
            display: flex;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            width: 80%;
            max-width: 900px;
            box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.2);
        }


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


        .profile-right {
            flex: 1;
            background: #222;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }


        .profile-picture img {
            width: 100px;
            height: 100px;
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
        <div class="profile-left">
            <h2>BE FIT <br> <span>BE STRONGER</span></h2>
        </div>
        <div class="profile-right">
            <div class="profile-picture">
                <img src="../images/<?php echo $admin['image'] ? $admin['image'] : 'default-profile.png'; ?>" alt="Profile Picture">
            </div>
            <div class="profile-details">
                <label>NAME</label>
                <input type="text" value="<?php echo $admin['first_name'] . ' ' . $admin['last_name']; ?>" readonly>
                <label>AGE</label>
                <input type="number" value="<?php echo $admin['age']; ?>" readonly>
                <label>GENDER</label>
                <input type="text" value="<?php echo ucfirst($admin['gender']); ?>" readonly>
                <label>EMAIL</label>
                <input type="email" value="<?php echo $admin['email']; ?>" readonly>
                <label>PHONE NO.</label>
                <input type="text" value="<?php echo $admin['phone_number']; ?>" readonly>
                <a href="admin-profile-edit.php" class="edit-button">EDIT</a>
            </div>
        </div>
    </div>


</body>
</html>


<?php include '../includes/footer.php'; ?>