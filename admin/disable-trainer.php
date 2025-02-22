<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if (isset($_GET["id"])) {
    $trainer_id = $_GET["id"];
    $sql = "UPDATE trainers SET status='disabled' WHERE trainer_id=$trainer_id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Trainer has been disabled!');
                window.location.href = 'view-trainers.php';
              </script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
