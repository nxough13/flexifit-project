<?php
ob_start(); // Turn on output buffering
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Get the input data
$input = json_decode(file_get_contents('php://input'), true);
$trainerId = $_GET['trainer_id'] ?? null;
$reason = $input['reason'] ?? '';


// Validate input
if (!$trainerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Trainer ID is required']);
    exit;
}


try {
    // Update the trainer status
    $stmt = $conn->prepare("UPDATE trainers SET status = 'active' WHERE trainer_id = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
   
    // Here you might want to log the action with the reason
    // logAction($trainerId, 'enabled', $reason);
   
    echo json_encode(['success' => true, 'message' => 'Trainer enabled successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}


$conn->close();
?>
<?php ob_end_flush(); // At the end of file ?>