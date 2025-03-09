<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
include '../includes/header.php';

$_SESSION['id'] = $user_id;
$_SESSION['user_type'] = 'admin'; // or 'member', depending on the user's role
// Check if the user is logged in and is an admin
if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['message'] = "You need to log in first or are not authorized to access this page.";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the data from the form
    $name = $_POST['name'];
    $duration_days = $_POST['duration_days'];
    $price = $_POST['price'];
    $free_training_session = $_POST['free_training_session'];
    $description = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);
    
    // Insert membership plan into the database
    $sql = "INSERT INTO membership_plans (name, duration_days, price, free_training_session, description, image) 
            VALUES ('$name', '$duration_days', '$price', '$free_training_session', '$description', '$image')";
    
    if (mysqli_query($conn, $sql)) {
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        echo "<p style='color:green;'>Membership plan created successfully.</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <br><br><br><br><br><br><br>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Membership Plan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .create-plan-page {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        h1 {
            text-align: center;
            color: #fcd100;
        }

        form {
            background-color: #2c2c2c;
            padding: 20px;
            border-radius: 10px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #fcd100;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #fcd100;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
        }

        textarea {
            resize: none;
        }

        .upload-section {
            margin-bottom: 15px;
        }

        .upload-image-button {
            background-color: #fcd100;
            color: #121212;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .upload-image-button:hover {
            background-color: #ffcc00;
        }

        .uploaded-images-list {
            margin-top: 10px;
            font-size: 14px;
        }

        .form-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .return-button,
        .add-plan-button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .return-button {
            background-color: black;
            color: white;
        }

        .add-plan-button {
            background-color: #fcd100;
            color: black;
        }

        .return-button:hover {
            background-color: #333;
        }

        .add-plan-button:hover {
            background-color: #ffcc00;
        }
    </style>
</head>
<body>
    <div class="create-plan-page">
        <h1>Create New Membership Plan</h1>
        <form action="create-plan.php" method="POST" enctype="multipart/form-data">
            <label for="name">Plan Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="duration_days">Duration (Days):</label>
            <input type="number" id="duration_days" name="duration_days" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" id="price" name="price" required>

            <label for="free_training_session">Free Training Sessions:</label>
            <input type="number" id="free_training_session" name="free_training_session" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>

            <label for="image">Upload Image:</label>
            <input type="file" id="image" name="image" accept=".jpg, .png, .jpeg" required>

            <div class="form-buttons">
                <button type="button" class="return-button" onclick="location.href='index.php'">Return</button>
                <button type="submit" class="add-plan-button">Add Plan</button>
            </div>
        </form>
    </div>
</body>
</html>
