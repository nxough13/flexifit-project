<?php
ob_start(); // Turn on output buffering
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if (isset($_GET["id"])) {
    $plan_id = $_GET["id"];
    $sql = "UPDATE membership_plans SET status='disabled' WHERE plan_id=$plan_id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Plan has been disabled!');
                window.location.href = 'view-plans.php';
              </script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
// neo
$conn->close();
?>
<?php ob_end_flush(); // At the end of file ?>
