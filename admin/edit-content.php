<?php
session_start();
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check if content ID is provided in URL or form
if (isset($_GET['id'])) {
    $content_id = intval($_GET['id']);
} elseif (isset($_POST['content_id'])) {
    $content_id = intval($_POST['content_id']);
} else {
    die("No content ID provided.");
}


// Fetch existing content details
$sql = "SELECT * FROM content WHERE content_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $content_id);
$stmt->execute();
$result = $stmt->get_result();
$content = $result->fetch_assoc();


if (!$content) {
    die("Content not found!");
}


// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize update variables
    $updates = [];
    $params = [];
    $types = "";


    if (!empty($_POST['title'])) {
        $updates[] = "title = ?";
        $params[] = trim($_POST['title']);
        $types .= "s";
    }
    if (!empty($_POST['description'])) {
        $updates[] = "description = ?";
        $params[] = trim($_POST['description']);
        $types .= "s";
    }
    if (!empty($_POST['content_type'])) {
        $updates[] = "content_type = ?";
        $params[] = $_POST['content_type'];
        $types .= "s";
    }


    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $image = "images/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
        $updates[] = "image = ?";
        $params[] = $image;
        $types .= "s";
    }


    if (!empty($updates)) {
        $sql = "UPDATE content SET " . implode(", ", $updates) . " WHERE content_id = ?";
        $params[] = $content_id;
        $types .= "i";
       
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
       
        if ($stmt->affected_rows > 0) {
            header("Location: content.php");
            exit();
        } else {
            echo "No changes made or error occurred.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Content</title>
    <style>
       body {
    background-color: #000;
    color: #fff;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 50px 0 20px 0; /* More space on top */
}


.container {
    background-color: #111;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0px 0px 15px yellow;
    width: 450px;
    text-align: center;
    border: 2px solid yellow;
    margin-top: 80px; /* Push the form lower */
}


/* Headings */
h2 {
    color: yellow;
    font-size: 22px;
    margin-bottom: 15px;
}


/* Inputs & Select */
input, select {
    width: 95%;
    padding: 12px;
    margin: 12px 0 8px 0; /* More space on top */
    border: 2px solid yellow;
    border-radius: 6px;
    background-color: #222;
    color: #fff;
    font-size: 16px;
    outline: none;
}


/* Button */
button {
    background-color: yellow;
    color: black;
    padding: 12px;
    border: none;
    width: 100%;
    cursor: pointer;
    font-weight: bold;
    border-radius: 6px;
    font-size: 16px;
    transition: 0.3s ease-in-out;
    margin: 20px 0 10px 0; /* More margin on top */
    box-shadow: 0px 4px 10px rgba(255, 255, 0, 0.5);
}


button:hover {
    background-color: #ffaa00;
    box-shadow: 0px 4px 15px rgba(255, 170, 0, 0.8);
}




        img {
            max-width: 100%;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>


<div class="container">
    <h2>Update Content</h2>
    <form action="update-content.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">


        <input type="text" name="title" value="<?php echo htmlspecialchars($content['title']); ?>" placeholder="Title">
        <input type="text" name="description" value="<?php echo htmlspecialchars($content['description']); ?>" placeholder="Description">
       
        <select name="content_type">
            <option value="article" <?php if ($content['content_type'] == 'article') echo 'selected'; ?>>Article</option>
            <option value="video" <?php if ($content['content_type'] == 'video') echo 'selected'; ?>>Video</option>
        </select>


        <p>Current Image:</p>
        <?php if (!empty($content['image'])): ?>
            <img src="<?php echo $content['image']; ?>" alt="Content Image">
        <?php endif; ?>
        <input type="file" name="image">


        <button type="submit">Update</button>
    </form>
</div>


</body>
</html>


<?php


include '../includes/footer.php';
?>
