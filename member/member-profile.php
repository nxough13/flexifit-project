<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

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
$profile_image = !empty($user['image']) ? "uploads/" . $user['image'] : "assets/default-profile.png";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile | FlexiFit Gym</title>
    <link rel="stylesheet" href="styles.css"> <!-- External CSS file -->
</head>
<body>

    <div class="profile-container">
        <h2>User Profile</h2>
        <div class="profile-content">
            <div class="profile-picture">
                <img src="<?php echo $profile_image; ?>" alt="Profile Picture">
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="profile_image" accept="image/*">
                    <button type="submit">Upload</button>
                </form>
            </div>
            <div class="profile-details">
                <form action="update-profile.php" method="POST">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>" readonly>
                    
                    <label>Password:</label>
                    <input type="password" name="password" placeholder="Enter new password (optional)">
                    
                    <label>First Name:</label>
                    <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                    
                    <label>Last Name:</label>
                    <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                    
                    <label>Phone Number:</label>
                    <input type="text" name="phone_number" value="<?php echo $user['phone_number']; ?>" required>
                    
                    <label>Age:</label>
                    <input type="number" name="age" value="<?php echo $user['age']; ?>">
                    
                    <label>Gender:</label>
                    <select name="gender">
                        <option value="male" <?php echo ($user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($user['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                    
                    <button type="submit" name="update_profile">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
