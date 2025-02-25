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


<div class="container">
    <h2>Create Content</h2>
    <form action="create-content.php" method="post" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="description" placeholder="Description" required>
        <select name="content_type">
            <option value="article">Article</option>
            <option value="video">Video</option>
        </select>
        <input type="file" name="file">
        <input type="file" name="image">
        <button type="submit">Create</button>
    </form>
</div>


<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content_type = $_POST['content_type'];
    $admin_id = 1;
   
    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_name = basename($_FILES['file']['name']);
        $file_path = "uploads/" . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }
   
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $image = "images/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }
   
    $sql = "INSERT INTO content (admin_id, title, description, content_type, file_path, image) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $admin_id, $title, $description, $content_type, $file_path, $image);
    $stmt->execute();
    $stmt->close();
    header("Location: content.php");
    exit();
}
?>
