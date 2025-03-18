<?php
session_start();
include '../includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// CRITICAL: Reset email_sent flag when first accessing receipt.php 
// This ensures the receipt is always displayed first without email being sent automatically
if (!isset($_SESSION['fresh_receipt_page']) || $_SESSION['fresh_receipt_page'] !== true) {
    // This is the first time the page is loaded after redirect from process-payment
    $_SESSION['email_sent'] = false;
    $_SESSION['fresh_receipt_page'] = true;
}

// If the "Send Receipt" button is clicked, send the email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_receipt'])) {
    // Check if email has already been sent during this session
    if (isset($_SESSION['email_sent']) && $_SESSION['email_sent'] === true) {
        echo "<script>alert('Receipt has already been sent to your email.'); window.location.href = 'receipt.php';</script>";
        exit();
    }
    
    // Fetch the user's email and admin's email
    $to = $_SESSION['payment_gmail'];  // User's email (fetched from session)
    $admin_email = 'flexifit04@gmail.com';  // Admin's email

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';  // Your Gmail email address
        $mail->Password = 'dwnw xuwn baln ljbp';  // Your generated App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit Gym');
        $mail->addAddress($to);  // User's email
        $mail->addAddress($admin_email);  // Admin's email

        // Get payment ID from session
        $payment_id = $_SESSION['payment_id'];

        // Fetch payment details from the database
        $query = "SELECT * FROM membership_payments WHERE payment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        $payment = $payment_result->fetch_assoc();
        $stmt->close();
        
        $member_id = $payment['member_id'];
        $plan_id = $payment['plan_id'];
        
        // Fetch the member details
        $member_query = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($member_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $member_result = $stmt->get_result();
        $member = $member_result->fetch_assoc();
        $stmt->close();
        
        $member_name = $member['first_name'] . " " . $member['last_name'];
        $member_no = str_pad($_SESSION['user_id'], 4, "0", STR_PAD_LEFT);
        
        // Get the correct start and end dates from the session
        $start_date = $_SESSION['start_date'];
        $end_date = $_SESSION['end_date'];
        
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

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Membership Fee Receipt - FlexiFit Gym";
        $mail->Body = "
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

        // Send the email
        $mail->send();

        // Set session flag to indicate email was sent
        $_SESSION['email_sent'] = true;
        
        // Reset the fresh_receipt_page flag so it doesn't interfere with future visits
        unset($_SESSION['fresh_receipt_page']);

        // Redirect after email is sent
        echo "<script>alert('Receipt has been sent to both your email and the admin.'); window.location.href = 'index.php';</script>";
        exit();  // Stop further processing
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch payment ID from session
$payment_id = $_SESSION['payment_id'];

// Fetch payment details from the database
$query = "SELECT * FROM membership_payments WHERE payment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo "<script>alert('Payment not found.'); window.location.href = 'membership-plans.php';</script>";
    exit();
}

// Get the payment and member details
$member_id = $payment['member_id'];
$plan_id = $payment['plan_id'];

// Fetch the logged-in user's details using user_id from session
$member_query = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $_SESSION['user_id']);  // Use logged-in user's ID
$stmt->execute();
$member_result = $stmt->get_result();
$member = $member_result->fetch_assoc();
$stmt->close();

if (!$member) {
    echo "<script>alert('Member not found.'); window.location.href = 'membership-plans.php';</script>";
    exit();
}

$member_name = $member['first_name'] . " " . $member['last_name'];
$member_no = str_pad($_SESSION['user_id'], 4, "0", STR_PAD_LEFT);  // Use logged-in user's ID for membership number

// Get the correct start and end dates from the session
$start_date = $_SESSION['start_date'];  // Use session value
$end_date = $_SESSION['end_date'];      // Use session value

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

// Display the receipt HTML
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - FlexiFit Gym</title>
    <style>
        /* Same styles as before */
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

            <!-- Conditionally display GCash phone number or card ID -->
            <?php if ($payment_mode == 'Gcash'): ?>
                <p><strong>GCash Phone Number:</strong> <?php echo $payment['gcash_phone_number']; ?></p>
            <?php elseif ($payment_mode == 'Credit_card' || $payment_mode == 'Debit'): ?>
                <p><strong>Card ID:</strong> <?php echo $payment['card_id_number']; ?></p>
            <?php endif; ?>

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

<!-- Button to send the receipt -->
<form method="POST" action="receipt.php">
    <button type="submit" name="send_receipt" style="position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background-color: yellow; color: black; font-weight: bold;">Send Receipt</button>
</form>

</body>
</html>

<?php include "../includes/footer.php"; ?>