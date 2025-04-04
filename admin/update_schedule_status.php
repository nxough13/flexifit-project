<?php
session_start();
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_errors.log');

$response = ['success' => false, 'message' => ''];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Validate required fields
    $required = ['schedule_id', 'status', 'admin_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize inputs
    $scheduleId = (int)$_POST['schedule_id'];
    $newStatus = trim($_POST['status']);
    $reason = trim($_POST['reason'] ?? '');
    $adminId = (int)$_POST['admin_id'];

    // Validate status
    $validStatuses = ['pending', 'approved', 'cancelled', 'completed'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception("Invalid status value");
    }

    // Database connection
    $conn = new mysqli("localhost", "root", "", "flexifit_db");
    if ($conn->connect_error) {
        throw new Exception("DB connection failed: " . $conn->connect_error);
    }

    // Get schedule and member details
    $stmt = $conn->prepare("
        SELECT s.*, u.email AS member_email, u.first_name, u.last_name
        FROM schedules s
        JOIN members m ON s.member_id = m.member_id
        JOIN users u ON m.user_id = u.user_id
        WHERE s.schedule_id = ?
    ");
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$schedule) {
        throw new Exception("Schedule not found");
    }

    // Update status
    $stmt = $conn->prepare("UPDATE schedules SET status = ? WHERE schedule_id = ?");
    $stmt->bind_param("si", $newStatus, $scheduleId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update status: " . $stmt->error);
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
    $mailSent = false;

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';
        $mail->Password = 'dwnw xuwn baln ljbp';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            file_put_contents(__DIR__ . '/smtp_debug.log', "$level: $str\n", FILE_APPEND);
        };

        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit');
        $mail->addAddress($schedule['member_email'], $schedule['first_name'].' '.$schedule['last_name']);
        
        if ($admin) {
            $mail->addCC($admin['email'], $admin['first_name'].' '.$admin['last_name']);
        }

        $mail->isHTML(true);
        $mail->Subject = "Schedule Update: " . ucfirst($newStatus);
        $mail->Body = "
            <h2>Schedule Status Updated</h2>
            <p>Dear {$schedule['first_name']},</p>
            <p>Your booking has been updated to: <strong>{$newStatus}</strong></p>
            <p>Reason: " . nl2br(htmlspecialchars($reason)) . "</p>
        ";
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        $mailSent = true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
    }

    $response = [
        'success' => true,
        'message' => 'Status updated successfully',
        'mail_sent' => $mailSent,
        'data' => [
            'member_email' => $schedule['member_email'],
            'admin_email' => $admin['email'] ?? null
        ]
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ];
    error_log("Error: " . $e->getMessage());
}

echo json_encode($response);
?>