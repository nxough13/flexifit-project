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

// Ensure member access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'member') {
    header("Location: ../login.php");
    exit();
}

// Fetch all trainers from the trainers table
$sql = "SELECT trainer_id, first_name, last_name, specialty, image FROM trainers";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Trainers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            margin: 20px;
            color: white;
        }
        .container {
            width: 90%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: yellow;
        }
        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            cursor: pointer;
            background-color: yellow;
            border: none;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .trainer-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: black;
        }
        .trainer-card img {
            width: 60%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .trainer-info {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Trainers List</h2>
        <a href="index.php" class="btn">Back to Dashboard</a>
    </div>

    <div class="grid-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="trainer-card">
                <!-- Display Trainer Image or Placeholder -->
                <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image']) 
                             ? 'uploads/' . htmlspecialchars($row['image']) 
                             : 'uploads/placeholder.png' ?>" 
                     alt="Trainer Image">
                
                <div class="trainer-info">
                    <p><strong>Trainer ID:</strong> <?= htmlspecialchars($row['trainer_id']) ?></p>
                    <p><strong>Name:</strong> <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p>
                    <p><strong>Specialty:</strong> <?= htmlspecialchars($row['specialty']) ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
