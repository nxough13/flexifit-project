<?php
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// neo
if (!isset($_SESSION['user_id']) || !isset($_GET['plan_id'])) {
    header("Location: membership-plans.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$plan_id = $_GET['plan_id'];

// Fetch plan duration
$stmt = $conn->prepare("SELECT duration_days FROM membership_plans WHERE plan_id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$stmt->bind_result($duration_days);
$stmt->fetch();
$stmt->close();

if ($duration_days) {
    // Set start_date and end_date
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+$duration_days days"));

    // Update user to member
    $conn->query("UPDATE users SET user_type = 'member' WHERE user_id = $user_id");

    // Insert into members table
    $stmt = $conn->prepare("INSERT INTO members (user_id, plan_id, start_date, end_date, membership_status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->bind_param("iiss", $user_id, $plan_id, $start_date, $end_date);
    $stmt->execute();
    $stmt->close();

    // Redirect to dashboard
    header("Location: ../index.php");
    exit();
} else {
    echo "Invalid plan.";
}
?>
