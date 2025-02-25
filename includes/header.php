<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Correct the path dynamically
$base_path = __DIR__; // Gets the directory of the current file
require_once $base_path . '/config.php'; // Ensures the correct path

// Check if user is logged in and fetch user details
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT first_name, image FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>

<style>
    header {
        position: fixed;
        top: 0;
        left: 0;
        width: 97.6%;
        background: black;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 1000; /* Ensures it stays on top of other elements */
        box-shadow: 0px 4px 8px rgba(255, 255, 255, 0.1);
    }

    body {
        padding-top: 80px; /* Adjust this to prevent content from being hidden behind the fixed header */
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

    nav ul {
        list-style: none;
        display: flex;
        gap: 20px;
        margin: 0;
        padding: 0;
    }

    nav ul li {
        display: inline;
    }

    nav ul li a {
        text-decoration: none;
        color: white;
        font-weight: bold;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-info a {
        text-decoration: none;
        color: white;
        font-weight: bold;
        margin-left: 15px;
    }

    .user-info img {
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
        margin-right: 15px;
    }

    .profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    margin-right: 15px; /* Adds spacing between username and logout */
}

.profile-link span {
    font-style: italic;
    font-weight: bold;
    color: yellow; /* Sets username color to yellow */
    margin-left: 5px; /* Adds spacing between image and username */
}

.user-info a.logout-link {
    text-decoration: none;
    color: red;
    font-weight: bold;
}
</style>

<header>
    <div class="logo">
        <a href="/flexifit-project/index.php">
            <img src="/flexifit-project/images/flexfit-logo.png" alt="FlexiFit Logo">
        </a>
        <span>FLEXIFIT GYM</span>
    </div>
    <nav>
        <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#offers">Offers</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
    </nav>

    <div class="user-info">
    <?php if ($user): ?>
        <a href="/flexifit-project/member/member-profile.php" class="profile-link">
            <img src="<?= $user['image'] ? '/flexifit-project/images/' . $user['image'] : '/flexifit-project/images/default.png'; ?>" 
                 alt="Profile" style="width: 30px; height: 30px; border-radius: 50%;">
            <span><?= htmlspecialchars($user['first_name']) ?></span>
        </a>
        <a href="/flexifit-project/logout.php" class="logout-link">Logout</a>
        <?php else: ?>
            <a href="/flexifit-project/login.php">Login</a>
            <a href="/flexifit-project/register.php">Register</a>
        <?php endif; ?>
    </div>
</header>
