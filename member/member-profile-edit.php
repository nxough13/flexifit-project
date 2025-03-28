<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';
include '../includes/config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();

}

// neo
// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


if (!$user) {
    die("User not found.");
}


// Default profile picture
$profile_image = !empty($user['image']) ? "../images/" . htmlspecialchars($user['image']) : "../images/default.png";


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $height = isset($_POST['height']) ? trim($_POST['height']) : '';
    $weight = isset($_POST['weight']) ? trim($_POST['weight']) : '';
    $weight_goal = isset($_POST['weight_goal']) ? trim($_POST['weight_goal']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';


    // Handle profile picture upload
    if (!empty($_FILES["profile_image"]["name"])) {
        $target_dir = "../images/";
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]); // Prevent duplicate file names
        $target_file = $target_dir . $image_name;


        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $image_name;
            $update_image = "UPDATE users SET image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_image);
            $stmt->bind_param("si", $profile_image, $user_id);
            $stmt->execute();
        }
    }




    $check_column_query = "SHOW COLUMNS FROM users LIKE 'description'";
    $result = $conn->query($check_column_query);
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN description TEXT");
    }


    // Update user info
    $update_query = "UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, age=?, gender=?, height=?, weight=?, weight_goal=?, description=? WHERE user_id=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssisssisi", $first_name, $last_name, $email, $phone_number, $age, $gender, $height, $weight, $weight_goal, $description, $user_id);


    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location='member-profile.php';</script>";
    } else {
        echo "<script>alert('Update failed. Please try again.');</script>";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | FlexiFit Gym</title>
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
            border-radius: 5px;
        }
        .edit-btn-container {
            text-align: center;
            padding: 20px;
        }
        .save-button {
            background: yellow;
            color: black;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }
        .save-button:hover {
            background: orange;
        }
        .profile-pic-preview {
            display: block;
            margin-top: 10px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 2px solid yellow;
        }
    </style>
</head>
<body>


    <div class="profile-header"></div>


    <div class="profile-info">
        <img src="<?php echo $profile_image; ?>" alt="Profile Picture" class="profile-pic" id="profilePreview">
       
        <form method="POST" enctype="multipart/form-data" style="width: 100%;">
            <div class="user-info">
                <label for="username" style="font-size: 20px; font-weight: bold;">Username:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required style="font-size: 24px; font-weight: bold;">
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required style="font-size: 24px; font-weight: bold;">
                <p style="font-size: 20px; font-weight: bold;">ROLE: <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></p>
            </div>


            <div class="about-section">
                <label for="description" style="font-size: 20px; font-weight: bold;"><strong>About Me:</strong></label>
                <input type="text" name="description" value="<?php echo !empty($user['description']) ? htmlspecialchars($user['description']) : ""; ?>" style="font-size: 18px; width: 80%;">
            </div>


    </div>


    <form method="POST" enctype="multipart/form-data">
        <div class="profile-details">
            <div class="detail-box">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="detail-box">
                <label>Phone No.:</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
            </div>
            <div class="detail-box">
                <label>Age:</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" required>
            </div>
            <div class="detail-box">
    <label style="font-size: 20px;">Gender:</label>
    <select name="gender" required style="width: 100%; padding: 12px; font-size: 18px; background: #333; color: white; border: none; border-radius: 5px;">
        <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
        <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
        <option value="Others" <?php echo ($user['gender'] == 'Others') ? 'selected' : ''; ?>>Others</option>
    </select>
</div>
            <div class="detail-box">
                <label>Height:</label>
                <input type="text" name="height" value="<?php echo htmlspecialchars($user['height']); ?>" required>
            </div>
            <div class="detail-box">
                <label>Weight:</label>
                <input type="text" name="weight" value="<?php echo htmlspecialchars($user['weight']); ?>" required>
            </div>
            <div class="detail-box">
                <label>Weight Goal:</label>
                <input type="text" name="weight_goal" value="<?php echo htmlspecialchars($user['weight_goal']); ?>" required>
            </div>
            <div class="detail-box">
                <label>Profile Picture:</label>
                <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)">
            </div>
        </div>
       
       


        <div class="edit-btn-container">
            <button type="submit" class="save-button">SAVE CHANGES</button>
        </div>
    </form>


    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                document.getElementById('profilePreview').src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>


</body>
</html>
<?php ob_end_flush(); // At the end of file ?>