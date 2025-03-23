<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Correct the path dynamically
$base_path = __DIR__; // Gets the directory of the current file
$config_path = $base_path . '/config.php'; // Path to config.php


// Check if the config file exists
if (file_exists($config_path)) {
    require_once $config_path; // Include the config file
} else {
    die("Config file not found. Please check the path: " . $config_path);
}


// Check if user is logged in and fetch user details
$user = null;
$profileLink = "login.php"; // Default profile link for non-logged-in users
$logoLink = "index.php"; // Default logo link for non-logged-in users


if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT first_name, image, user_type FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();


    // Set profile link and logo link based on user type
if ($user) {
    $userType = $user['user_type'];
    $profileLink = ($userType === 'admin') ? "admin/admin-profile.php" : "member/member-profile.php";
    $logoLink = ($userType === 'admin') ? "admin/index.php" : "index.php";
} else {
    // Default for non-logged-in users
    $profileLink = "login.php";
    $logoLink = "index.php";
}


}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlexiFit Gym</title>
    <style>
        /* Add some basic styling for the dropdown */
        nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }


        nav ul li {
            position: relative;
        }


        nav ul li ul {
            display: none;
            position: absolute;
            background: black;
            padding: 10px;
            list-style: none;
            margin: 0;
            top: 100%;
            left: 0;
            min-width: 150px;
        }


        nav ul li:hover ul {
            display: block;
        }


        nav ul li ul li {
            margin: 10px 0;
        }


        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: black;
            padding: 15px 30px;
        }


        .logo {
            display: flex;
            align-items: center;
        }


        .logo img {
            height: 50px;
        }


        .logo span {
            font-weight: bold;
            font-size: 20px;
            margin-left: 10px;
            color: white;
        }


        nav a {
            text-decoration: none;
            color: white;
            font-weight: bold;
        }


        .profile-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }


        .profile-link img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }


        .logout-link {
            text-decoration: none;
            color: red;
            font-weight: bold;
            margin-left: 15px;
        }


        .login-link, .register-link {
            text-decoration: none;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="<?= $logoLink ?>">
                <img src="../images/flexfit-logo.png" alt="FlexiFit Logo">
            </a>
            <a href="<?= $logoLink ?>">
                <span>FLEXIFIT GYM</span>
            </a>
        </div>
        <nav>
            <ul>
            <li style="display: inline;"><a href="/flexifit-project/index.php#home" style="text-decoration: none; color: white; font-weight: bold;">Home</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#about" style="text-decoration: none; color: white; font-weight: bold;">About</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#offers" style="text-decoration: none; color: white; font-weight: bold;">Offers</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#contact" style="text-decoration: none; color: white; font-weight: bold;">Contact</a></li>
                <?php if ($user && ($user['user_type'] === 'member' || $user['user_type'] === 'admin')): ?>
                    <li>
                        <a href="#">More</a>
                        <ul>
                        <li style="display: inline;"><a href="/flexifit-project/member/content.php" style="text-decoration: none; color: white; font-weight: bold;">Contents</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/view-trainers.php" style="text-decoration: none; color: white; font-weight: bold;">Trainers</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/membership-plans.php" style="text-decoration: none; color: white; font-weight: bold;">Membership</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/view-equipments.php" style="text-decoration: none; color: white; font-weight: bold;">Equipments</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/create-schedule.php" style="text-decoration: none; color: white; font-weight: bold;"> Set Schedule</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>


        <div style="display: flex; align-items: center;">
        <?php if ($user): ?>
            <a href="<?= $profileLink ?>" style="display: flex; align-items: center; text-decoration: none; color: white; font-weight: bold;">
                <img src="<?= $user['image'] ? '/flexifit-project/images/' . $user['image'] : '/flexifit-project/images/default-profile.png'; ?>"
                     alt="Profile" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                <span style="font-style: italic;"><?= htmlspecialchars($user['first_name']) ?></span>
            </a>
            <a href="/flexifit-project/logout.php" style="text-decoration: none; color: red; font-weight: bold; margin-left: 15px;">Logout</a>
        <?php else: ?>
            <a href="/flexifit-project/login.php" style="text-decoration: none; color: white; font-weight: bold; margin-right: 15px;">Login</a>
            <a href="/flexifit-project/register.php" style="text-decoration: none; color: white; font-weight: bold;">Register</a>
        <?php endif; ?>
    </div>
    </header>
</body>
</html>