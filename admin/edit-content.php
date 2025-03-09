<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Initialize a variable to store the success message
$content_creation_successful = false;


// Check if the user is logged in and has an admin type
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin') {
    $user_id = $_SESSION['user_id'];  // Retrieve the logged-in admin's user_id
} else {
    // Redirect to login page or show an error message if the user is not logged in as admin
    echo "You must be logged in as an admin to create content.";
    exit();
}


// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture user input
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content_type = $_POST['content_type'];


    // Handle file upload (Make this optional)
    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_path = "";
   
    if (!empty($file_name)) {
        $file_path = "uploads/" . basename($file_name);
        if (!move_uploaded_file($file_tmp, $file_path)) {
            echo "Error uploading the file.";
            exit();
        }
    }


    // Insert content data into the database using the logged-in user's user_id
    $query = "INSERT INTO content (admin_id, title, description, content_type, file_path)
              VALUES ('$user_id', '$title', '$description', '$content_type', '$file_path')";


    if (mysqli_query($conn, $query)) {
        // Set success flag if content is created successfully
        $content_creation_successful = true;


        // Redirect to content.php after success
        header("Location: content.php");
        exit(); // Ensure that the script stops after the redirect
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Content</title>
    <style>
        /* Global styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: #f2f2f2;
            margin: 0;
            padding: 0;
        }


        .container {
            width: 80%;
            margin: 0 auto;
            padding: 40px 20px;
        }


        h2 {
            text-align: center;
            color: #ffcc00;
            font-size: 2rem;
            margin-bottom: 20px;
        }


        /* Form Styling */
        form {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
        }


        label {
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
            color: #ffcc00;
        }


        input[type="text"], textarea, select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #333;
            color: #f2f2f2;
            font-size: 14px;
        }


        input[type="file"] {
            background-color: #333;
            color: #f2f2f2;
            padding: 8px;
            border: 1px solid #444;
            border-radius: 5px;
            font-size: 14px;
        }


        .finish-btn {
            background-color: #ffcc00;
            color: #000;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }


        .finish-btn:hover {
            background-color: #e6b800;
        }


        /* Success and Error messages */
        .success-message, .error-message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
        }


        .success-message {
            background-color: #4CAF50;
            color: white;
        }


        .error-message {
            background-color: #f44336;
            color: white;
        }


        .current-image {
            margin-top: 20px;
            text-align: center;
        }


        .current-image img {
            max-width: 150px;
            border-radius: 5px;
            border: 2px solid #ffcc00;
        }
    </style>
</head>
<body>


    <div class="container">
        <h2>Update Content</h2>


        <!-- Success message -->
        <?php if ($content_creation_successful): ?>
            <div class="success-message">
                Content updated successfully!
            </div>
        <?php endif; ?>


        <form action="" method="POST" enctype="multipart/form-data">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" placeholder="Title" required value="WORKOUT">


            <label for="description">Description</label>
            <textarea name="description" id="description" placeholder="Description" required>WORKOUT WORKOUT hahdsfgdf</textarea>


            <label for="content_type">Content Type</label>
            <select name="content_type" id="content_type" required>
                <option value="guide">Guide</option>
                <option value="tip" selected>Tip</option>
                <option value="announcement">Announcement</option>
                <option value="workout_plan">Workout Plan</option>
                <option value="other">Other</option>
            </select>


            <label for="file">Choose Image (Optional)</label>
            <input type="file" name="file" id="file">


            <!-- Display current image -->
            <div class="current-image">
                <img src="uploads/current_image.jpg" alt="Current Image">
            </div>


            <button class="finish-btn" type="submit">Update Content</button>
        </form>
    </div>
   
</body>
</html>
<?php include '../includes/footer.php'; ?>
