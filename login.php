<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Include the header
include 'includes/header.php';


// Login logic
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
                } elseif ($user_type === 'trainer') {
                    header("Location: member/index.php");
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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlexiFit</title>
    <!-- Link to your global CSS file (if you have one) -->
    <link rel="stylesheet" href="styles/global.css">
    <style type="text/css">
        /* Login-specific styles */
        body {
            background-color: #000;
            color: #FFD700;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: #111;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0px 0px 20px #FFD700;
            text-align: center;
            width: 600px;
            margin-top: 40px; /* Add margin to separate from the header */
        }
        h2 {
            margin-bottom: 30px;
            font-size: 32px;
        }
        input {
            width: 100%;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #FFD700;
            background: #222;
            color: #FFD700;
            border-radius: 10px;
            font-size: 20px;
        }
        button {
            background: #FFD700;
            color: #000;
            padding: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-size: 22px;
            margin-top: 20px;
        }
        button:hover {
            background: #ffcc00;
        }
        .error {
            color: red;
            margin-top: 20px;
            font-size: 18px;
        }
    </style>
</head>
<body>


<!-- Header is already included via includes/header.php -->


<div class="login-container">
    <h2>Login</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p class="error"><?php echo isset($error) ? $error : ''; ?></p>
</div>


</body>
</html>
