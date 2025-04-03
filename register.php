<?php
ob_start(); // Turn on output buffering
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

session_start();
include 'includes/header.php';

// Initialize a variable to store the success message
$registration_successful = false;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture user input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birthdate = $_POST['birthdate'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $goal = $_POST['goal'];
    $medical_condition = $_POST['medical_condition'];
    $user_type = 'non-member'; // Explicitly set user type
   
    // Generate username by combining first and last name (lowercase, no spaces)
    $username = strtolower(str_replace(' ', '', $first_name . $last_name));
    
    // Check if username already exists and append a number if it does
    $original_username = $username;
    $counter = 1;
    while (true) {
        $check_username = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $check_username->store_result();
        
        if ($check_username->num_rows == 0) {
            break; // Username is available
        }
        
        $username = $original_username . $counter;
        $counter++;
    }

    // Capture selected medical conditions (if any)
    $selected_conditions = isset($_POST['medical_conditions']) ? $_POST['medical_conditions'] : [];
   
    // If "Other" is selected, append the details provided in the text area
    if (in_array('Other', $selected_conditions) && !empty($_POST['other_details'])) {
        $other_details = trim($_POST['other_details']);
        // Remove "Other" from the list of selected conditions
        $selected_conditions = array_filter($selected_conditions, function($value) {
            return $value !== 'Other';
        });
        // Append only the user-provided details
        $selected_conditions[] = $other_details;
    }

    // Convert the selected conditions into a comma-separated string
    $selected_conditions_str = implode(", ", $selected_conditions);

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Hash the password for security
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Handle file uploads
        $medical_certificate_path = null;

        // Upload medical certificate if provided
        if (!empty($_FILES['medical_certificate']['name'])) {
            $target_dir = '../images/medical_certificates/';
           
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
           
            $file_ext = strtolower(pathinfo($_FILES['medical_certificate']['name'], PATHINFO_EXTENSION));
            $new_filename = $username . '_certificate_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
           
            // Check file size (max 5MB)
            if ($_FILES['medical_certificate']['size'] > 5000000) {
                echo "<script>alert('Medical certificate file is too large (max 5MB allowed)!');</script>";
            } else {
                // Allow certain file formats
                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
                if (in_array($file_ext, $allowed_types)) {
                    if (move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $target_file)) {
                        // Store relative path (without the ../images/)
                        $medical_certificate_path = 'medical_certificates/' . $new_filename;
                    } else {
                        echo "<script>alert('Error uploading medical certificate!');</script>";
                    }
                } else {
                    echo "<script>alert('Only PDF, JPG, JPEG, and PNG files are allowed for medical certificates!');</script>";
                }
            }
        }

        // Prepare the query with proper user_type and file paths
        $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, gender, age, birthdate, email, phone_number, address, height, weight, weight_goal, medical_condition, medical_conditions, password, user_type, medical_certificate)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssisssssdssssss",
            $username,
            $first_name,
            $last_name,
            $gender,
            $age,
            $birthdate,
            $email,
            $phone,
            $address,
            $height,
            $weight,
            $goal,
            $medical_condition,
            $selected_conditions_str,
            $password,
            $user_type,
            $medical_certificate_path
        );

        // Execute the statement
        if ($stmt->execute()) {
            $registration_successful = true;
            header('Location: login.php');
            exit();
        } else {
            echo "<script>alert('Error: " . addslashes($stmt->error) . "');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #000;
            color: #fff;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
       
        .side-img {
            width: 20%;
            height: 100vh;
            background-size: cover;
            background-position: center;
            position: fixed;
            top: 0;
            z-index: -1;
        }
       
        .left-img {
            background-image: url('images/left-image.jpg');
            left: 0;
        }
       
        .right-img {
            background-image: url('images/right-image.jpg');
            right: 0;
        }
       
        .container {
            width: 80%;
            max-width: 800px;
            background-color: rgba(17, 17, 17, 0.9);
            padding: 30px;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }
       
        h2 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            color: #FFD700;
            font-size: 1.8rem;
        }
       
        label {
            display: block;
            text-align: left;
            font-weight: bold;
            margin-top: 15px;
            color: #FFD700;
        }
       
        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            border: none;
            outline: none;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-bottom: 2px solid #FFD700;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
       
        input:focus, select:focus, textarea:focus {
            border-color: #ffcc00;
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.4);
        }
       
        .gender, .medical-condition {
            display: flex;
            justify-content: center;
            gap: 15px;
            align-items: center;
            margin: 15px 0;
        }
       
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin: 15px 0;
        }
       
        .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 5px 0;
    cursor: pointer;
    padding: 8px 15px;
    border-radius: 20px;
    background: linear-gradient(to bottom, #444, #333);
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.1),
                inset 0 -2px 2px rgba(0, 0, 0, 0.2),
                inset 0 2px 2px rgba(255, 255, 255, 0.05);
    border: 1px solid #555;
}


