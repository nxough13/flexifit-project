<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $upload_dir = "uploads/";

    // Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image = $_FILES['image']['name'];
    $target_file = $upload_dir . basename($image);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image type and size
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif ($_FILES['image']['size'] > 5000000) { // 5MB limit
        $error = "File size is too large. Maximum allowed size is 5MB.";
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Insert data into the database
            $stmt = $conn->prepare("INSERT INTO equipment (name, description, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $description, $image);

            if ($stmt->execute()) {
                $success = "Equipment added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $error = "Error uploading file.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(0, 0, 0);
            margin: 20px;
        }
        .container {
            width: 50%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(11, 11, 11, 0.1);
            text-align: center;
        }
        h2 {
            color: #FFFFFF;
        }
        label {
            color: white;
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            cursor: pointer;
            background-color: rgb(255, 215, 0);
            border: none;
            display: inline-block;
            margin: 10px;
        }
        .message {
            font-weight: bold;
            margin-bottom: 15px;
            color: green;
        }
        .error {
            font-weight: bold;
            margin-bottom: 15px;
            color: red;
        }
        .btn-container {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Equipment</h2>
    
    <?php if (isset($success)) : ?>
        <p class="message"><?= $success ?></p>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label for="name">Equipment Name:</label>
        <input type="text" name="name" id="name" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <label for="image">Upload Image:</label>
        <input type="file" name="image" id="image" accept="image/*" required>

        <button type="submit" class="btn">Add Equipment</button>
    </form>

    <div class="btn-container">
        <a href="view-equipments.php" class="btn">View Equipment List</a>
    </div>
</div>

</body>
</html>
