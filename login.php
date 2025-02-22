<?php
session_start();
require 'includes/config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $first_name, $last_name, $hashed_password, $user_type);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['user_type'] = $user_type;

                // Redirect based on user type
                if ($user_type === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "User not found!";
        }
        $stmt->close();
    } else {
        $error = "All fields are required!";
    }
}
?>

<style>
    body {font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; padding: 50px;}
    form {background: #fff; padding: 20px; width: 300px; margin: auto; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    input {width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;}
    button {background: #28a745; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer;}
    button:hover {background: #218838;}
    p {color: red; font-size: 14px;}
</style>

<!-- Login Form -->
<form method="post">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
<p><?php echo isset($error) ? $error : ''; ?></p>
