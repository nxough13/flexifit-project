<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Correct the path dynamically
$base_path = __DIR__; // Gets the directory of the current file
require_once $base_path . '../config.php'; // Ensures the correct path

// Check if user is logged in and fetch user details
$user = null;
$profileLink = "../login.php"; // Default profile link for non-logged-in users

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT first_name, image, user_type FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Set profile link based on user type
    if ($user) {
        $userType = $user['user_type'];
        $profileLink = ($userType === 'admin') ? "/flexifit-project/admin/admin-profile.php" : "/flexifit-project/member/member-profile.php";
    }
}
?>

<header style="display: flex; justify-content: space-between; align-items: center; background: black; padding: 15px 30px;">
    <div class="logo" style="display: flex; align-items: center;">
        <a href="/flexifit-project/index.php">
            <img src="/flexifit-project/images/flexfit-logo.png" alt="FlexiFit Logo" style="height: 50px;">
        </a>
        <span style="font-weight: bold; font-size: 20px; margin-left: 10px; color: white;">FLEXIFIT GYM</span>
    </div>
    <nav>
        <ul style="list-style: none; display: flex; gap: 20px; margin: 0; padding: 0;">
            <li style="display: inline;"><a href="/flexifit-project/index.php#home" style="text-decoration: none; color: white; font-weight: bold;">Home</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#about" style="text-decoration: none; color: white; font-weight: bold;">About</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#offers" style="text-decoration: none; color: white; font-weight: bold;">Offers</a></li>
            <li style="display: inline;"><a href="/flexifit-project/index.php#contact" style="text-decoration: none; color: white; font-weight: bold;">Contact</a></li>
            <?php if ($user && ($user['user_type'] === 'member' || $user['user_type'] === 'admin')): ?>
                <li style="display: inline;"><a href="/flexifit-project/member/view-content.php" style="text-decoration: none; color: white; font-weight: bold;">Contents</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/view-trainers.php" style="text-decoration: none; color: white; font-weight: bold;">Trainers</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/membership-plans.php" style="text-decoration: none; color: white; font-weight: bold;">Membership</a></li>
                <li style="display: inline;"><a href="/flexifit-project/member/view-equipments.php" style="text-decoration: none; color: white; font-weight: bold;">Equipments</a></li>
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