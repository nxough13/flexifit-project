<?php
require 'includes/config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_type = 'member'; // Default user type

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } else {
            $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email is already registered!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $user_type);

                if ($stmt->execute()) {
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Registration failed!";
                }
            }
            $stmt->close();
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<style>
    body {font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; padding: 50px;}
    form {background: #fff; padding: 20px; width: 300px; margin: auto; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    input {width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;}
    button {background: #007bff; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer;}
    button:hover {background: #0056b3;}
    p {color: red; font-size: 14px;}
</style>

<!-- Registration Form -->
<form method="post">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Register</button>
</form>
<p><?php echo isset($error) ? $error : ''; ?></p>
