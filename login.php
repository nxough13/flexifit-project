<?php
session_start();


// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";


$conn = new mysqli($host, $user, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Login logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }


    $email = trim($_POST['email']);
    $password = trim($_POST['password']);


    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, password, user_type FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }


        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();


        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $first_name, $last_name, $hashed_password, $user_type);
            $stmt->fetch();


            if (password_verify($password, $hashed_password)) {
                // Regenerate session ID for security
                session_regenerate_id(true);


                // Store user data in session
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


// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlexiFit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            background-image: url('../images/background.jpg'); /* Add your image path */
            background-size: cover; /* Cover the entire screen */
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Prevent image repetition */
            color: white;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Ensure the body takes at least the full viewport height */
        }


        /* Header styling */
        header {
            width: 100%;
            background: rgba(17, 17, 17, 0.9);
            padding: 20px 0;
            text-align: center;
            position: absolute;
            top: 0;
        }


        /* Login container styling */
        .login-container {
            background: rgba(17, 17, 17, 0.9);
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0px 0px 30px rgba(255, 215, 0, 0.5);
            text-align: center;
            width: 100%;
            max-width: 600px;
        }


        h2 {
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
        }


        /* Input field styling */
        input {
            width: 100%;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #FFD700;
            background: rgba(34, 34, 34, 0.8);
            color: #FFD700;
            border-radius: 10px;
            font-size: 18px;
            transition: border-color 0.3s ease;
        }


        input:focus {
            border-color: #ffcc00;
            outline: none;
        }


        /* Button styling */
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
            transition: background-color 0.3s ease;
        }


        button:hover {
            background: #ffcc00;
        }


        /* Error message styling */
        .error {
            color: #ff4444;
            margin-top: 20px;
            font-size: 18px;
        }


        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                padding: 30px;
                margin: 20px;
            }


            h2 {
                font-size: 28px;
            }


            input {
                padding: 15px;
                font-size: 16px;
            }


            button {
                padding: 15px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit">Login</button>
        </form>
        <p class="error"><?php echo isset($error) ? htmlspecialchars($error) : ''; ?></p>
    </div>
</body>
</html>
