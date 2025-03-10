<?php
include '../includes/header.php';
session_start();

// Fetch the selected plan from the session
$selected_plan = $_SESSION['selected_plan'];
$start_date = $_SESSION['start_date'];
$end_date = $_SESSION['end_date'];

// Fetch plan details from the database
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
$plan_query = "SELECT * FROM membership_plans WHERE plan_id = ?";
$stmt = $conn->prepare($plan_query);
$stmt->bind_param("i", $selected_plan);
$stmt->execute();
$plan_result = $stmt->get_result();
$plan = $plan_result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - FlexiFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
      /* Add your existing CSS for layout */
    </style>
</head>
<body>

<header>
    <a href="index.php">Home</a>
    <a href="membership-plans.php">Back to Plans</a>
</header>

<div class="container">
    <div class="left-box">
        <h2>Chosen Plan: <?php echo htmlspecialchars($plan['name']); ?></h2>
        <p>Price: ₱<?php echo number_format($plan['price'], 2); ?></p>
        <p>Starting Date: <?php echo $start_date; ?></p>
        <p>End Date: <?php echo $end_date; ?></p>
        <p>Description: <?php echo htmlspecialchars($plan['description']); ?></p>
    </div>

    <div class="right-box">
        <h2>Choose Your Payment Method</h2>

        <div class="radio-container">
            <input type="radio" id="payOnSite" name="paymentMethod" checked>
            <label for="payOnSite">Pay On Site</label>
            <input type="radio" id="gcash" name="paymentMethod">
            <label for="gcash">G-Cash</label>
            <input type="radio" id="payWithCard" name="paymentMethod">
            <label for="payWithCard">Pay With Card</label>
        </div>

        <div id="gcashFields">
            <label for="gcashNumber">G-Cash Number:</label>
            <input type="text" id="gcashNumber" placeholder="Enter GCash Number">
            <label for="referenceNumber">Reference Number:</label>
            <input type="text" id="referenceNumber" placeholder="Enter Reference Number">
        </div>

        <div id="cardFields">
            <label for="cardType">Type of Card:</label>
            <input type="text" id="cardType" placeholder="Enter Type of Card">
            <label for="cardID">Card ID Number:</label>
            <input type="text" id="cardID" placeholder="Enter Card ID Number">
        </div>

        <button type="submit">Submit</button>
    </div>
</div>

<script>
    function togglePaymentFields() {
        const gcashNumber = document.getElementById('gcashNumber');
        const referenceNumber = document.getElementById('referenceNumber');
        const cardType = document.getElementById('cardType');
        const cardID = document.getElementById('cardID');

        const isGcash = document.getElementById('gcash').checked;
        const isCard = document.getElementById('payWithCard').checked;
        const isPayOnSite = document.getElementById('payOnSite').checked;

        gcashNumber.disabled = !isGcash;
        referenceNumber.disabled = !isGcash;

        cardType.disabled = !isCard;
        cardID.disabled = !isCard;

        document.getElementById('payOnSiteNote').style.display = isPayOnSite ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', togglePaymentFields);
</script>

</body>
</html>
