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

    // Set the payment status based on the payment method
    if ($payment_method == 'payOnSite') {
        $status = 'pending';
        $payment_mode = 'cash';  // Set payment mode as 'cash' for pay on site
    } else {
        $status = 'completed';
        $payment_mode = $payment_method;  // Otherwise, use the selected payment method
    }
    

    $gcash_number = $_POST['gcashNumber'] ?? null;
    $reference_number = $_POST['referenceNumber'] ?? null;
    $card_type = $_POST['cardType'] ?? null;
    $card_id = $_POST['cardID'] ?? null;

    // Check if the user is already a member
    $check_member_query = "SELECT * FROM members WHERE user_id = ?";
    $stmt = $conn->prepare($check_member_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Check the user's medical condition before inserting the member
        $check_medical_condition = "SELECT medical_condition FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($check_medical_condition);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $condition_result = $stmt->get_result();
        $condition_row = $condition_result->fetch_assoc();

        if ($condition_row['medical_condition'] == 'yes') {
            $status = 'pending';
        } else {
            if ($payment_method == 'payOnSite') {
                $status = 'pending';
            } else {
                $status = 'active';
            }
        }

        // Insert into the members table
        $insert_member = "INSERT INTO members (user_id, plan_id, start_date, end_date, membership_status, free_training_session) 
                          VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_member);
        $stmt->bind_param("iisssi", $_SESSION['user_id'], $selected_plan, $start_date, $end_date, $status, $plan['free_training_session']);
        $stmt->execute();

        // Get the member_id of the inserted record and store it in the session
        $member_id = $stmt->insert_id;
        $_SESSION['member_id'] = $member_id;
        $stmt->close();

        // Update user_type to 'member'
        $update_user_type = "UPDATE users SET user_type = 'member' WHERE user_id = ?";
        $stmt = $conn->prepare($update_user_type);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Get existing member_id if the user is already a member
        $member = $result->fetch_assoc();
        $member_id = $member['member_id'];
    }

    // Insert payment details (ensure the member_id is valid here)
    $payment_query = "INSERT INTO membership_payments (member_id, plan_id, amount, gmail, payment_mode, gcash_reference_number, gcash_phone_number, card_type, card_id_number, payment_status, payment_date) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param("iidsssssss", $member_id, $selected_plan, $amount_paid, $gmail, $payment_mode, $reference_number, $gcash_number, $card_type, $card_id, $status);
    $stmt->execute();

    // Get the payment_id of the inserted record and store it in the session
    $payment_id = $stmt->insert_id;
    $_SESSION['payment_id'] = $payment_id;  // Store the payment_id for use in the receipt page
    $stmt->close();

    // Close the connection
    $conn->close();

    echo "<script>alert('Payment processed successfully!'); window.location.href='receipt.php';</script>";
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

        .ads-image {
            position: fixed;
            top: 80px;
            left: 0;
            width: 200px;
            height: 100vh;
            background-image: url('../admin/uploads/Left-image.jpg');
            background-size: cover;
            background-position: center;
            border-right: 5px solid yellow;
            z-index: 99;
        }

        select[name="cardType"] {
            width: 90%;
            padding: 12px;
            border: 2px solid yellow;
            border-radius: 5px;
            background-color: black;
            color: yellow;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            margin-bottom: 20px;
        }

        select[name="cardType"]::-ms-expand {
            display: none;
        }

        select[name="cardType"]:focus {
            border-color: yellow;
            outline: none;
        }

        .container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 5% auto;
            width: 75%;
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
            width: 60%;
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
            gap: 15px;
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
            color: white;
            font-style: italic;
            margin-top: 10px;
            font-size: larger;
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
            width: 40%;
        }

        .submit-btn:hover {
            background-color: black;
            color: yellow;
            border: 2px solid yellow;
        }

        .back-to-plans-btn {
            background-color: yellow;
            color: black;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            width: 30%;
            margin-top: 10px;
        }

        .back-to-plans-btn:hover {
            background-color: black;
            color: yellow;
        }

        .right-box input[name="gmail"] {
            width: 90%;
            padding: 12px;
            border: 2px solid yellow;
            border-radius: 5px;
            background-color: black;
            color: yellow;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="ads-image"></div>

    <div class="container">
        <div class="left-box">
            <h2>Chosen Plan: <?php echo htmlspecialchars($plan['name']); ?></h2>
            <p>Price: ₱<?php echo number_format($plan['price'], 2); ?></p>
            <p>Starting Date: <?php echo $start_date; ?></p>
            <p>End Date: <?php echo $end_date; ?></p>
            <br><br>
            <button class="back-to-plans-btn" onclick="window.location.href='membership-plans.php'">Back to Plans</button>
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
                    <br>
                    <p>NOTE: Membership Application will be set as pending until paid and activated by an Admin.</p>
                    <br>
                </div>

                <div class="payment-fields" id="gcashFields" style="display: none;">
                    <br>
                    <input type="text" name="gcashNumber" placeholder="Enter GCash Number">
                    <br><br>
                    <input type="text" name="referenceNumber" placeholder="Enter Reference Number">
                    <br><br><br>
                </div>

                <div class="payment-fields" id="cardFields" style="display: none;">
                    <br>
                    <select name="cardType">
                        <option value="">Select Card Type</option>
                        <option value="Visa">Visa</option>
                        <option value="Mastercard">Mastercard</option>
                        <option value="Amex">Amex</option>
                        <option value="Other">Other</option>
                        <option value="Debit">Debit</option>
                        <option value="Credit">Credit</option>
                    </select>

                    <br><br>
                    <input type="text" name="cardID" placeholder="Enter Card ID Number">
                    <br><br><br>
                </div>
                <p style="font-size: large;">Enter Gmail for Receipt:</p>
                <input type="email" name="gmail" placeholder="Enter your Gmail" required>
                <br>
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

            paymentMethods.forEach(method => {
                method.addEventListener("change", toggleFields);
            });

            toggleFields();
        });
    </script>
<br><br>
</body>
</html>

<?php include "../includes/footer.php"; ?>
