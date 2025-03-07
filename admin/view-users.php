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

// Fetch users excluding soft-deleted ones, using 'image' column
$sql = "SELECT user_id, first_name, last_name, email, user_type, image FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(0, 0, 0);
            margin: 20px;
        }
        .container {
            width: 90%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(11, 11, 11, 0.1);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: #FFFFFF;
        }
        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            cursor: pointer;
            background-color:rgb(255, 215, 0);
            border: none;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .user-card {
            background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between; 
        }
        .user-card img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .user-info {
            flex-grow: 1;
        }
        .delete {
            background-color: #dc3545;
            padding: 8px 12px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Users List</h2>
<!-- <a href="restore-user.php" class="btn">View Deleted Users</a> -->
        <a href="index.php" class="btn">Home</a>
    </div>

    <div class="grid-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="user-card">
                <!-- Display Profile Image or Placeholder -->
                <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image']) 
                             ? 'uploads/' . htmlspecialchars($row['image']) 
                             : 'uploads/placeholder.png' ?>" 
                     alt="Profile Image">
                
                <div class="user-info">
                    <p><strong>User ID:</strong> <?= htmlspecialchars($row['user_id']) ?></p>
                    <p><strong>First Name:</strong> <?= htmlspecialchars($row['first_name']) ?></p>
                    <p><strong>Last Name:</strong> <?= htmlspecialchars($row['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                    <p><strong>User Type:</strong> <?= htmlspecialchars($row['user_type']) ?></p>
                    <!-- <a href="delete-user.php?id=<?= $row['user_id'] ?>" class="delete" -->
                       <!-- onclick="return confirm('Are you sure you want to deactivate this user?');">Delete</a> -->
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
