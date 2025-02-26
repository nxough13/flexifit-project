<?php
// Connect to database
session_start();
include '../includes/header.php';


$conn = new mysqli("localhost", "root", "", "flexifit_db");


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content_type = $_POST['content_type'];
   
    // Handle image upload
    $image = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $image = "uploads/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }


    // Insert into database
    $sql = "INSERT INTO content (title, description, content_type, image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $title, $description, $content_type, $image);
    $stmt->execute();
   
    // Redirect to content page
    header("Location: content.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Content</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #111;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px yellow;
            width: 350px;
            text-align: center;
        }
        h2 {
            color: yellow;
        }
        input, select {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid yellow;
            border-radius: 5px;
            background-color: #222;
            color: #fff;
        }
        button {
            background-color: yellow;
            color: black;
            padding: 10px;
            border: none;
            width: 100%;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
        }
        button:hover {
            background-color: #ffaa00;
        }
    </style>
</head>
<body>


<div class="container">
    <h2>Create Content</h2>
    <form action="create-content.php" method="post" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="description" placeholder="Description" required>
        <select name="content_type">
            <option value="article">Article</option>
            <option value="video">Video</option>
        </select>
        <input type="file" name="image">
        <button type="submit">Create</button>
    </form>
</div>


</body>
</html>




<?php


include '../includes/footer.php';
?>
