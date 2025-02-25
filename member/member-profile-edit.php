<?php
session_start();
include '../includes/config.php';
include '../includes/header.php';




// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$message = "";


// Fetch user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Default profile image
$profile_image = !empty($user['image']) ? "../images/" . $user['image'] : "images/default-profile.png";


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];


    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../images/";
        $image_name = basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);


        // Update database with new image
        $update_query = "UPDATE users SET first_name=?, last_name=?, phone_number=?, age=?, gender=?, image=? WHERE user_id=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone_number, $age, $gender, $image_name, $user_id);
    } else {
        // Update database without changing image
        $update_query = "UPDATE users SET first_name=?, last_name=?, phone_number=?, age=?, gender=? WHERE user_id=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $first_name, $last_name, $phone_number, $age, $gender, $user_id);
    }


    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        header("Location: member-profile.php");
        exit();
    } else {
        $message = "Error updating profile!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | FlexiFit Gym</title>
    <link rel="stylesheet" href="../styles.css"> <!-- Using the same CSS -->
    <style>
        body {
            background-color: #0d0d0d;
            color: white;
            font-family: Arial, sans-serif;
        }
        .profile-container {
            width: 50%;
            margin: auto;
            padding: 20px;
            background-color: #1c1c1c;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(255, 255, 255, 0.1);
        }
        .profile-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-picture img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid yellow;
            object-fit: cover;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
            color: yellow;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: none;
            background-color: #2c2c2c;
            color: white;
            border-radius: 5px;
            outline: none;
        }
        input[type="file"] {
            border: none;
            background: none;
            padding: 5px;
        }
        .edit-button {
            display: block;
            width: 100%;
            text-align: center;
            background-color: yellow;
            color: black;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
        }
        .edit-button:hover {
            background-color: #ffcc00;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <div class="profile-content">
            <div class="profile-picture">
                <img src="<?php echo $profile_image; ?>" alt="Profile Picture">
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <label>Upload Profile Picture:</label>
                <input type="file" name="image">


                <label>First Name:</label>
                <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" required>


                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" required>


                <label>Phone Number:</label>
                <input type="text" name="phone_number" value="<?php echo $user['phone_number']; ?>" required>


                <label>Age:</label>
                <input type="number" name="age" value="<?php echo $user['age']; ?>" required>


                <label>Gender:</label>
                <select name="gender" required>
                    <option value="male" <?php if ($user['gender'] == 'male') echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if ($user['gender'] == 'female') echo 'selected'; ?>>Female</option>
                </select>


                <button type="submit" class="edit-button">Save</button>
            </form>
        </div>
    </div>
</body>
</html>
