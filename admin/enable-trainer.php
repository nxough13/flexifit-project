<?php
session_start();
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/trainer_errors.log');

$response = ['success' => false, 'message' => '', 'mail_sent' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $required = ['trainer_id', 'reason', 'admin_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $trainerId = (int)$_POST['trainer_id'];
    $reason = trim($_POST['reason']);
    $adminId = (int)$_POST['admin_id'];

    $conn = new mysqli("localhost", "root", "", "flexifit_db");
    if ($conn->connect_error) {
        throw new Exception("DB connection failed");
    }

    // Get trainer details
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $trainer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$trainer) {
        throw new Exception("Trainer not found");
    }

    // Update status
    $stmt = $conn->prepare("UPDATE trainers SET status = 'active' WHERE trainer_id = ?");
    $stmt->bind_param("i", $trainerId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to enable trainer");
    }
    $stmt->close();

    // Get admin details
    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';
        $mail->Password = 'dwnw xuwn baln ljbp';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit Admin');
        $mail->addAddress($trainer['email'], $trainer['first_name'].' '.$trainer['last_name']);
        
        if ($admin) {
            $mail->addCC($admin['email'], $admin['first_name'].' '.$admin['last_name']);
        }

        $mail->isHTML(true);
        $mail->Subject = "Your Trainer Account Has Been Enabled";
        $mail->Body = "
            <h2>Account Enabled</h2>
            <p>Dear {$trainer['first_name']},</p>
            <p>Your trainer account has been enabled by the FlexiFit admin team.</p>
            <p><strong>Note:</strong> " . nl2br(htmlspecialchars($reason)) . "</p>
            <p>You can now log in and accept bookings.</p>
        ";
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        $response['mail_sent'] = true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
    }

    $response = [
        'success' => true,
        'message' => 'Trainer enabled successfully',
        'mail_sent' => $response['mail_sent'],
        'data' => [
            'trainer_email' => $trainer['email'],
            'admin_email' => $admin['email'] ?? null
        ]
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Error in enable-trainer.php: " . $e->getMessage());
}

echo json_encode($response);
?>