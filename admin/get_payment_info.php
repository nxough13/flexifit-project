<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
header('Content-Type: application/json');

// Debug - log the request
error_log("GET request: " . print_r($_GET, true));

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    $query = "SELECT mp.proof_of_payment, mp.payment_mode 
              FROM membership_payments mp
              JOIN members m ON mp.member_id = m.member_id
              WHERE m.user_id = ?
              ORDER BY mp.payment_date DESC
              LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        // Verify file exists
        if ($data['proof_of_payment']) {
            $proof_path = '../uploads/payment_proofs/' . $data['proof_of_payment'];
            if (!file_exists($proof_path)) {
                error_log("File not found: " . $proof_path);
                $data['proof_of_payment'] = null;
            }
        }
        echo json_encode($data);
    } else {
        echo json_encode([
            'proof_of_payment' => null,
            'payment_mode' => null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    error_log("Error in get_payment_info: " . $e->getMessage());
}

$conn->close();
?>