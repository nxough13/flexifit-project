<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMsg = $errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $specialty = $_POST["specialty"];
    $availability_status = $_POST["availability_status"];

    // Image upload handling
    $image = "default.png"; // Default image
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $imageName = basename($_FILES["image"]["name"]);
        $targetFilePath = __DIR__ . "/uploads/" . $imageName;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $image = $imageName;
        }
    }

    // Insert into database
    $sql = "INSERT INTO trainers (first_name, last_name, email, specialty, availability_status, image) 
            VALUES ('$first_name', '$last_name', '$email', '$specialty', '$availability_status', '$image')";

    if ($conn->query($sql) === TRUE) {
        // Redirect to prevent form resubmission
        header("Location: view-trainers.php");
        exit(); // Ensure script stops execution after redirect
    } else {
        $errorMsg = "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Trainer</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; }
        form { background: white; padding: 20px; margin: 50px auto; width: 300px; border-radius: 5px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
        .btn { background: #28a745; color: white; padding: 10px; border: none; cursor: pointer; }
        .msg { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<h2>Add New Trainer</h2>

<?php if ($errorMsg) echo "<p class='error'>$errorMsg</p>"; ?>

<form action="" method="POST" enctype="multipart/form-data">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="specialty" placeholder="Specialty">
    <select name="availability_status">
        <option value="available">Available</option>
        <option value="unavailable">Unavailable</option>
    </select>
    <input type="file" name="image" accept="image/*">
    <button type="submit" class="btn">Add Trainer</button>
</form>

</body>
</html>
