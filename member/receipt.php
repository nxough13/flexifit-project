<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check if terms were accepted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['terms_action'])) {
    if ($_POST['terms_action'] === 'accept') {
        $_SESSION['terms_accepted'] = true;
    } else {
        // If terms were declined, delete the payment record and redirect
        if (isset($_SESSION['payment_id'])) {
            $delete_query = "DELETE FROM membership_payments WHERE payment_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $_SESSION['payment_id']);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['terms_accepted']);
        unset($_SESSION['payment_id']);
        header("Location: process-payment.php");
        exit();
    }
}

// Redirect if not logged in or payment not set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['payment_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if terms were already accepted
$show_terms = !isset($_SESSION['terms_accepted']);

// Send email only if terms were accepted
if (!$show_terms && !isset($_SESSION['email_sent'])) {
    $to = $_SESSION['payment_gmail'];
    $admin_email = 'flexifit04@gmail.com';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';
        $mail->Password = 'dwnw xuwn baln ljbp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit Gym');
        $mail->addAddress($to);
        $mail->addAddress($admin_email);

        // Payment details
        $payment_id = $_SESSION['payment_id'];
        $query = "SELECT * FROM membership_payments WHERE payment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        $payment = $payment_result->fetch_assoc();
        $stmt->close();
        
        $member_id = $payment['member_id'];
        $plan_id = $payment['plan_id'];
        
        // Member details
        $member_query = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($member_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $member_result = $stmt->get_result();
        $member = $member_result->fetch_assoc();
        $stmt->close();
        
        $member_name = $member['first_name'] . " " . $member['last_name'];
        $member_no = str_pad($_SESSION['user_id'], 4, "0", STR_PAD_LEFT);
        
        $start_date = $_SESSION['start_date'];
        $end_date = $_SESSION['end_date'];
        $payment_date = date("F j, Y", strtotime($payment['payment_date']));
        
        $plan_query = "SELECT name, description FROM membership_plans WHERE plan_id = ?";
        $stmt = $conn->prepare($plan_query);
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $plan_result = $stmt->get_result();
        $plan = $plan_result->fetch_assoc();
        $stmt->close();
        $plan_name = $plan['name'];
        $plan_description = $plan['description'];
        
        $amount_paid = number_format($payment['amount'], 2);
        $payment_mode = ucfirst(str_replace('_', ' ', $payment['payment_mode']));
        $reference_number = $payment['gcash_reference_number'] ?? $payment['card_id_number'];

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Membership Payment Receipt - FlexiFit Gym";
        $mail->Body = generateReceiptHTML($member_name, $member_no, $plan_name, $plan_description, 
                                         $amount_paid, $payment_mode, $reference_number, 
                                         $start_date, $end_date, $payment_date);

        $mail->send();
        
        // Update database to mark email as sent
        $update_query = "UPDATE membership_payments SET email_sent = 1 WHERE payment_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['email_sent'] = true;
        
        // Redirect to home page after successful email
        header("Location: /flexifit-project/index.php");
        exit();
        
    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Email Error',
                text: 'Failed to send receipt: {$mail->ErrorInfo}',
                confirmButtonColor: '#FFD700',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Fetch payment details for display
$payment_id = $_SESSION['payment_id'];
$query = "SELECT * FROM membership_payments WHERE payment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Payment Not Found',
            text: 'The payment record could not be found.',
            confirmButtonColor: '#FFD700',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'membership-plans.php';
        });
    </script>";
    exit();
}

// Get member details
$member_query = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$member_result = $stmt->get_result();
$member = $member_result->fetch_assoc();
$stmt->close();

if (!$member) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Member Not Found',
            text: 'Your member record could not be found.',
            confirmButtonColor: '#FFD700',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'membership-plans.php';
        });
    </script>";
    exit();
}

// Format data for display
$member_name = $member['first_name'] . " " . $member['last_name'];
$member_no = str_pad($_SESSION['user_id'], 4, "0", STR_PAD_LEFT);
$start_date = $_SESSION['start_date'];
$end_date = $_SESSION['end_date'];
$payment_date = date("F j, Y", strtotime($payment['payment_date']));

$plan_query = "SELECT name, description FROM membership_plans WHERE plan_id = ?";
$stmt = $conn->prepare($plan_query);
$stmt->bind_param("i", $payment['plan_id']);
$stmt->execute();
$plan_result = $stmt->get_result();
$plan = $plan_result->fetch_assoc();
$stmt->close();

$plan_name = $plan['name'];
$plan_description = $plan['description'];
$amount_paid = number_format($payment['amount'], 2);
$payment_mode = ucfirst(str_replace('_', ' ', $payment['payment_mode']));
$reference_number = $payment['gcash_reference_number'] ?? $payment['card_id_number'];

