<?php
include '../includes/database.php';

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $query = "
        SELECT mp.payment_proof, mp.payment_mode, m.membership_status
        FROM members m
        JOIN membership_payments mp ON m.member_id = mp.member_id
        WHERE m.user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['payment_proof' => '', 'payment_mode' => 'N/A', 'status' => 'pending']);
    }
}

$conn->close();
?>
