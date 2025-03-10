<?php
session_start(); // Start the session to handle success messages

$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';
// neo
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $availability_status = $_POST["availability_status"];
    $status = "active"; // Default status
    if (!empty($_FILES["image"]["name"])) {
        $image = basename($_FILES["image"]["name"]);
        $target_path = "uploads/" . $image;
        
        // Move the uploaded file to the "uploads" directory
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
            // Image uploaded successfully
        } else {
            $_SESSION['error_message'] = "Error uploading image.";
            header("Location: create-trainers.php");
            exit();
        }
    } else {
        $image = "default.png"; // Default image if no file is uploaded
    }
    

    // Insert into trainers table
    $sql = "INSERT INTO trainers (first_name, last_name, email, age, gender, status, image) 
            VALUES ('$first_name', '$last_name', '$email', '$age', '$gender', '$status', '$image')";

    if ($conn->query($sql) === TRUE) {
        $trainer_id = $conn->insert_id; // Get the trainer ID
        
        // Insert specialties into the specialty table and get their IDs
        if (!empty($_POST['specialty'])) {
            foreach ($_POST['specialty'] as $specialty_name) {
                $specialty_name = $conn->real_escape_string($specialty_name);
                
                // Check if the specialty already exists in the specialty table
                $check_sql = "SELECT specialty_id FROM specialty WHERE name = '$specialty_name'";
                $result = $conn->query($check_sql);
                
                if ($result->num_rows > 0) {
                    // Specialty already exists, fetch its ID
                    $row = $result->fetch_assoc();
                    $specialty_id = $row['specialty_id'];
                } else {
                    // Insert new specialty
                    $insert_sql = "INSERT INTO specialty (name) VALUES ('$specialty_name')";
                    if ($conn->query($insert_sql) === TRUE) {
                        $specialty_id = $conn->insert_id; // Get the newly inserted specialty ID
                    } else {
                        $errorMessage = "Error while inserting specialty: " . $conn->error;
                        break;
                    }
                }

                // Insert relationship into trainer_specialty table
                $insert_relation_sql = "INSERT INTO trainer_specialty (trainer_id, specialty_id) 
                                        VALUES ('$trainer_id', '$specialty_id')";
                if (!$conn->query($insert_relation_sql)) {
                    $errorMessage = "Error while linking trainer and specialty: " . $conn->error;
                    break;
                }
            }
        }

        // Set the success message in session and redirect to clear it from the URL
        $_SESSION['success_message'] = "Trainer added successfully!";
        header("Location: create-trainers.php");
        exit(); // Ensure no further code is executed after redirect
    } else {
        $errorMessage = 'Error: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trainer</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #121212; color: yellow; padding: 20px; }
        h2 { color: yellow; text-shadow: 0 0 10px rgba(255, 255, 0, 0.8); margin-bottom: 20px; font-size: 30px; font-weight: bold; text-align: center; margin-top: 50px; }
        .container {
    display: flex;
    justify-content: space-between;
    max-width: 1200px;
    margin: auto;
    background: #1e1e1e;
    padding: 40px;
    border-radius: 10px;
    color: yellow;
    border: 2px solid yellow;
    box-shadow: 0 0 15px rgba(255, 255, 0, 0.8);
} .form-container {
    flex: 1;
    margin-right: 40px;
}
.profile-container {
    flex: 0 0 300px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    border: 2px solid yellow;
    padding: 20px;
    border-radius: 8px;
}
        label { font-weight: bold; margin-top: 10px; display: block; }
        input, select { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid yellow; border-radius: 4px; background: #1e1e1e; color: yellow; }
        .btn, .back-btn, .clear-btn, .specialty-btn { background: yellow; color: black; padding: 12px; border: none; cursor: pointer; width: 48%; border-radius: 4px; font-weight: bold; box-shadow: 0 0 10px rgba(255, 255, 0, 0.8); margin-bottom: 10px; }
        .btn:hover, .back-btn:hover, .clear-btn:hover, .specialty-btn:hover { background: black; color: yellow; border: 2px solid yellow; box-shadow: 0 0 15px rgba(255, 255, 0, 1); }
        .img-preview { width: 200px; height: 200px; object-fit: cover; border-radius: 50%; margin-top: 10px; margin-bottom: 10px; }
        .specialty-container { display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
        .specialty-input { width: 80%; margin-bottom: 10px; }
        .remove-btn { background: red; color: white; border: none; padding: 5px; border-radius: 4px; cursor: pointer; }
        .remove-btn:hover { background: darkred; }
        .back-btn, .clear-btn { background-color: transparent; color: yellow; border: 2px solid yellow; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        .back-btn:hover, .clear-btn:hover { background-color: yellow; color: black; }
        .success-message, .error-message { color: white; padding: 10px; margin-bottom: 20px; text-align: center; }
        .success-message { background-color: green; }
        .error-message { background-color: red; }
    </style>
</head>
<body>
<br>
<br>
<h2>Add New Trainer</h2>

<?php 
// Display success message if it's set in the session
if (isset($_SESSION['success_message'])) { ?>
    <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php } ?>

<?php if (!empty($errorMessage)) { ?>
    <div class="error-message"><?php echo $errorMessage; ?></div>
<?php } ?>

<div class="container">
    <!-- form-container for the input fields -->
    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <label>First Name:</label>
            <input type="text" name="first_name" required>
            
            <label>Last Name:</label>
            <input type="text" name="last_name" required>
            
            <label>Email:</label>
            <input type="email" name="email" required>
            
            <label>Age:</label>
            <input type="number" name="age" min="18" required>
            
            <label>Gender:</label>
            <select name="gender" required>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
            
            <label>Specialty:</label>
            <div class="specialty-container">
                <input type="text" name="specialty[]" class="specialty-input" required>
                <button type="button" class="specialty-btn" onclick="addSpecialty()">Add Specialty</button>
            </div>

            <label>Availability Status:</label>
            <select name="availability_status" required>
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
            </select>
            
            <button type="submit" class="btn">Add Trainer</button>
        
    </div>

    <!-- profile-container for profile image and submit button -->
    <div class="profile-container">
  
        <h3>Profile Image</h3>
        <br>
        <label>Profile Image:</label>
        <br>
        <input type="file" name="image">
        <img id="preview" class="img-preview" src="" alt="Image Preview">

        <br> <br> <br> <br> <br> <br> <br>
        
        <!-- Move the submit button here, inside the form -->
        
        <button type="button" class="back-btn" onclick="window.location.href='view-trainers.php'">Return</button>
        <button type="button" class="clear-btn" onclick="clearForm()">Clear</button>
</form>
    </div>
</div>


        
    </div>
</div>


<script>
   // Function to reset the form and keep the "Add Specialty" button intact
function clearForm() {
    document.querySelector('form').reset();
    document.getElementById('preview').src = '';
    document.querySelector('input[type="file"]').value = '';

    // Reset specialty inputs and remove any "Remove" buttons, but keep the "Add Specialty" button
    const specialtyContainer = document.querySelector('.specialty-container');
    
    // Remove all specialty input fields and their associated remove buttons
    const specialtyInputs = specialtyContainer.querySelectorAll('input');
    specialtyInputs.forEach(input => input.remove());

    const removeButtons = specialtyContainer.querySelectorAll('.remove-btn');
    removeButtons.forEach(button => button.remove());
}




    // Preview the uploaded image
    document.querySelector('input[type="file"]').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function() {
            const preview = document.getElementById('preview');
            preview.src = reader.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    });

    function addSpecialty() {
        const specialtyContainer = document.querySelector('.specialty-container');
        const specialtyInput = document.createElement('input');
        specialtyInput.type = 'text';
        specialtyInput.name = 'specialty[]';
        specialtyInput.classList.add('specialty-input');
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.classList.add('remove-btn');
        removeBtn.textContent = 'Remove';
        removeBtn.onclick = function() {
            specialtyContainer.removeChild(specialtyInput);
            specialtyContainer.removeChild(removeBtn);
        };
        
        specialtyContainer.appendChild(specialtyInput);
        specialtyContainer.appendChild(removeBtn);
    }
</script>

</body>
</html>