// HTML Receipt Template Function
function generateReceiptHTML($member_name, $member_no, $plan_name, $plan_description, 
                           $amount_paid, $payment_mode, $reference_number, 
                           $start_date, $end_date, $payment_date) {
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .receipt-container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { max-width: 150px; margin-bottom: 10px; }
            .title { font-size: 24px; font-weight: bold; color: #FF6B00; margin-bottom: 5px; }
            .subtitle { font-size: 16px; color: #666; margin-bottom: 20px; }
            .divider { border-top: 2px solid #FF6B00; margin: 20px 0; }
            .receipt-details { margin-bottom: 30px; }
            .detail-row { display: flex; margin-bottom: 10px; }
            .detail-label { flex: 1; font-weight: bold; color: #555; }
            .detail-value { flex: 2; }
            .amount-row { background-color: #FFF8F0; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .amount-label { font-size: 18px; font-weight: bold; }
            .amount-value { font-size: 24px; color: #FF6B00; font-weight: bold; }
            .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #888; }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="header">
                <div class="title">FLEXIFIT GYM</div>
                <div class="subtitle">Membership Payment Receipt</div>
            </div>
            
            <div class="divider"></div>
            
            <div class="receipt-details">
                <div class="detail-row">
                    <div class="detail-label">Member Name:</div>
                    <div class="detail-value">$member_name</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Membership No:</div>
                    <div class="detail-value">$member_no</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Plan:</div>
                    <div class="detail-value">$plan_name</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">$plan_description</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Start Date:</div>
                    <div class="detail-value">$start_date</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">End Date:</div>
                    <div class="detail-value">$end_date</div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="amount-row">
                <div class="detail-row">
                    <div class="amount-label">Amount Paid:</div>
                    <div class="amount-value">₱$amount_paid</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div class="detail-value">$payment_mode</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Reference No:</div>
                    <div class="detail-value">$reference_number</div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="receipt-details">
                <div class="detail-row">
                    <div class="detail-label">Payment Date:</div>
                    <div class="detail-value">$payment_date</div>
                </div>
            </div>
            
            <div class="footer">
                Thank you for choosing FlexiFit Gym!<br>
                For any inquiries, please contact us at flexifit04@gmail.com
            </div>
        </div>
    </body>
    </html>
HTML;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt | FlexiFit Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Previous styles remain the same */
        
        /* Terms and Conditions Modal Styles */
        .terms-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .terms-content {
            background-color: #121212;
            margin: 5% auto;
            padding: 30px;
            border: 2px solid #FF6B00;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            color: white;
            position: relative;
        }
        
        .terms-header {
            text-align: center;
            margin-bottom: 20px;
            color: #FFD700;
        }
        
        .terms-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 15px;
            background-color: #1E1E1E;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .terms-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .terms-btn {
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .accept-btn {
            background-color: #28a745;
            color: white;
            border: none;
        }
        
        .accept-btn:hover {
            background-color: #218838;
        }
        
        .decline-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .decline-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <?php if ($show_terms): ?>
    <!-- Terms and Conditions Modal -->
    <div id="termsModal" class="terms-modal" style="display: block;">
        <div class="terms-content">
            <div class="terms-header">
                <h2>Terms and Conditions</h2>
                <p>Please read and accept our terms before proceeding</p>
            </div>
            
            <div class="terms-body">
                <h3>FlexiFit Gym Membership Terms and Conditions</h3>
                
                <h4>1. Membership Agreement</h4>
                <p>By accepting these terms, you agree to become a member of FlexiFit Gym and abide by all rules and regulations set forth by the gym.</p>
                
                <h4>2. Payment Terms</h4>
                <p>All membership fees are non-refundable. Payments must be made in full before access to gym facilities is granted.</p>
                
                <h4>3. Membership Duration</h4>
                <p>Your membership begins on the start date specified and ends on the end date. Membership will not automatically renew.</p>
                
                <h4>4. Gym Rules</h4>
                <p>Members must follow all gym rules including proper attire, equipment usage, and respectful behavior towards staff and other members.</p>
                
                <h4>5. Liability Waiver</h4>
                <p>You acknowledge that participation in physical activity carries inherent risks and agree that FlexiFit Gym is not responsible for any injuries sustained while using our facilities.</p>
                
                <h4>6. Cancellation Policy</h4>
                <p>Memberships cannot be cancelled once payment is processed. No refunds will be issued for unused portions of the membership.</p>
                
                <h4>7. Personal Belongings</h4>
                <p>FlexiFit Gym is not responsible for lost or stolen personal items. Lockers are provided for your convenience.</p>
                
                <h4>8. Age Requirement</h4>
                <p>Members must be at least 18 years old or have parental consent to join the gym.</p>
                
                <h4>9. Medical Conditions</h4>
                <p>You certify that you have no medical conditions that would prevent you from safely participating in physical activity, or you have consulted with a physician.</p>
            </div>
            
            <div class="terms-footer">
                <form method="POST" action="receipt.php">
                    <input type="hidden" name="terms_action" value="decline">
                    <button type="submit" class="terms-btn decline-btn">I Disagree</button>
                </form>
                <form method="POST" action="receipt.php">
                    <input type="hidden" name="terms_action" value="accept">
                    <button type="submit" class="terms-btn accept-btn">I Accept</button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Regular Receipt Content -->
    <div class="receipt-container">
        <div class="receipt-header">
            <h1 class="receipt-title">Payment Receipt</h1>
            <p class="receipt-subtitle">FlexiFit Gym Membership</p>
        </div>
        
        <div class="receipt-body">
            <!-- Rest of your receipt content remains the same -->
            <!-- ... -->
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print receipt functionality
        function printReceipt() {
            window.print();
        }
    </script>
</body>
</html>

<?php 
// Close database connection
$conn->close();
include "../includes/footer.php"; 
?>
<?php ob_end_flush(); // At the end of file ?>