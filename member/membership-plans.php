<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style>
body {font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; height: 100vh;}
.container {width: 80%; max-width: 600px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); text-align: center;}
h2 {color: #333;}
.plan {border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; cursor: pointer; transition: 0.3s;}
.plan:hover {background: #e2e2e2;}
button {width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; transition: 0.3s;}
button:hover {background: #218838;}
</style>

</head>
<body>
    
</body>
</html>


<?php
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch plans from database
$result = $conn->query("SELECT plan_id, plan_name, duration_days FROM membership_plans");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plan_id'])) {
    $plan_id = $_POST['plan_id'];
    header("Location: join_membership.php?plan_id=$plan_id");
    exit();
}
?>

<h2>Choose Your Membership Plan</h2>
<form method="post">
    <?php while ($row = $result->fetch_assoc()) { ?>
        <button type="submit" name="plan_id" value="<?= $row['plan_id'] ?>">
            <?= $row['plan_name'] ?> (<?= $row['duration_days'] ?> days)
        </button>
    <?php } ?>
</form>
