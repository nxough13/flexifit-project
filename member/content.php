<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Fetch all content from the database
$sql = "SELECT * FROM content ORDER BY content_id ASC";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>
    <style>
        body {
            background-color: #222;
            color: #fff;
            font-family: Arial, sans-serif;
        }


        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }


        h2 {
            text-align: center;
            color: #ffcc00;
            font-size: 2rem;
        }


        .content-item {
            background-color: #333;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }


        .content-item .content-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffcc00;
        }


        .content-item .content-description {
            margin: 10px 0;
            font-size: 1.1rem;
        }


        .content-item .content-type {
            font-size: 1rem;
            color: #b0b0b0;
        }


        .content-item .content-created-at {
            font-size: 1rem;
            color: #999;
        }


        .content-item .content-image {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
        }


        .details-btn {
            display: inline-block;
            background-color: #ffcc00;
            color: #222;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }


        .details-btn:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>


<div class="container">
    <h2>Explore the Feed</h2>


    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="content-item">
                <div class="content-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="content-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                <div class="content-type">Type: <?php echo htmlspecialchars($row['content_type']); ?></div>


                <?php if ($row['image']): ?>
                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Content Image" class="content-image">
                <?php endif; ?>


                <div class="content-created-at">Posted on: <?php echo htmlspecialchars($row['created_at']); ?></div>


                <!-- Details Button -->
                <a href="view-content.php?content_id=<?php echo $row['content_id']; ?>" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>


</body>
</html>


<?php
$conn->close();
?>
<?php include '../includes/footer.php'; // neo?>
