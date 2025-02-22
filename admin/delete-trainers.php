<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if (isset($_GET['id'])) {
    $trainer_id = $_GET['id'];
    $sql = "DELETE FROM trainers WHERE trainer_id=$trainer_id";

    if ($conn->query($sql) === TRUE) {
        echo "Trainer deleted!";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
