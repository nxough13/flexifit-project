<?php

$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $specialty = $_POST["specialty"];
    $availability_status = $_POST["availability_status"];
    $status = "active"; // Default status

    // Handle Image Upload
    $image = "default.png"; // Default image
    if (!empty($_FILES["image"]["name"])) {
        $image = basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $image);
    }

    // Insert into database
    $sql = "INSERT INTO trainers (first_name, last_name, email, age, gender, specialty, availability_status, status, image) 
            VALUES ('$first_name', '$last_name', '$email', '$age', '$gender', '$specialty', '$availability_status', '$status', '$image')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Trainer added successfully!'); window.location.href = 'view-trainers.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trainer</title>
    <style>
      body {
    font-family: Arial, sans-serif;
    background-color: #121212;
    color: yellow;
    text-align: center;
    padding: 20px;
}

.container {
    max-width: 700px;
    margin: auto;
    background: #1e1e1e;
    padding: 20px;
    border-radius: 8px;
    color: yellow;
    border: 2px solid yellow;
    box-shadow: 0 0 15px rgba(255, 255, 0, 0.8);
}

/* Title */
h2 {
    color: yellow;
    text-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
    margin-bottom: 20px;
}

/* Labels */
label {
    font-weight: bold;
    display: block;
    margin: 10px 0 5px;
}

/* Inputs and Selects */
input, select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid yellow;
    border-radius: 4px;
    background: #1e1e1e;
    color: yellow;
}

/* Buttons */
.btn {
    background: yellow;
    color: black;
    padding: 10px;
    border: none;
    cursor: pointer;
    width: 100%;
    border-radius: 4px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
}

.btn:hover {
    background: black;
    color: yellow;
    border: 2px solid yellow;
    box-shadow: 0 0 15px rgba(255, 255, 0, 1);
}

/* Back to List */
a {
    color: yellow;
    text-decoration: none;
}

a:hover {
    text-shadow: 0 0 8px yellow;
}
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Trainer</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>First Name:</label>
        <input type="text" name="first_name" required>
        
        <label>Last Name:</label>
        <input type="text" name="last_name" required>
        
        <label>Email:</label>
        <input type="email" name="email" required>
        
        <label>Age:</label>
        <input type="number" name="age" min="18" required>
        
        <label>Gender:</label>
        <select name="gender" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select>
        
        <label>Specialty:</label>
        <input type="text" name="specialty" required>
        
        <label>Availability Status:</label>
        <select name="availability_status" required>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
        </select>
        
        <label>Profile Image:</label>
        <input type="file" name="image">
        
        <button type="submit" class="btn">Add Trainer</button>
        <a href="view-trainers.php" class="back-btn">← Back to List</a>
    </form>
</div>

</body>
</html>
