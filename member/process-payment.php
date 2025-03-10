<?php
include '../includes/header.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Fetch the selected plan from the session
$selected_plan = $_SESSION['selected_plan'];
$start_date = $_SESSION['start_date'];
$end_date = $_SESSION['end_date'];

// Fetch plan details from the database
$plan_query = "SELECT * FROM membership_plans WHERE plan_id = ?";
$stmt = $conn->prepare($plan_query);
$stmt->bind_param("i", $selected_plan);
$stmt->execute();
$plan_result = $stmt->get_result();
$plan = $plan_result->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST['paymentMethod'];
    $amount_paid = $plan['price'];
    $gmail = $_POST['gmail'];
    $status = ($payment_method == 'payOnSite') ? 'pending' : 'completed';

    $gcash_number = $_POST['gcashNumber'] ?? null;
    $reference_number = $_POST['referenceNumber'] ?? null;
    $card_type = $_POST['cardType'] ?? null;
    $card_id = $_POST['cardID'] ?? null;

    $payment_query = "INSERT INTO membership_payments (member_id, plan_id, amount, gmail, payment_mode, gcash_reference_number, gcash_phone_number, card_type, card_id_number, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param("iidsssssss", $_SESSION['user_id'], $selected_plan, $amount_paid, $gmail, $payment_method, $reference_number, $gcash_number, $card_type, $card_id, $status);
    
    if ($stmt->execute()) {
        echo "<script>alert('Payment processed successfully!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Payment failed. Try again.');</script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - FlexiFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            width: 100%;
            background-color: #121212;
            font-family: 'Roboto', sans-serif;
        }

        /* Ads Image on the left side */
        .ads-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 200px; /* Set width of the image */
            height: 100vh; /* Full height of the page */
            background-image: url('default.jpg'); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            border-right: 5px solid yellow; /* Adds the yellow border on the right */
            z-index: 99;
        }

        .container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 5% auto;
            width: 75%; /* Adjusted to move content to the left */
            gap: 30px;
        }

        .left-box {
            width: 48%;
            background-color: black;
            padding: 30px;
            border-radius: 10px;
            color: yellow;
            box-shadow: 0 0 15px rgba(255, 255, 0, 0.5);
            font-size: 18px;
        }

        .left-box h2 {
            font-size: 32px;
            font-weight: bold;
        }

        .left-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid yellow;
            border-radius: 5px;
            background-color: black;
            color: yellow;
            font-size: 16px;
            text-align: center;
            margin-top: 10px;
        }

        .right-box {
            width: 48%;
            background-color: #222;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid yellow;
            box-shadow: 0 0 15px rgba(255, 255, 0, 0.5);
            color: white;
            text-align: center;
        }

        .right-box h2 {
            color: yellow;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .radio-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .radio-container input {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .radio-container label {
            background-color: yellow;
            color: black;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            width: 90%;
            text-align: center;
            border: 2px solid black;
        }

        .radio-container input:checked + label {
            background-color: black;
            color: yellow;
            border: 2px solid yellow;
        }

        .payment-fields {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            gap: 10px;
        }

        .payment-fields input {
            width: 90%;
            padding: 12px;
            border: 2px solid yellow;
            border-radius: 5px;
            background-color: black;
            color: yellow;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .payment-fields input:disabled {
            background-color: #555;
            color: gray;
            border: 2px solid gray;
        }

        #payOnSiteWarning {
            display: none;
            color: red;
            font-style: italic;
            margin-top: 10px;
        }

        .submit-btn {
            background-color: yellow;
            color: black;
            font-size: 16px;
            font-weight: bold;
            padding: 12px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            width: 90%;
        }

        .submit-btn:hover {
            background-color: black;
            color: yellow;
            border: 2px solid yellow;
        }
    </style>
</head>

<body>

    <!-- Ads Image on the left side -->
    <div class="ads-image"></div>


    <div class="container">
        <div class="left-box">
            <h2>Chosen Plan: <?php echo htmlspecialchars($plan['name']); ?></h2>
            <p>Price: ₱<?php echo number_format($plan['price'], 2); ?></p>
            <p>Starting Date: <?php echo $start_date; ?></p>
            <p>End Date: <?php echo $end_date; ?></p>
            <input type="email" name="gmail" placeholder="Enter your Gmail" required>
            <button style="margin-top: 10px;" onclick="window.location.href='membership-plans.php'">Back to Plans</button>
        </div>

        <div class="right-box">
            <h2>Choose Your Payment Method</h2>
            <form method="POST">
                <div class="radio-container">
                    <input type="radio" id="payOnSite" name="paymentMethod" value="payOnSite" checked>
                    <label for="payOnSite">Pay On Site</label>
                    <input type="radio" id="gcash" name="paymentMethod" value="gcash">
                    <label for="gcash">G-Cash</label>
                    <input type="radio" id="payWithCard" name="paymentMethod" value="credit_card">
                    <label for="payWithCard">Pay With Card</label>
                </div>

                <div id="payOnSiteWarning">
                    <p>Note: Membership Application will be set as pending until paid and activated by an Admin.</p>
                </div>

                <div class="payment-fields" id="gcashFields" style="display: none;">
                    <input type="text" name="gcashNumber" placeholder="Enter GCash Number">
                    <input type="text" name="referenceNumber" placeholder="Enter Reference Number">
                </div>

                <div class="payment-fields" id="cardFields" style="display: none;">
                    <input type="text" name="cardType" placeholder="Enter Type of Card">
                    <input type="text" name="cardID" placeholder="Enter Card ID Number">
                </div>

                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
            const gcashFields = document.getElementById("gcashFields");
            const cardFields = document.getElementById("cardFields");
            const payOnSiteWarning = document.getElementById("payOnSiteWarning");

            function toggleFields() {
                const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

                if (selectedMethod === "gcash") {
                    gcashFields.style.display = "block";
                    cardFields.style.display = "none";
                    payOnSiteWarning.style.display = "none";
                } else if (selectedMethod === "credit_card") {
                    cardFields.style.display = "block";
                    gcashFields.style.display = "none";
                    payOnSiteWarning.style.display = "none";
                } else {
                    payOnSiteWarning.style.display = "block";
                    gcashFields.style.display = "none";
                    cardFields.style.display = "none";
                }
            }

            // Attach event listener to payment method radio buttons
            paymentMethods.forEach(method => {
                method.addEventListener("change", toggleFields);
            });

            // Initialize the form on load
            toggleFields();
        });
    </script>

</body>
</html>

