<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $age = trim($_POST['age']);
    $gender = trim($_POST['gender']);
    $phone_number = trim($_POST['phone_number']);
    $user_type = 'guest'; // Default user type

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($password) && !empty($confirm_password) && !empty($age) && !empty($gender) && !empty($phone_number)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } elseif (!is_numeric($age) || $age <= 0) {
            $error = "Please enter a valid age!";
        } elseif (!preg_match("/^[0-9]{10,15}$/", $phone_number)) {
            $error = "Invalid phone number!";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email is already registered!";
            } else {
                // Handle image upload
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $targetDir = "images/"; // Folder to store images
                    $fileName = basename($_FILES["image"]["name"]);
                    $targetFilePath = $targetDir . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

                    // Allowed file types
                    $allowedTypes = array("jpg", "jpeg", "png", "gif");
                    if (in_array($fileType, $allowedTypes)) {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                            $image = $fileName; // Save the file name in the database
                        } else {
                            $error = "File upload failed!";
                        }
                    } else {
                        $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                    }
                }

                // If no error, insert into the database
                if (!isset($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, age, gender, phone_number, user_type, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssissss", $first_name, $last_name, $email, $hashed_password, $age, $gender, $phone_number, $user_type, $image);

                    if ($stmt->execute()) {
                        header("Location: login.php");
                        exit();
                    } else {
                        $error = "Registration failed!";
                    }
                }
            }
            $stmt->close();
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FlexiFit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #000;
            color: #FFD700;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #111;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px #FFD700;
            text-align: center;
            width: 350px;
        }
        h2 {
            margin-bottom: 15px;
        }
        input, select {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #FFD700;
            background: #222;
            color: #FFD700;
            border-radius: 5px;
        }
        button {
            background: #FFD700;
            color: #000;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        button:hover {
            background: #ffcc00;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Register for FlexiFit</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone_number" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <input type="file" name="image">
        <input type="number" name="age" placeholder="Age" required>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
        <button type="submit">Register</button>
    </form>
    <p class="error"><?php echo isset($error) ? $error : ''; ?></p>
</div>

</body>
</html>