.checkbox-group label:hover {
    background: linear-gradient(to bottom, #555, #444);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15),
                0 2px 4px rgba(0, 0, 0, 0.2),
                inset 0 -2px 2px rgba(0, 0, 0, 0.2),
                inset 0 2px 2px rgba(255, 255, 255, 0.1);
}


.checkbox-group input[type="checkbox"]:checked + span {
    background: linear-gradient(to bottom, #FFD700, #e6c200);
    color: #000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2),
                inset 0 1px 2px rgba(255, 255, 255, 0.3),
                inset 0 -2px 2px rgba(0, 0, 0, 0.2);
}
.finish-btn {
    background: linear-gradient(to bottom, #FFD700, #e6c200);
    color: #000;
    font-weight: bold;
    border: none;
    padding: 15px 25px;
    cursor: pointer;
    margin-top: 25px;
    width: 100%;
    border-radius: 8px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2),
                0 2px 4px rgba(0, 0, 0, 0.2),
                inset 0 -3px 5px rgba(0, 0, 0, 0.1),
                inset 0 3px 5px rgba(255, 255, 255, 0.2);
    text-transform: uppercase;
    letter-spacing: 1px;
    border: 1px solid #e6c200;
}


.finish-btn:hover {
    background: linear-gradient(to bottom, #e6c200, #d9b800);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25),
                0 3px 6px rgba(0, 0, 0, 0.25),
                inset 0 -3px 5px rgba(0, 0, 0, 0.15),
                inset 0 3px 5px rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}


.finish-btn:active {
    transform: translateY(1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2),
                inset 0 -1px 3px rgba(0, 0, 0, 0.2),
                inset 0 1px 3px rgba(255, 255, 255, 0.1);
}
       
        .success-message {
            color: #4CAF50;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 5px;
        }
       
        .radio-container {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    border-radius: 30px;
    background: linear-gradient(to bottom, #444, #333);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.2),
                inset 0 -2px 2px rgba(0, 0, 0, 0.3),
                inset 0 2px 2px rgba(255, 255, 255, 0.1);
    border: 1px solid #555;
}


.radio-container:hover {
    background: linear-gradient(to bottom, #555, #444);
    box-shadow: 0 5px 8px rgba(0, 0, 0, 0.15),
                0 2px 4px rgba(0, 0, 0, 0.25),
                inset 0 -2px 2px rgba(0, 0, 0, 0.3),
                inset 0 2px 2px rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}


input[type="radio"]:checked + label {
    background: linear-gradient(to bottom, #FFD700, #e6c200);
    color: #000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2),
                inset 0 1px 2px rgba(255, 255, 255, 0.3),
                inset 0 -2px 2px rgba(0, 0, 0, 0.2);
}
       
        textarea[name="other_details"] {
            background: #333;
            color: white;
            border: 2px solid #FFD700;
            resize: vertical;
            min-height: 80px;
        }
       
        .file-upload {
            margin: 20px 0;
        }
       
        .file-upload-label {
            display: block;
            padding: 12px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px dashed #FFD700;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
       
        .file-upload-label:hover {
            background: rgba(0, 0, 0, 0.9);
        }
       
        .file-name {
            margin-top: 5px;
            font-size: 0.9rem;
            color: #aaa;
        }
       
        @media (max-width: 768px) {
            .side-img {
                display: none;
            }
           
            .container {
                width: 95%;
                padding: 20px;
            }
           
            .gender, .medical-condition {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
    <script>
        function toggleMedicalFields() {
    let yesRadio = document.getElementById("medical-yes");
    let noRadio = document.getElementById("medical-no");
    let checkboxes = document.querySelectorAll(".medical-checkbox");
    let otherTextArea = document.getElementById("other-details");
    let otherRadio = document.getElementById("medical-other");
    let fileUpload = document.getElementById("medical_certificate");


    if (noRadio.checked) {
        checkboxes.forEach(box => {
            box.checked = false;
            box.disabled = true;
        });
        otherRadio.disabled = true;
        otherTextArea.disabled = true;
        otherTextArea.value = "";
        fileUpload.disabled = true;
        // Reset the file input
        fileUpload.value = "";
        document.getElementById("certificate-file-name").textContent = "No file chosen";
    } else {
        checkboxes.forEach(box => box.disabled = false);
        otherRadio.disabled = false;
        fileUpload.disabled = false;
    }
}


        function toggleOtherDetails() {
            let otherTextArea = document.getElementById("other-details");
            otherTextArea.disabled = !document.getElementById("medical-other").checked;
            if (!otherTextArea.disabled) {
                otherTextArea.focus();
            }
        }
       
        function calculateAge() {
            const birthdate = document.getElementById('birthdate').value;
            if (birthdate) {
                const birthDateObj = new Date(birthdate);
                const today = new Date();
                let age = today.getFullYear() - birthDateObj.getFullYear();
                const m = today.getMonth() - birthDateObj.getMonth();


                if (m < 0 || (m === 0 && today.getDate() < birthDateObj.getDate())) {
                    age--;
                }


                document.getElementById('age').value = age;
            }
        }
       
        function displayFileName(input, labelId) {
            const fileName = input.files[0] ? input.files[0].name : "No file chosen";
            document.getElementById(labelId).textContent = fileName;
        }
    </script>
</head>
<body>
    <div class="side-img left-img"></div>
    <div class="container">
        <h2>PERSONAL INFORMATION</h2>


        <?php if ($registration_successful): ?>
            <div class="success-message">
                Registration successful! You will be redirected to the login page.
            </div>
        <?php endif; ?>


        <form action="" method="POST" enctype="multipart/form-data">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="First Name" required>


            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>


            <label>Gender</label>
            <div class="gender">
                <div class="radio-container">
                    <input type="radio" name="gender" value="female" id="female" required>
                    <label for="female">Female</label>
                </div>
                <div class="radio-container">
                    <input type="radio" name="gender" value="male" id="male">
                    <label for="male">Male</label>
                </div>
                <div class="radio-container">
                    <input type="radio" name="gender" value="non-binary" id="non-binary">
                    <label for="non-binary">Non-Binary</label>
                </div>
            </div>


            <label for="birthdate">Birthdate</label>
            <input type="date" name="birthdate" id="birthdate" required onchange="calculateAge()">


            <label for="age">Age</label>
            <input type="number" name="age" id="age" required readonly>


            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Email" required>


            <label for="phone">Phone No.</label>
            <input type="text" name="phone" id="phone" placeholder="Phone No." maxlength="11" pattern="\d{11}" title="Phone number must be 11 digits" required>


            <label for="address">Address</label>
            <input type="text" name="address" id="address" placeholder="Address" required>


            <label for="height">Height (cm)</label>
            <input type="number" name="height" id="height" placeholder="Height in centimeters" required>


            <label for="weight">Weight (Kg)</label>
            <input type="number" name="weight" id="weight" placeholder="Weight in Kg" required step="0.1">


            <label for="goal">Weight Goal (Kg)</label>
            <input type="number" name="goal" id="goal" placeholder="Weight Goal in Kg" required step="0.1">


            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>


            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>


            <h2>MEDICAL INFORMATION</h2>
            <p>Do you have any medical conditions to be wary about?</p>
            <div class="medical-condition">
                <div class="radio-container">
                    <input type="radio" name="medical_condition" id="medical-yes" onclick="toggleMedicalFields()" value="yes">
                    <label for="medical-yes">Yes</label>
                </div>
                <div class="radio-container">
                    <input type="radio" name="medical_condition" id="medical-no" onclick="toggleMedicalFields()" value="no" checked>
                    <label for="medical-no">No</label>
                </div>
            </div>


            <div class="checkbox-group">
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Asthma" disabled> Asthma</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Diabetes" disabled> Diabetes</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Heart Disease" disabled> Heart Disease</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Hypertension" disabled> Hypertension</label>
                <label><input type="checkbox" id="medical-other" name="medical_conditions[]" value="Other" onclick="toggleOtherDetails()" disabled> Others</label>
            </div>


            <textarea name="other_details" id="other-details" placeholder="If others, please provide details..." disabled></textarea>


            <div class="file-upload">
                <label>Medical Certificate (PDF, JPG, PNG - max 5MB)</label>
                <label for="medical_certificate" class="file-upload-label">
                    Click to upload Medical Certificate
                    <input type="file" id="medical_certificate" name="medical_certificate" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" onchange="displayFileName(this, 'certificate-file-name')">
                </label>
                <div id="certificate-file-name" class="file-name">No file chosen</div>
            </div>


       


            <button class="finish-btn" type="submit">COMPLETE REGISTRATION</button>
        </form>
    </div>
    <div class="side-img right-img"></div>
</body>
</html>
<?php ob_end_flush(); // At the end of file ?>