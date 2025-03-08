<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch membership plans from the database
$sql = "SELECT * FROM membership_plans";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Membership Plan</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: yellow;
            margin: 0;
            padding: 0;
        }
        header {
            background: #000;
            padding: 20px;
            text-align: center;
        }
        h1 {
            color: #ffc107;
            font-size: 2.5rem;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .plan-card {
            background-color: #1f1f1f;
            color: white;
            padding: 20px;
            width: 30%;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 0px 10px rgba(255, 193, 7, 0.8);
        }
        .plan-card img {
            max-width: 100%;
            border-radius: 8px;
        }
        .plan-card h3 {
            margin-top: 20px;
            color: #ffc107;
        }
        .plan-card p {
            margin: 10px 0;
        }
        .plan-card .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffc107;
        }
        .plan-card input[type="radio"] {
            margin-right: 10px;
        }
        .submit-container {
            margin-top: 30px;
            text-align: center;
        }
        .submit-container button {
            background-color: #ffc107;
            color: black;
            font-size: 1.2rem;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.8);
        }
        .submit-container button:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
<header>
    <h1>Choose Your Membership Plan</h1>
    <p>Join our fitness community and start your transformation!</p>
</header>

<div class="container">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            ?>
            <div class="plan-card">
                <img src="uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?> Image">
                <h3><?php echo $row['name']; ?></h3>
                <p><?php echo $row['description']; ?></p>
                <p class="price">For only ₱<?php echo number_format($row['price'], 2); ?></p>
                <p>Duration: <?php echo $row['duration_days']; ?> Days</p>
                <p>
                    <input type="radio" name="plan" value="<?php echo $row['plan_id']; ?>" required> Select this Plan
                </p>
            </div>
            <?php
        }
    } else {
        echo "<p>No membership plans available.</p>";
    }
    $conn->close();
    ?>
</div>

<div class="submit-container">
    <button type="submit" onclick="window.location.href='payment.php'">Proceed to Payment</button>
</div>

</body>
</html>
