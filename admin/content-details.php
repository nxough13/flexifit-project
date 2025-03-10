<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';


$conn = new mysqli("localhost", "root", "", "flexifit_db");




// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Check if content_id is set and valid
if (isset($_GET['content_id']) && is_numeric($_GET['content_id'])) {
    $content_id = $_GET['content_id']; // Get content_id from URL
} else {
    echo "Invalid content ID!";
    exit();
}


// Prepare and bind SQL query to avoid SQL injection
$query = $conn->prepare("SELECT c.content_id, c.title, c.description, c.content_type, c.file_path, c.image, c.created_at, u.first_name, u.last_name
                         FROM content c
                         JOIN users u ON c.admin_id = u.user_id
                         WHERE c.content_id = ?");
$query->bind_param("i", $content_id); // Bind content_id as integer


// Execute query
$query->execute();


// Get result
$result = $query->get_result();


// Fetch content details
if ($result->num_rows > 0) {
    $content = $result->fetch_assoc();
} else {
    echo "Content not found!";
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
            background-color: #111;
            color: #fff;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }


        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }


        .content-box {
            background-color: #222;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
            width: 80%;
            max-width: 800px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #f2f2f2;
        }


        .content-title {
            font-size: 28px;
            color: #f5a623;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }


        .content-description {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            line-height: 1.6;
        }


        .admin-info {
            font-size: 14px;
            color: #999;
            margin-bottom: 20px;
        }


        .admin-info p {
            margin: 5px 0;
        }


        .content-box img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }


        .edit-btn {
            background-color: #f5a623;
            color: #000;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }


        .edit-btn:hover {
            background-color: #e18a16;
        }


        .content-box .username {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }


        .content-box .username img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }


    </style>
</head>
<body>


<div class="container">
    <div class="content-box">
        <h2 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h2>


        <div class="content-description">
            <p><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
        </div>


        <div class="admin-info">
            <p>By: <?php echo htmlspecialchars($content['first_name'] . " " . $content['last_name']); ?> | Created at: <?php echo $content['created_at']; ?></p>
        </div>


        <?php if (!empty($content['image'])): ?>
            <img src="uploads/<?php echo $content['image']; ?>" alt="Content Image">
        <?php else: ?>
            <p>No image available</p>
        <?php endif; ?>


        <a href="edit-content.php?content_id=<?php echo $content['content_id']; ?>" class="edit-btn">Edit Content</a>
    </div>
</div>


</body>
</html>


<?php
$query->close();
$conn->close();
?>
<?php include '../includes/footer.php'; ?>
