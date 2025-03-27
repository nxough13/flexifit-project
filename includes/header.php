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
        $profileLink = ($userType === 'admin') ? "admin-profile.php" : "member-profile.php";
        $logoLink = ($userType === 'admin') ? "index.php" : "index.php";
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


        :root {
            --primary: #FFD700;
            --primary-dark: #e0a800;
            --dark: #222;
            --darker: #111;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 80px; /* To account for fixed header */
        }

        /* Fixed Header Styles */
        header {
            background-color: var(--darker);
            padding: 0 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 80px; /* Fixed height */
            display: flex;
            align-items: center;
        }

        .header-spacer {
    height: 80px; /* Must match header height */
    width: 100%;
}

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: transform 0.3s ease;
            height: 80px; /* Match header height */
        }

        .logo:hover {
            transform: scale(1.03);
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .logo span {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }

        /* Navigation Styles */
        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
            height: 80px; /* Match header height */
        }

        nav ul li {
            display: flex;
            align-items: center;
            height: 100%;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            padding: 0 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            height: 100%;
            position: relative;
        }

        nav a:hover {
            color: var(--primary);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }

        nav a:hover::after {
            width: 100%;
        }

        /* User Controls */
        .user-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            height: 80px; /* Match header height */
        }

        .profile-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .profile-link:hover {
            color: var(--primary);
        }

        .profile-link img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            transition: transform 0.3s ease;
        }

        .profile-link:hover img {
            transform: scale(1.1);
        }

        .login-link, .register-link {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link {
            color: white;
            border: 2px solid var(--primary);
        }

        .login-link:hover {
            background-color: var(--primary);
            color: var(--dark);
        }

        .register-link {
            background-color: var(--primary);
            color: var(--dark);
        }

        .register-link:hover {
            background-color: var(--primary-dark);
        }

        .logout-link {
            color: var(--danger);
            font-weight: 600;
            margin-left: 1rem;
            transition: color 0.3s ease;
        }

        .logout-link:hover {
            color: #ff6b6b;
        }

        /* Mobile Menu Toggle - Hidden since we're not using dropdowns */
        .menu-toggle {
            display: none;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            header {
                padding: 0 1rem;
            }

            nav ul {
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .logo span {
                display: none;
            }

            nav ul {
                gap: 0.75rem;
            }

            .login-link, .register-link {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            header {
                padding: 0 0.5rem;
            }

            nav ul {
                gap: 0.5rem;
            }

            .profile-link span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="<?= $logoLink ?>" class="logo">
                <img src="/flexifit-project/images/flexfit-logo.png" alt="FlexiFit Logo">
                <span>FLEXIFIT GYM</span>
            </a>

            <nav id="main-nav">
                <ul>
                    <li><a href="<?= $logoLink ?>">Home</a></li>
                    
                    <?php if ($user && $user['user_type'] === 'admin'): ?>
                        <!-- Admin Navigation Links -->
                        <li><a href="content.php">Content</a></li>
                        <li><a href="view-trainers.php">Trainers</a></li>
                        <li><a href="view-users.php">Users</a></li>
                        <li><a href="view-plans.php">Plans</a></li>
                        <li><a href="view-equipments.php">Equipment</a></li>
                        <li><a href="view-schedules.php">Schedules</a></li>
                    <?php elseif ($user && $user['user_type'] === 'member'): ?>
                        <!-- Member Navigation Links -->
                        <li><a href="content.php">Content</a></li>
                        <li><a href="view-trainers.php">Trainers</a></li>
                        <li><a href="membership-plans.php">Membership</a></li>
                        <li><a href="view-equipments.php">Equipment</a></li>
                        <li><a href="create-schedule.php">Schedule</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="user-controls">
                <?php if ($user): ?>
                    <a href="<?= $profileLink ?>" class="profile-link">
                        <img src="<?= $user['image'] ? 'images/' . $user['image'] : 'images/default-profile.png'; ?>" 
                             alt="Profile">
                        <span><?= htmlspecialchars($user['first_name']) ?></span>
                    </a>
                    <a href="logout.php" class="logout-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-link">Login</a>
                    <a href="register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="header-spacer"></div>
</body>
</html>