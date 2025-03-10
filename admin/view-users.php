<?php
session_start();
include "../includes/header.php";
$conn = new mysqli("localhost", "root", "", "flexifit_db");



// neo
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}


// Fetch all user information (Selecting columns one by one)
$sql = "SELECT user_id, first_name, last_name, email, phone_number, password, user_type, image,
               age, gender, created_at, username, birthdate, address, height, weight, weight_goal,
               medical_condition, medical_conditions, description
        FROM users";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            margin: 20px;
            color: white;
        }
        .container {
            width: 90%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: #FFD700;
        }
        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            background-color: rgb(255, 215, 0);
            border: none;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            justify-content: center;
        }
        .user-card {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: black;
            width: 320px;
            margin: auto;
        }
        .user-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .user-info {
            text-align: left;
            font-size: 14px;
        }
        .see-more {
            background-color: #007bff;
            padding: 6px 10px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            font-size: 13px;
            cursor: pointer;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>


<div class="container">
    <div class="top-bar">
        <h2>Users List</h2>
        <a href="index.php" class="btn">Home</a>
    </div>


    <div class="grid-container">
        <?php
        if ($result->num_rows > 0) :
            while ($row = $result->fetch_assoc()) : ?>
                <div class="user-card">
                    <?php
                    $imageFileName = !empty($row['image']) ? htmlspecialchars($row['image']) : null;
                    $imageFolder = '../images/';
                    $imagePath = ($imageFileName && file_exists($imageFolder . $imageFileName))
                        ? $imageFolder . $imageFileName
                        : $imageFolder . 'default.png';
                    ?>
                    <img src="<?= $imagePath ?>" alt="Profile Image">


                    <div class="user-info">
                        <p><strong>User ID:</strong> <?= htmlspecialchars($row['user_id']) ?></p>
                        <p><strong>First Name:</strong> <?= htmlspecialchars($row['first_name']) ?></p>
                        <p><strong>Last Name:</strong> <?= htmlspecialchars($row['last_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                        <p><strong>Phone Number:</strong> <?= htmlspecialchars($row['phone_number']) ?></p>


                        <!-- Hidden Details -->
                        <div class="hidden more-info">
                        <p>
    <strong>User Type:</strong>
    <span id="userType-<?= $row['user_id'] ?>"><?= htmlspecialchars($row['user_type']) ?></span>
   
    <a href="update-user_type.php?user_id=<?= $row['user_id'] ?>" class="btn" style="padding: 5px 10px; font-size: 12px; margin-left: 10px;">
        Edit
    </a>
</p>




                            <p><strong>Age:</strong> <?= htmlspecialchars($row['age']) ?></p>
                            <p><strong>Gender:</strong> <?= htmlspecialchars($row['gender']) ?></p>
                            <p><strong>Created At:</strong> <?= htmlspecialchars($row['created_at']) ?></p>
                            <p><strong>Username:</strong> <?= htmlspecialchars($row['username']) ?></p>
                            <p><strong>Birthdate:</strong> <?= htmlspecialchars($row['birthdate']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($row['address']) ?></p>
                            <p><strong>Height:</strong> <?= htmlspecialchars($row['height']) ?> cm</p>
                            <p><strong>Weight:</strong> <?= htmlspecialchars($row['weight']) ?> kg</p>
                            <p><strong>Weight Goal:</strong> <?= htmlspecialchars($row['weight_goal']) ?> kg</p>
                            <p><strong>Medical Condition:</strong> <?= htmlspecialchars($row['medical_condition']) ?></p>
                            <p><strong>Medical Conditions:</strong> <?= htmlspecialchars($row['medical_conditions']) ?></p>
                            <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                        </div>


                        <!-- Toggle Button -->
                        <span class="see-more" onclick="toggleDetails(this)">See more...</span>
                    </div>
                </div>
            <?php endwhile;
        else :
            echo "<p style='color: red; text-align: center;'>No users found in the database.</p>";
        endif;
        ?>
    </div>
</div>


<script>
    function toggleDetails(button) {
        var moreInfo = button.previousElementSibling; // Get the hidden details
        if (moreInfo.classList.contains('hidden')) {
            moreInfo.classList.remove('hidden'); // Show details
            button.textContent = "See less...";
        } else {
            moreInfo.classList.add('hidden'); // Hide details
            button.textContent = "See more...";
        }
    }
</script>


</body>
</html>


<?php $conn->close(); ?>