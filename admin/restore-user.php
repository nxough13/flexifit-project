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
// neo
// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle user restoration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restore_id'])) {
    $restore_id = intval($_POST['restore_id']);
    
    // Update user to restore (set deleted_at to NULL)
    $restore_sql = "UPDATE users SET deleted_at = NULL WHERE user_id = ?";
    $stmt = $conn->prepare($restore_sql);
    $stmt->bind_param("i", $restore_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User restored successfully.";
    } else {
        $_SESSION['message'] = "Error restoring user.";
    }
    
    $stmt->close();
    header("Location: restore-user.php"); // Refresh page after restoring
    exit();
}

// Fetch soft-deleted users
$sql = "SELECT user_id, first_name, last_name, email, user_type, image FROM users WHERE deleted_at IS NOT NULL";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(0, 0, 0);
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
            background-color: rgb(255, 215, 0);
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
        .restore {
            background-color: #28a745;
            padding: 8px 12px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Deleted Users</h2>
        <a href="view-users.php" class="btn">View Active Users</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: white; text-align: center;"><?= $_SESSION['message'] ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="grid-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="user-card">
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
                    <form method="POST" action="">
                        <input type="hidden" name="restore_id" value="<?= $row['user_id'] ?>">
                        <button type="submit" class="restore" onclick="return confirm('Are you sure you want to restore this user?');">Restore</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
