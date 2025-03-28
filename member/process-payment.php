<?php
ob_start(); // Turn on output buffering
include '../includes/header.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

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

    $_SESSION['payment_gmail'] = $gmail;

    // Handle file upload
    $proof_of_payment = null;
    if (isset($_FILES['proofImage']) && $_FILES['proofImage']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/payment_proofs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['proofImage']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['proofImage']['tmp_name'], $target_path)) {
            $proof_of_payment = $file_name;
        }
    }

    // Set the payment status based on the payment method
    if ($payment_method == 'payOnSite') {
        $status = 'pending';
        $payment_mode = 'cash';
    } else {
        $status = 'completed';
        $payment_mode = $payment_method;
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

    // Insert payment details
    $payment_query = "INSERT INTO membership_payments (member_id, plan_id, amount, gmail, payment_mode, gcash_reference_number, gcash_phone_number, card_type, card_id_number, payment_status, proof_of_payment, payment_date) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param("iidssssssss", $member_id, $selected_plan, $amount_paid, $gmail, $payment_mode, $reference_number, $gcash_number, $card_type, $card_id, $status, $proof_of_payment);
    $stmt->execute();

    // Get the payment_id of the inserted record and store it in the session
    $payment_id = $stmt->insert_id;
    $_SESSION['payment_id'] = $payment_id;
    $stmt->close();

    unset($_SESSION['email_sent']);
    unset($_SESSION['fresh_receipt_page']);

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --primary: #FFD700;
        --primary-dark: #FFC000;
        --dark: #121212;
        --darker: #0A0A0A;
        --light: #F5F5F5;
        --gray: #333333;
        --yellow-glow: 0 0 15px rgba(255, 215, 0, 0.5);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark);
        color: var(--light);
        line-height: 1.6;
        overflow-x: hidden;
    }

    .payment-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 2rem;
        gap: 2rem;
        max-width: 1200px;
        margin: 2rem auto;
    }

    .plan-summary {
        flex: 0 0 48%; /* Fixed width to prevent shrinking */
        min-width: 0; /* Prevent flex item from growing beyond container */
        background: linear-gradient(135deg, var(--darker) 0%, var(--gray) 100%);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--yellow-glow);
        border: 2px solid var(--primary);
        transition: transform 0.3s ease;
    }

    .plan-summary:hover {
        transform: translateY(-5px);
    }

    .plan-summary h2 {
        color: var(--primary);
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        text-align: center;
        border-bottom: 2px solid var(--primary);
        padding-bottom: 0.5rem;
    }

    .plan-details {
        margin-bottom: 2rem;
    }

    .plan-details p {
        margin: 0.8rem 0;
        font-size: 1.1rem;
        display: flex;
        justify-content: space-between;
    }

    .plan-details span {
        color: var(--primary);
        font-weight: 600;
    }

    .back-btn {
        display: inline-block;
        background-color: var(--primary);
        color: var(--dark);
        padding: 0.8rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid var(--primary);
        width: 100%;
        margin-top: 1rem;
    }

    .back-btn:hover {
        background-color: transparent;
        color: var(--primary);
    }

    .payment-methods {
        flex: 0 0 48%; /* Fixed width to match plan summary */
        min-width: 0; /* Prevent flex item from growing beyond container */
        background: linear-gradient(135deg, var(--darker) 0%, var(--gray) 100%);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--yellow-glow);
        border: 2px solid var(--primary);
    }

    .payment-methods h2 {
        color: var(--primary);
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        text-align: center;
        border-bottom: 2px solid var(--primary);
        padding-bottom: 0.5rem;
    }

    .method-options {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .method-option {
        position: relative;
    }

    .method-option input {
        position: absolute;
        opacity: 0;
    }

    .method-option label {
        display: block;
        padding: 1rem;
        background-color: var(--primary);
        color: var(--dark);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid var(--primary);
    }

    .method-option input:checked + label {
        background-color: transparent;
        color: var(--primary);
    }

    .method-option input:focus + label {
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
    }

    .payment-fields-container {
        min-height: 200px; /* Fixed height container */
        position: relative;
    }

    .payment-fields {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .payment-fields.active {
        opacity: 1;
        visibility: visible;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--primary);
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 0.8rem 1rem;
        background-color: var(--darker);
        border: 2px solid var(--primary);
        border-radius: 6px;
        color: var(--light);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
        border-color: var(--primary-dark);
    }

    .form-control::placeholder {
        color: #777;
    }

    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FFD700'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.5rem;
    }

    .proof-upload {
        margin-top: 1.5rem;
        padding: 1.5rem;
        border: 2px dashed var(--primary);
        border-radius: 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .proof-upload:hover {
        background-color: rgba(255, 215, 0, 0.1);
    }

    .proof-upload i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .proof-upload p {
        margin-bottom: 0.5rem;
    }

    .proof-upload input {
        display: none;
    }

    .file-name {
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: var(--primary);
        font-style: italic;
    }

    .submit-btn {
        display: block;
        width: 100%;
        padding: 1rem;
        background-color: var(--primary);
        color: var(--dark);
        border: none;
        border-radius: 6px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1.5rem;
    }

    .submit-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .warning-note {
        color: var(--primary);
        background-color: rgba(255, 215, 0, 0.1);
        padding: 1rem;
        border-radius: 6px;
        margin-top: 1rem;
        border-left: 4px solid var(--primary);
        display: none;
    }

    @media (max-width: 768px) {
        .payment-container {
            flex-direction: column;
            padding: 1rem;
        }
        
        .plan-summary, .payment-methods {
            width: 100%;
            flex: 1 1 100%;
        }
        
        .payment-fields-container {
            min-height: auto;
            position: static;
        }
        
        .payment-fields {
            position: static;
            opacity: 1;
            visibility: visible;
            display: none;
        }
        
        .payment-fields.active {
            display: block;
        }
    }
</style>
</head>
<body>
    <div class="payment-container">
        <div class="plan-summary">
            <h2>Plan Summary</h2>
            <div class="plan-details">
                <p><strong>Plan:</strong> <span><?php echo htmlspecialchars($plan['name']); ?></span></p>
                <p><strong>Price:</strong> <span>₱<?php echo number_format($plan['price'], 2); ?></span></p>
                <p><strong>Duration:</strong> <span><?php echo $plan['duration_days'] ?> days</span></p>
                <p><strong>Start Date:</strong> <span><?php echo $start_date; ?></span></p>
                <p><strong>End Date:</strong> <span><?php echo $end_date; ?></span></p>
                <?php if ($plan['free_training_session'] > 0): ?>
                    <p><strong>Free Sessions:</strong> <span><?php echo $plan['free_training_session'] ?></span></p>
                <?php endif; ?>
            </div>
            <a href="membership-plans.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Choose Different Plan
            </a>
        </div>

        <div class="payment-methods">
            <h2>Payment Details</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="method-options">
                    <div class="method-option">
                        <input type="radio" id="payOnSite" name="paymentMethod" value="payOnSite" checked>
                        <label for="payOnSite"><i class="fas fa-money-bill-wave"></i> Pay On Site</label>
                    </div>
                    <div class="method-option">
                        <input type="radio" id="gcash" name="paymentMethod" value="gcash">
                        <label for="gcash"><i class="fas fa-mobile-alt"></i> GCash</label>
                    </div>
                    <div class="method-option">
                        <input type="radio" id="payWithCard" name="paymentMethod" value="credit_card">
                        <label for="payWithCard"><i class="fas fa-credit-card"></i> Credit/Debit Card</label>
                    </div>
                </div>

                <div class="warning-note" id="payOnSiteWarning">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>NOTE: Membership will be pending until payment is verified by an admin at our facility.</p>
                </div>

                <div class="payment-fields" id="gcashFields">
                    <div class="form-group">
                        <label for="gcashNumber">GCash Number</label>
                        <input type="text" id="gcashNumber" name="gcashNumber" class="form-control" placeholder="09XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label for="referenceNumber">Reference Number</label>
                        <input type="text" id="referenceNumber" name="referenceNumber" class="form-control" placeholder="Enter transaction reference">
                    </div>
                </div>

                <div class="payment-fields" id="cardFields">
                    <div class="form-group">
                        <label for="cardType">Card Type</label>
                        <select id="cardType" name="cardType" class="form-control">
                            <option value="">Select Card Type</option>
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="Amex">American Express</option>
                            <option value="Debit">Debit Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cardID">Card Number (Last 4 digits)</label>
                        <input type="text" id="cardID" name="cardID" class="form-control" placeholder="XXXX XXXX XXXX XXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label for="gmail">Email for Receipt</label>
                    <input type="email" id="gmail" name="gmail" class="form-control" placeholder="your.email@example.com" required>
                </div>

                <div class="proof-upload" onclick="document.getElementById('proofImage').click()">
                    <input type="file" id="proofImage" name="proofImage" accept="image/*">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Upload Proof of Payment</p>
                    <small>(Screenshot, Photo of receipt, etc.)</small>
                    <div class="file-name" id="fileName"></div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Payment
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Payment method toggle
            const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
            const gcashFields = document.getElementById("gcashFields");
            const cardFields = document.getElementById("cardFields");
            const payOnSiteWarning = document.getElementById("payOnSiteWarning");

            function toggleFields() {
                const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                
                // Hide all fields first
                gcashFields.classList.remove("active");
                cardFields.classList.remove("active");
                payOnSiteWarning.style.display = "none";
                
                // Show relevant fields
                if (selectedMethod === "gcash") {
                    gcashFields.classList.add("active");
                } else if (selectedMethod === "credit_card") {
                    cardFields.classList.add("active");
                } else {
                    payOnSiteWarning.style.display = "block";
                }
            }

            paymentMethods.forEach(method => {
                method.addEventListener("change", toggleFields);
            });

            // Initial setup
            toggleFields();

            // File upload display
            document.getElementById('proofImage').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
                document.getElementById('fileName').textContent = fileName;
            });
        });
    </script>
</body>
</html>

<?php include "../includes/footer.php"; ?>
<?php ob_end_flush(); // At the end of file ?>