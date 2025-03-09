<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if (isset($_GET["id"])) {
    $plan_id = $_GET["id"];
    $sql = "UPDATE membership_plans SET status='active' WHERE plan_id=$plan_id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Plan has been re-enabled!');
                window.location.href = 'view-plans.php';
              </script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
