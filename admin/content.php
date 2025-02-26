<?php
session_start();
include '../includes/header.php';
?>
<?php




$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);




// Fetch all content from the database
$sql = "SELECT * FROM content ORDER BY content_id DESC";
$result = $conn->query($sql);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content List</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .container {
            background-color: #111;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px yellow;
            width: 90%;
            max-width: 600px;
            text-align: center;
        }
        h2 {
            color: yellow;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid yellow;
            text-align: center;
        }
        a {
            text-decoration: none;
            color: black;
            background-color: yellow;
            padding: 5px 10px;
            margin: 5px;
            display: inline-block;
            border-radius: 5px;
        }
        a:hover {
            background-color: #ffaa00;
        }
    </style>
</head>
<body>




<div class="container">
    <h2>Content List</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td>
                <a href="view-content.php?id=<?php echo $row['content_id']; ?>">View</a>
               
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="create-content.php">Add New Content</a>
</div>




</body>
</html>
<?php


include '../includes/footer.php';
?>
