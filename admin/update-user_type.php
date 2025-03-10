<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Database connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}


// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    die("User ID not specified.");
}


$user_id = intval($_GET['user_id']);


// Fetch user details
$sql = "SELECT user_type FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows == 0) {
    die("User not found.");
}


$user = $result->fetch_assoc();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_user_type = $_POST['user_type'];


    // Update user_type in database
    $update_sql = "UPDATE users SET user_type = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_user_type, $user_id);


    if ($update_stmt->execute()) {
        echo "<script>alert('User type updated successfully!'); window.location.href='view-users.php';</script>";
    } else {
        echo "Error updating user type: " . $conn->error;
    }
}

// neo
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Type</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            color: white;
            text-align: center;
            padding: 50px;
        }
        .container {
            background: #222;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
        }
        select, button {
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }
        button {
            background-color: gold;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User Type</h2>
        <form method="post">
            <label for="user_type">User Type:</label>
            <select name="user_type" id="user_type">
                <option value="non-member" <?= ($user['user_type'] == 'non-member') ? 'selected' : '' ?>>Non-member</option>
                <option value="member" <?= ($user['user_type'] == 'member') ? 'selected' : '' ?>>Member</option>
                <option value="admin" <?= ($user['user_type'] == 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
            <br>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>