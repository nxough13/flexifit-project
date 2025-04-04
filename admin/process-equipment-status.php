<?php
// process-equipment-status.php
require_once '../vendor/autoload.php'; // Path to PHPMailer autoload
require_once '../includes/db_connect.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$inventoryId = $data['inventory_id'] ?? null;
$actionType = $data['action_type'] ?? null;
$reason = $data['reason'] ?? '';
$notifyAdmin = $data['notify_admin'] ?? false;
$notifyMain = $data['notify_main'] ?? false;

if (!$inventoryId || !$actionType) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Update equipment status
    $newStatus = $actionType === 'disable' ? 'disabled' : 'active';
    $stmt = $conn->prepare("UPDATE equipment_inventory SET active_status = ? WHERE inventory_id = ?");
    $stmt->bind_param("si", $newStatus, $inventoryId);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No changes made to the equipment status.");
    }
    
    // Get equipment details for email
    $stmt = $conn->prepare("
        SELECT e.name, ei.identifier 
        FROM equipment_inventory ei
        JOIN equipment e ON ei.equipment_id = e.equipment_id
        WHERE ei.inventory_id = ?
    ");
    $stmt->bind_param("i", $inventoryId);
    $stmt->execute();
    $equipment = $stmt->get_result()->fetch_assoc();
    
    // Send emails if requested
    if ($notifyAdmin || $notifyMain) {
        $adminEmail = $_SESSION['email'];
        $adminName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        $equipmentName = $equipment['name'] . ' (' . $equipment['identifier'] . ')';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'flexifit04@gmail.com'; // Your email
            $mail->Password   = 'your_password_here'; // Your email password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Recipients
            if ($notifyAdmin) {
                $mail->addAddress($adminEmail, $adminName);
            }
            if ($notifyMain) {
                $mail->addAddress('flexifit04@gmail.com', 'FlexiFit Admin');
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Equipment ' . ucfirst($actionType) . 'd: ' . $equipmentName;
            
            $emailBody = "
                <h2>Equipment Status Change Notification</h2>
                <p><strong>Equipment:</strong> {$equipmentName}</p>
                <p><strong>Action:</strong> " . ucfirst($actionType) . "d</p>
                <p><strong>Reason:</strong> {$reason}</p>
                <p><strong>Changed by:</strong> {$adminName} ({$adminEmail})</p>
                <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            ";
            
            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags($emailBody);
            
            $mail->send();
        } catch (Exception $e) {
            // Log email error but don't fail the operation
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Equipment has been " . $actionType . "d successfully."
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Error: " . $e->getMessage()
    ]);
}