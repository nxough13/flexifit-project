<?php
// db config
$host = "localhost"; 
$user = "root";     
$pass = "";           
$dbname = "flexifit_db"; 
// neo
//create conn
$conn = new mysqli($host, $user, $pass, $dbname);

//check conn
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
