<?php
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_type'] == 'guest') {
    // Guests cannot access members or admin areas
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch available membership plans
$result = $conn->query("SELECT plan_id, name, duration_days, price FROM membership_plans");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plan_id'])) {
    $plan_id = $_POST['plan_id'];

    // Get membership plan details
    $plan_query = $conn->query("SELECT duration_days FROM membership_plans WHERE plan_id = $plan_id");
    if ($plan_query->num_rows > 0) {
        $plan = $plan_query->fetch_assoc();
        $duration_days = $plan['duration_days'];

        // Set membership dates
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$duration_days days"));

        // Insert membership into the `members` table
        $insert_query = $conn->prepare("INSERT INTO members (user_id, membership_status, plan_id, start_date, end_date) VALUES (?, 'active', ?, ?, ?)");
        $insert_query->bind_param("iiss", $user_id, $plan_id, $start_date, $end_date);
        $insert_query->execute();

        // Update user type to 'member'
        $update_user_query = $conn->prepare("UPDATE users SET user_type = 'member' WHERE user_id = ?");
        $update_user_query->bind_param("i", $user_id);
        $update_user_query->execute();

        // Redirect to index.php after successful membership activation
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Membership Plan</title>
    <style>
        body {
            font-family: Arial, sans-serif; 
            background: #f4f4f4; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
        }
        .container {
            width: 80%; 
            max-width: 600px; 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1); 
            text-align: center;
        }
        h2 { color: #333; }
        .plan {
            border: 1px solid #ccc; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
            background: #f9f9f9; 
            cursor: pointer; 
            transition: 0.3s;
        }
        .plan:hover { background: #e2e2e2; }
        button {
            width: 100%; 
            padding: 10px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: 0.3s;
        }
        button:hover { background: #218838; }
    </style>
</head>
<body>

<div class="container">
    <h2>Choose Your Membership Plan</h2>
    <form method="post">
        <?php while ($row = $result->fetch_assoc()) { ?>
            <button type="submit" name="plan_id" value="<?= $row['plan_id'] ?>">
                <?= $row['name'] ?> - <?= $row['duration_days'] ?> days - $<?= $row['price'] ?>
            </button>
        <?php } ?>
    </form>
</div>

</body>
</html>
