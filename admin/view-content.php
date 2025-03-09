<?php
// Connect to database
session_start();
include '../includes/header.php';




$conn = new mysqli("localhost", "root", "", "flexifit_db");




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
    font-family: Arial, sans-serif;
    background-color: #121212;
    color: white;
    margin: 0;
    padding: 0;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}




.container {
    max-width: 1100px;
    width: 90%;
    background: #1f1f1f;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 0px 15px rgba(255, 193, 7, 0.8);




    /* Adjust margin for more top space while keeping it centered */
    margin-top: 120px;
    margin-bottom: auto;
}




        h2 {
            color: #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #2c2c2c;
        }
        table, th, td {
            border: 1px solid #ffc107;
        }
        th, td {
            padding: 12px;
            text-align: center;
            color: white;
        }
        th {
            background: #ffc107;
            color: black;
        }
        tr:nth-child(even) {
            background: #3c3c3c;
        }
        .action-btn {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            font-size: 16px;
        }
        .edit-btn {
            background: #28a745;
        }
        .delete-btn {
            background: #dc3545;
        }
        .enable-btn {
            background: #007BFF;
            display: none;
        }
        .add-btn {
            display: inline-block;
            background: #ffc107;
            color: black;
            padding: 10px 15px;
            margin-top: 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .add-btn:hover {
            background: #e0a800;
        }
    </style>




    <script>
        function disableContent(contentId) {
            if (confirm("Are you sure you want to disable this content?")) {
                document.getElementById('disable-' + contentId).style.display = 'none';
                document.getElementById('enable-' + contentId).style.display = 'inline-block';
            }
        }




        function enableContent(contentId) {
            if (confirm("Do you want to enable this content again?")) {
                document.getElementById('enable-' + contentId).style.display = 'none';
                document.getElementById('disable-' + contentId).style.display = 'inline-block';
            }
        }
    </script>
</head>
<body>




<div class="container">
    <h2>Content List</h2>
    <a href="create-content.php" class="add-btn">+ Add New Content</a>
    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Title</th>
            <th>Description</th>
            <th>Action</th>
        </tr>




        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["content_id"] . "</td>";
                echo "<td>";
                if (!empty($row["image"]) && file_exists(__DIR__ . "/uploads/" . $row["image"])) {
                    echo "<img src='uploads/" . $row["image"] . "' width='50' height='50' style='border-radius:50px;'>";
                } else {
                    echo "<img src='uploads/default.png' width='50' height='50' style='border-radius:50px;'>";
                }
                echo "</td>";
                echo "<td>" . $row["title"] . "</td>";
                echo "<td>" . $row["description"] . "</td>";
                echo "<td>";
                echo "<a href='edit-content.php?id=" . $row["content_id"] . "' class='action-btn edit-btn' style='text-decoration: none;'>✏️</a>";
                echo "<a href='#' onclick='disableContent(" . $row["content_id"] . ")' id='disable-" . $row["content_id"] . "' class='action-btn delete-btn' style='text-decoration: none;'>❌</a>";
                echo "<a href='#' onclick='enableContent(" . $row["content_id"] . ")' id='enable-" . $row["content_id"] . "' class='action-btn enable-btn' style='text-decoration: none;'>🔄</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No content found.</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</div>




</body>
</html>








<?php




include '../includes/footer.php';
?>





CONTENT-DETAILS.PHP

<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check if connection was successful
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
