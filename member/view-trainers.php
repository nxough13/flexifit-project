<?php
// Start session to access the logged-in user's details
session_start();
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

if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();

}


// Fetch all trainers from the database
$sql = "SELECT * FROM trainers ORDER BY trainer_id ASC";
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


        .trainer-item {
            background-color: #333;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }


        .trainer-item .trainer-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffcc00;
        }


        .trainer-item .trainer-details {
            margin: 10px 0;
            font-size: 1.1rem;
        }


        .trainer-item .trainer-status {
            font-size: 1rem;
            color: #b0b0b0;
        }


        .trainer-item .trainer-availability {
            font-size: 1rem;
            color: #999;
        }


        .trainer-item .trainer-image {
    width: 150px; /* Set a fixed width */
    height: 150px; /* Set a fixed height */
    object-fit: cover; /* This ensures the image fits within the box without stretching */
    border-radius: 50%; /* Optionally, make the image circular */
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
        <h2>View Trainers</h2>


        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="trainer-item">
                    <div class="trainer-name"><?php echo htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']); ?></div>
                    <div class="trainer-details">Email: <?php echo htmlspecialchars($row['email']); ?></div>
                    <div class="trainer-details">Age: <?php echo htmlspecialchars($row['age']); ?></div>
                    <div class="trainer-status">Status: <?php echo htmlspecialchars($row['status']); ?></div>
                    <div class="trainer-availability">Availability: <?php echo htmlspecialchars($row['availability_status']); ?></div>


                    <?php if ($row['image']): ?>
                        <img style="width: 250px;"src="../admin/uploads/<?php echo $row['image'] ?? 'default.jpg'; ?>" alt="Trainer Image">

                    <?php endif; ?>


                    <!-- Details Button -->
                    <a href="trainers.php?trainer_id=<?php echo $row['trainer_id']; ?>" class="details-btn">Details</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No trainers available.</p>
        <?php endif; ?>
    </div>


</body>

</html>


<?php
$conn->close();
?>
<?php include '../includes/footer.php'; ?>