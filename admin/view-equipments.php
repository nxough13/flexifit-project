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

// Fetch all equipment from the equipment table
$sql = "SELECT equipment_id, name, description, image FROM equipment";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipments</title>
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
        .equipment-card {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .equipment-card img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .equipment-info {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>Equipment List</h2>
    <div>
        <a href="add-equipment.php" class="btn">Add Equipment</a>
        <a href="index.php" class="btn">Home</a>
    </div>
</div>

    <div class="grid-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="equipment-card">
                <!-- Display Equipment Image or Placeholder -->
                <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image']) 
                             ? 'uploads/' . htmlspecialchars($row['image']) 
                             : 'uploads/placeholder.png' ?>" 
                     alt="Equipment Image">
                
                <div class="equipment-info">
                    <p><strong>Equipment ID:</strong> <?= htmlspecialchars($row['equipment_id']) ?></p>
                    <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
