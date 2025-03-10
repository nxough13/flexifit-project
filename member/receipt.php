<?php
session_start();
include '../includes/header.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check if payment_id is available in session
if (!isset($_SESSION['payment_id'])) {
    echo "<script>alert('No payment data found! Please make sure the payment was successful.'); window.location.href = 'membership-plans.php';</script>";
    exit();
}

$payment_id = $_SESSION['payment_id'];  // Ensure payment_id is in session

// Fetch the latest payment and member details
$query = "SELECT * FROM membership_payments WHERE payment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo "<script>alert('Payment not found. Please try again.'); window.location.href = 'membership-plans.php';</script>";
    exit();
}

$member_id = $payment['member_id'];
$plan_id = $payment['plan_id'];

// Fetch member details from the users table
$member_query = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member_result = $stmt->get_result();
$member = $member_result->fetch_assoc();
$stmt->close();

// Ensure first_name and last_name are available
if (!$member) {
    echo "<script>alert('Member not found. Please try again.'); window.location.href = 'membership-plans.php';</script>";
    exit();
}

$member_name = $member['first_name'] . " " . $member['last_name']; // Correct member name
$member_no = str_pad($member_id, 4, "0", STR_PAD_LEFT); // Format member number with leading zeros

// Now for payment details
$payment_date = date("m-d-Y", strtotime($payment['payment_date']));
$plan_query = "SELECT name FROM membership_plans WHERE plan_id = ?";
$stmt = $conn->prepare($plan_query);
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$plan_result = $stmt->get_result();
$plan = $plan_result->fetch_assoc();
$stmt->close();
$plan_name = $plan['name'];

$amount_paid = number_format($payment['amount'], 2);
$payment_mode = ucfirst($payment['payment_mode']);
$reference_number = $payment['gcash_reference_number'] ?? $payment['card_id_number'];

// Fetch start and end date from the members table
$member_query = "SELECT start_date, end_date FROM members WHERE member_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member_dates_result = $stmt->get_result();
$member_dates = $member_dates_result->fetch_assoc();
$stmt->close();

$start_date = date("m-d-Y", strtotime($member_dates['start_date']));
$end_date = date("m-d-Y", strtotime($member_dates['end_date']));

// Send email with receipt details (using PHP mail function, ensure the mail server is configured)
$to = $payment['gmail'];
$subject = "Membership Fee Receipt - FlexiFit Gym";
$body = "
<html>
<head>
    <title>Receipt</title>
</head>
<body>
    <h1>RECEIPT</h1>
    <p><strong>FLEXIFIT GYM</strong></p>
    <table>
        <tr><td><strong>Membership Plan:</strong></td><td>$plan_name</td></tr>
        <tr><td><strong>Amount Paid:</strong></td><td>₱$amount_paid</td></tr>
        <tr><td><strong>Mode of Payment:</strong></td><td>$payment_mode</td></tr>
        <tr><td><strong>Reference No:</strong></td><td>$reference_number</td></tr>
        <tr><td><strong>Starting Date:</strong></td><td>$start_date</td></tr>
        <tr><td><strong>End Date:</strong></td><td>$end_date</td></tr>
        <tr><td><strong>Membership No:</strong></td><td>$member_no</td></tr>
    </table>
    <p><strong>Received From:</strong> $member_name</p>
    <p><strong>Received By:</strong> FlexiFit Gym</p>
    <p><strong>Date Payment:</strong> $payment_date</p>
    <p><strong>Date Received:</strong> $payment_date</p>
</body>
</html>
";

// Send email
$headers = "From: no-reply@flexifitgym.com";
mail($to, $subject, $body, "Content-Type: text/html; charset=UTF-8\r\n$headers");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - FlexiFit Gym</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: white;
            margin: 0;
            padding: 0;
        }

        .receipt-container {
            width: 80%;
            margin: 0 auto;
            padding: 30px;
            background-color: #1a1a1a;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            margin-top: 50px;
        }

        .header {
            text-align: center;
            font-size: 36px;
            color: yellow;
            margin-bottom: 20px;
        }

        .content {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .left {
            width: 50%;
        }

        .right {
            width: 50%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table td, .table th {
            padding: 10px;
            border: 1px solid yellow;
            text-align: left;
        }

        .table th {
            background-color: #333;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
        }

        .footer p {
            font-size: 18px;
        }

    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">RECEIPT</div>
    <div class="content">
        <div class="left">
            <p><strong>FLEXIFIT GYM</strong></p>
            <p>Membership Plan: <?php echo $plan_name; ?></p>
            <p>Amount Received: ₱<?php echo $amount_paid; ?></p>
            <p>Mode of Payment: <?php echo $payment_mode; ?></p>
            <p>Reference No: <?php echo $reference_number; ?></p>
            <p>Starting Date: <?php echo $start_date; ?></p>
            <p>End Date: <?php echo $end_date; ?></p>
            <p>Membership No: <?php echo $member_no; ?></p>
        </div>
        <div class="right">
            <p><strong>Received From:</strong> <?php echo $member_name; ?></p>
            <p><strong>Received By:</strong> FlexiFit Gym</p>
            <p><strong>Date Payment:</strong> <?php echo $payment_date; ?></p>
            <p><strong>Date Received:</strong> <?php echo $payment_date; ?></p>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for being a part of FlexiFit Gym!</p>
        <p>If you have any questions, feel free to contact us.</p>
    </div>
</div>
<br><br><br><br><br><br><br><br><br><br><br><br><br>
</body>
</html>

<?php include "../includes/footer.php"; ?>
