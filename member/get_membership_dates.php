<?php
ob_start(); // Turn on output buffering
session_start();
header("Content-Type: application/json");

// Debugging - log session data
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    error_log("User not authenticated");
    echo json_encode(["error" => "User not authenticated"]);
    exit;
}

$user_id = $_SESSION['user_id'];
error_log("Fetching membership for user_id: $user_id");

$query = "SELECT start_date, end_date FROM members WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(["error" => "Database error"]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    error_log("Found membership: " . print_r($row, true));
    echo json_encode([
        "success" => true,
        "start_date" => $row['start_date'],
        "end_date" => $row['end_date']
    ]);
} else {
    error_log("No membership found for user_id: $user_id");
    echo json_encode([
        "error" => "No active membership found",
        "success" => false
    ]);
}

$conn->close();
?>
<?php ob_end_flush(); // At the end of file ?>