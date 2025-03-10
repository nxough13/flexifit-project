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
    <h2>Update Content</h2>
    <form action="update-content.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="content_id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>">


        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="description" placeholder="Description" required>
        <select name="content_type">
            <option value="article">Article</option>
            <option value="video">Video</option>
        </select>
        <input type="file" name="file">
        <input type="file" name="image">
        <button type="submit">Update</button>
    </form>
</div>


<!-- update-content.php -->
<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content_id'])) {
    $content_id = intval($_POST['content_id']);
    if ($content_id == 0) {
        die("Invalid Content ID!");
    }

// neo
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content_type = $_POST['content_type'];


    // Optional file handling
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


    // Update query
    $sql = "UPDATE content SET title=?, description=?, content_type=?, file_path=?, image=? WHERE content_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $title, $description, $content_type, $file_path, $image, $content_id);
    $stmt->execute();
   
    if ($stmt->affected_rows > 0) {
        header("Location: content.php");
        exit();
    } else {
        echo "No changes made or error occurred.";
    }


    $stmt->close();
}


?>
