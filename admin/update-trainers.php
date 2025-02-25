<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $trainer_id = $_POST["trainer_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $age = $_POST["age"]; // ✅ Added
    $gender = $_POST["gender"]; // ✅ Added
    $specialty = $_POST["specialty"];
    $availability_status = $_POST["availability_status"];
    $status = $_POST["status"]; // ✅ Added

    // Fetch existing image
    $sql = "SELECT image FROM trainers WHERE trainer_id=$trainer_id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $currentImage = $row["image"];

    // Image upload handling
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $imageName = basename($_FILES["image"]["name"]);
        $targetFilePath = __DIR__ . "/uploads/" . $imageName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            // Delete old image (if not default.png)
            if (!empty($currentImage) && file_exists("uploads/" . $currentImage) && $currentImage !== "default.png") {
                unlink("uploads/" . $currentImage);
            }
            $image = $imageName;
        } else {
            $image = $currentImage; // Keep old image if upload fails
        }
    } else {
        $image = $currentImage; // Keep old image if no new file is uploaded
    }

    // ✅ Fixed SQL Query (Added missing columns)
    $sql = "UPDATE trainers SET 
                first_name='$first_name', 
                last_name='$last_name', 
                email='$email',
                age=$age, 
                gender='$gender', 
                specialty='$specialty', 
                availability_status='$availability_status', 
                image='$image',
                status='$status' 
            WHERE trainer_id=$trainer_id";

    if ($conn->query($sql) === TRUE) {
        echo "Trainer updated successfully!";
        header("Location: view-trainers.php"); // Redirect to trainer list
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
