<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check if 'id' is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p style='color: red; text-align: center;'>Error: No content ID provided.</p>";
    exit();
}


$content_id = $_GET['id'];


// Fetch content from the database
$sql = "SELECT * FROM content WHERE content_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $content_id);
$stmt->execute();
$result = $stmt->get_result();
$content = $result->fetch_assoc();


if (!$content) {
    echo "<p style='color: red; text-align: center;'>Error: Content not found.</p>";
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>
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
            width: 400px;
            text-align: center;
        }
        h2 {
            color: yellow;
        }
        p {
            font-size: 16px;
        }
        .content-image {
            width: 100%;
            border-radius: 5px;
            margin-top: 10px;
        }
        a {
            display: block;
            text-decoration: none;
            color: black;
            background-color: yellow;
            padding: 10px;
            margin-top: 15px;
            font-weight: bold;
            border-radius: 5px;
        }
        a:hover {
            background-color: #ffaa00;
        }
    </style>
</head>
<body>


<div class="container">
    <h2><?php echo htmlspecialchars($content['title']); ?></h2>
    <p><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
   
    <?php if (!empty($content['image'])): ?>
        <img src="<?php echo htmlspecialchars($content['image']); ?>" class="content-image" alt="Content Image">
    <?php endif; ?>
   
    <?php if (!empty($content['file_path'])): ?>
        <a href="<?php echo htmlspecialchars($content['file_path']); ?>" download>Download File</a>
    <?php endif; ?>


    <a href="content.php">Back to Content List</a>
</div>


</body>
</html>
