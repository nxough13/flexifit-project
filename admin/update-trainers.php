<?php
ob_start(); // Turn on output buffering

session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check admin permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an admin to update trainers.";
    header("Location: ../index.php");
    exit();
}

// Ensure trainer ID is present
if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
    $_SESSION['error_message'] = "Trainer ID is missing.";
    header("Location: view-trainers.php");
    exit();
}

$trainer_id = $_GET['trainer_id'];

try {
    // Fetch existing trainer data
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
    $stmt->bind_param("i", $trainer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Trainer not found.");
    }
    
    $trainer = $result->fetch_assoc();
    $existing_image = $trainer['image'];

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize inputs
        $first_name = trim($_POST["first_name"]);
        $last_name = trim($_POST["last_name"]);
        $email = trim($_POST["email"]);
        $age = intval($_POST["age"]);
        $gender = $_POST["gender"];
        $availability_status = $_POST["availability_status"];
        
        // Handle password update
        $password_hash = $trainer['password']; // Default to existing password
        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Handle image upload
        $image = $existing_image; // Default to existing image
        $target_dir = "../uploads/trainers/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            // Validate new image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                throw new Exception("File is not an image.");
            }
            
            // Check file size (max 2MB)
            if ($_FILES["image"]["size"] > 2000000) {
                throw new Exception("Image size must be less than 2MB.");
            }
            
            // Allow certain file formats
            $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
            }
            
            // Generate unique filename
            $image = uniqid("trainer_") . "." . $file_extension;
            $target_path = $target_dir . $image;
            
            // Delete old image if it exists and is not default
            if (!empty($existing_image) && $existing_image != "default.png" && file_exists($target_dir . $existing_image)) {
                unlink($target_dir . $existing_image);
            }
            
            // Upload new image
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
                throw new Exception("Error uploading image.");
            }
        } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
            // Keep existing image if no new one was uploaded
            $image = $_POST['existing_image'];
        } else {
            // Fallback to default image if none provided
            $image = "default.png";
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update trainer details - now includes password in the correct position
            $sql = "UPDATE trainers SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    password = ?,
                    age = ?, 
                    gender = ?, 
                    availability_status = ?,
                    image = ?
                    WHERE trainer_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisssi", 
                $first_name, 
                $last_name, 
                $email, 
                $password_hash,
                $age, 
                $gender, 
                $availability_status, 
                $image, 
                $trainer_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating trainer: " . $conn->error);
            }
            
            // Update specialties
            $stmt = $conn->prepare("DELETE FROM trainer_specialty WHERE trainer_id = ?");
            $stmt->bind_param("i", $trainer_id);
            $stmt->execute();
            
            if (!empty($_POST['specialty'])) {
                foreach ($_POST['specialty'] as $specialty_name) {
                    $specialty_name = trim($specialty_name);
                    if (empty($specialty_name)) continue;
                    
                    // Check if specialty exists
                    $check_stmt = $conn->prepare("SELECT specialty_id FROM specialty WHERE name = ?");
                    $check_stmt->bind_param("s", $specialty_name);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $specialty_id = $row['specialty_id'];
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO specialty (name) VALUES (?)");
                        $insert_stmt->bind_param("s", $specialty_name);
                        if (!$insert_stmt->execute()) {
                            throw new Exception("Error saving specialty: " . $conn->error);
                        }
                        $specialty_id = $conn->insert_id;
                    }
                    
                    // Link trainer to specialty
                    $relation_stmt = $conn->prepare("INSERT INTO trainer_specialty (trainer_id, specialty_id) VALUES (?, ?)");
                    $relation_stmt->bind_param("ii", $trainer_id, $specialty_id);
                    if (!$relation_stmt->execute()) {
                        throw new Exception("Error linking specialty: " . $conn->error);
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Trainer updated successfully!";
            header("Location: view-trainers.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: edit-trainers.php?trainer_id=" . $trainer_id);
    exit();
}

$conn->close();
?>
<?php ob_end_flush(); // At the end of file ?>