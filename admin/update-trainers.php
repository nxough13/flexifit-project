<?php
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure trainer ID is present
if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
    die("Error: Trainer ID is missing.");
}
$trainer_id = $_GET['trainer_id'];

// Fetch existing trainer data to keep the old image if no new one is uploaded
$sql = "SELECT image FROM trainers WHERE trainer_id = '$trainer_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Trainer not found.";
    header("Location: view-trainers.php"); // Redirect to view-trainers.php in case of an error
    exit();
}

$trainer = $result->fetch_assoc();
$existing_image = $trainer['image'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $conn->real_escape_string($_POST["first_name"]);
    $last_name = $conn->real_escape_string($_POST["last_name"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $age = $conn->real_escape_string($_POST["age"]);
    $gender = $conn->real_escape_string($_POST["gender"]);
    $availability_status = $conn->real_escape_string($_POST["availability_status"]);

    // Handle Image Upload (keeping the old one if no new image is provided)
    if (!empty($_FILES["image"]["name"])) {
        $image = basename($_FILES["image"]["name"]);
        $target_path = "uploads/" . $image; // Save in the `uploads` folder directly
        
        // Check if image is uploaded successfully before updating the database
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
            // Image uploaded successfully, use the new image name
        } else {
            $_SESSION['error_message'] = "Error uploading image.";
            header("Location: edit-trainers.php?trainer_id=$trainer_id"); // Redirect to edit page in case of error
            exit();
        }
    } else {
        // If no new image is uploaded, keep the old image
        $image = $existing_image;
    }

    // Update trainer details
    $sql = "UPDATE trainers SET 
            first_name = '$first_name', 
            last_name = '$last_name', 
            email = '$email', 
            age = '$age', 
            gender = '$gender', 
            availability_status = '$availability_status',
            image = '$image'
            WHERE trainer_id = '$trainer_id'";

    if ($conn->query($sql) === TRUE) {
        // Update specialties
        if (!empty($_POST['specialty'])) {
            $conn->query("DELETE FROM trainer_specialty WHERE trainer_id = '$trainer_id'");

            foreach ($_POST['specialty'] as $specialty_name) {
                $specialty_name = $conn->real_escape_string($specialty_name);
                $check_sql = "SELECT specialty_id FROM specialty WHERE name = '$specialty_name'";
                $result = $conn->query($check_sql);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $specialty_id = $row['specialty_id'];
                } else {
                    $conn->query("INSERT INTO specialty (name) VALUES ('$specialty_name')");
                    $specialty_id = $conn->insert_id;
                }

                $conn->query("INSERT INTO trainer_specialty (trainer_id, specialty_id) VALUES ('$trainer_id', '$specialty_id')");
            }
        }
// neo
        $_SESSION['success_message'] = "Trainer updated successfully!";
        header("Location: view-trainers.php"); // Redirect to the view-trainers.php page
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating trainer: " . $conn->error;
        header("Location: view-trainers.php"); // Redirect to view-trainers.php in case of error
        exit();
    }
}

$conn->close();
?>
