<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
// neo
session_start();
include 'includes/header.php'; // Ensure the path to header.php is correct

// Initialize a variable to store the success message
$registration_successful = false;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture user input
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birthdate = $_POST['birthdate'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $goal = $_POST['goal'];
    $medical_condition = $_POST['medical_condition'];
   
    // Capture selected medical conditions (if any)
    $selected_conditions = isset($_POST['medical_conditions']) ? $_POST['medical_conditions'] : [];
   
    // If "Other" is selected, append the details provided in the text area (but not "Other" itself)
    if (in_array('Other', $selected_conditions) && !empty($_POST['other_details'])) {
        $other_details = $_POST['other_details'];
        // Remove "Other" from the list of selected conditions
        $selected_conditions = array_filter($selected_conditions, function($value) {
            return $value !== 'Other'; // Remove "Other" from the list
        });
        // Append only the user-provided details, not the word "Other"
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

        // Generate username by appending first and last name
        $username = strtolower($first_name . $last_name);  // Use both first and last name for username

        // Prepare the query using placeholders to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, gender, age, birthdate, email, phone_number, address, height, weight, weight_goal, medical_condition, medical_conditions, password, user_type)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user')");

$stmt->bind_param("sssssssssssssss", 
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
    $password 
);


        // Execute the statement
        if ($stmt->execute()) {
            // Set success flag if registration is successful
            $registration_successful = true;
            // Redirect to login page after success
            header('Location: login.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
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
            font-family: Arial, sans-serif;
            background-color: black;
            color: white;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }
        .side-img {
            width: 20%;
            height: 100vh;
            background-size: cover;
            background-position: center;
            position: fixed;
            top: 0;
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
            background-color: #111;
            padding: 20px;
            margin: 20px;
            overflow-y: auto;
            border-radius: 10px;
        }
        h2 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            text-align: left;
            font-weight: bold;
            margin-top: 10px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: none;
            outline: none;
            background: black;
            color: white;
            border-bottom: 2px solid yellow;
        }
        input[type="password"] {
            font-family: Arial, sans-serif;
        }
        .gender, .medical-condition {
            display: flex;
            justify-content: center;
            gap: 10px;
            align-items: center;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }
        .finish-btn {
            background: yellow;
            color: black;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            margin-top: 20px;
        }
        .success-message {
            color: green;
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .radio-container {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 30px;
            background-color: #333;
            color: white;
            cursor: pointer;
        }
        .radio-container input[type="radio"]:checked + label {
            background-color: yellow;
            color: black;
        }
        .radio-container input[type="radio"] {
            display: none;
        }
        textarea[name="other_details"] {
            background: #333;
            color: white;
            border: 2px solid yellow;
            resize: vertical;
        }
    </style>
    <script>
        function toggleMedicalFields() {
            let yesRadio = document.getElementById("medical-yes");
            let noRadio = document.getElementById("medical-no");
            let checkboxes = document.querySelectorAll(".medical-checkbox");
            let otherTextArea = document.getElementById("other-details");
            let otherRadio = document.getElementById("medical-other");


            if (noRadio.checked) {
                checkboxes.forEach(box => box.disabled = true);
                otherRadio.disabled = true;
                otherTextArea.disabled = true;
            } else {
                checkboxes.forEach(box => box.disabled = false);
                otherRadio.disabled = false;
            }
        }


        function toggleOtherDetails() {
            let otherTextArea = document.getElementById("other-details");
            otherTextArea.disabled = !document.getElementById("medical-other").checked;
        }
    </script>
</head>
<body>
    <div class="side-img left-img"></div>
    <div class="container">
        <h2>PERSONAL INFORMATION</h2>


        <!-- Success message -->
        <?php if ($registration_successful): ?>
            <div class="success-message">
                Registration successful! You will be redirected to the login page.
            </div>
        <?php endif; ?>


        <form action="" method="POST">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="First Name" required>


            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>


            <label>Gender</label>
            <div class="gender">
                <input type="radio" name="gender" value="female" id="female" required>
                <label for="female">Female</label>


                <input type="radio" name="gender" value="male" id="male">
                <label for="male">Male</label>


                <input type="radio" name="gender" value="non-binary" id="non-binary">
                <label for="non-binary">Non-Binary</label>
            </div>


            


            
            <label for="birthdate">Birthdate</label>
<input type="date" name="birthdate" id="birthdate" required onchange="calculateAge()">

<label for="age">Age</label>
<input type="number" name="age" id="age" required readonly>

<script>
    function calculateAge() {
        const birthdate = document.getElementById('birthdate').value;
        if (birthdate) {
            const birthDateObj = new Date(birthdate);
            const today = new Date();
            let age = today.getFullYear() - birthDateObj.getFullYear();
            const m = today.getMonth() - birthDateObj.getMonth();

            // Adjust age if birthday hasn't occurred yet this year
            if (m < 0 || (m === 0 && today.getDate() < birthDateObj.getDate())) {
                age--;
            }

            // Set the calculated age in the 'age' input field
            document.getElementById('age').value = age;
        }
    }
</script>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Email" required>


            <label for="phone">Phone No.</label>
            <input type="text" name="phone" id="phone" placeholder="Phone No." maxlength="11" pattern="\d{11}" title="Phone number must be 11 digits" required>


            <label for="address">Address</label>
            <input type="text" name="address" id="address" placeholder="Address" required>


            <label for="height">Height (ft)</label>
            <input type="text" name="height" id="height" placeholder="Height in feet" required>


            <label for="weight">Weight (Kg)</label>
            <input type="text" name="weight" id="weight" placeholder="Weight in Kg" required>


            <label for="goal">Weight Goal (Kg)</label>
            <input type="text" name="goal" id="goal" placeholder="Weight Goal in Kg" required>


            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>


            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>


            <br><br><br>
            <h2>MEDICAL INFORMATION</h2>
            <p>Do you have any medical conditions to be wary about?</p>
            <div class="medical-condition">
                <label><input type="radio" name="medical_condition" id="medical-yes" onclick="toggleMedicalFields()" value="yes"> Yes</label>
                <label><input type="radio" name="medical_condition" id="medical-no" onclick="toggleMedicalFields()" value="no"> No</label>
            </div>


            <div class="checkbox-group">
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Asthma"> Asthma</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Diabetes"> Diabetes</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Heart Disease"> Heart Disease</label>
                <label><input type="checkbox" name="medical_conditions[]" class="medical-checkbox" value="Hypertension"> Hypertension</label>
                <label><input type="checkbox" id="medical-other" name="medical_conditions[]" value="Other" onclick="toggleOtherDetails()"> Others</label>
            </div>


            <textarea name="other_details" id="other-details" placeholder="If others, then please provide details..." disabled></textarea>


            <button class="finish-btn" type="submit">FINISH</button>
        </form>
    </div>
    <div class="side-img right-img"></div>
</body>
</html>