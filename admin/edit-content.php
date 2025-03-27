<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Initialize variables
$content_creation_successful = false;
$content_id = isset($_GET['content_id']) ? $_GET['content_id'] : null; // Get content ID from URL
$content_data = null;


// Check if the user is logged in and has an admin type
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin') {
    $user_id = $_SESSION['user_id'];  // Retrieve the logged-in admin's user_id
} else {
    // Redirect to login page or show an error message if the user is not logged in as admin
    echo "You must be logged in as an admin to create or edit content.";
    exit();
}


// Fetch content data if editing
if ($content_id) {
    $query = "SELECT * FROM content WHERE content_id = '$content_id' AND admin_id = '$user_id'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $content_data = mysqli_fetch_assoc($result);
    } else {
        echo "Content not found or you do not have permission to edit it.";
        exit();
    }
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture user input
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content_type = $_POST['content_type'];


    // Handle image upload (optional)
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_path = $content_data ? $content_data['image'] : ''; // Keep existing image path if no new image is uploaded


    // Handle file upload (optional)
    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_path = $content_data ? $content_data['file_path'] : ''; // Keep existing file path if no new file is uploaded


    // If a new image is uploaded, move it to the 'uploads' folder
    if (!empty($image_name)) {
        // Delete the old image if it exists
        if (!empty($content_data['image']) && file_exists($content_data['image'])) {
            unlink($content_data['image']); // Delete the old image
        }


        // Upload the new image
        $image_path = "uploads/" . basename($image_name);
        if (!move_uploaded_file($image_tmp, $image_path)) {
            echo "Error uploading the image.";
            exit();
        }
    }


    // If a new file is uploaded, move it to the 'uploads' folder
    if (!empty($file_name)) {
        // Delete the old file if it exists
        if (!empty($content_data['file_path']) && file_exists($content_data['file_path'])) {
            unlink($content_data['file_path']); // Delete the old file
        }


        // Upload the new file
        $file_path = "uploads/" . basename($file_name);
        if (!move_uploaded_file($file_tmp, $file_path)) {
            echo "Error uploading the file.";
            exit();
        }
    }


    // Update or insert content in the database
    if ($content_id) {
        $query = "UPDATE content
                  SET title = '$title', description = '$description', content_type = '$content_type', image = '$image_path', file_path = '$file_path'
                  WHERE content_id = '$content_id' AND admin_id = '$user_id'";
    } else {
        $query = "INSERT INTO content (admin_id, title, description, content_type, image, file_path)
                  VALUES ('$user_id', '$title', '$description', '$content_type', '$image_path', '$file_path')";
    }


    if (mysqli_query($conn, $query)) {
        $content_creation_successful = true;
        header("Location: content.php"); // Redirect to content list after success
        exit();
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
    <title><?php echo $content_id ? 'Edit' : 'Create'; ?> Content</title>
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


        .current-image, .current-file {
            margin-top: 20px;
            text-align: center;
        }


        .current-file p {
            color: #ffcc00;
            font-weight: bold;
        }


    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $content_id ? 'Edit' : 'Create'; ?> Content</h2>


        <?php if ($content_creation_successful): ?>
            <div class="success-message">
                Content <?php echo $content_id ? 'updated' : 'created'; ?> successfully!
            </div>
        <?php endif; ?>


        <form action="" method="POST" enctype="multipart/form-data">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" placeholder="Title" required
                   value="<?php echo $content_data ? htmlspecialchars($content_data['title']) : ''; ?>">


            <label for="description">Description</label>
            <textarea name="description" id="description" placeholder="Description" required>
                <?php echo $content_data ? htmlspecialchars($content_data['description']) : ''; ?>
            </textarea>


            <label for="content_type">Content Type</label>
            <select name="content_type" id="content_type" required>
                <option value="guide" <?php echo ($content_data && $content_data['content_type'] == 'guide') ? 'selected' : ''; ?>>Guide</option>
                <option value="tip" <?php echo ($content_data && $content_data['content_type'] == 'tip') ? 'selected' : ''; ?>>Tip</option>
                <option value="announcement" <?php echo ($content_data && $content_data['content_type'] == 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                <option value="workout_plan" <?php echo ($content_data && $content_data['content_type'] == 'workout_plan') ? 'selected' : ''; ?>>Workout Plan</option>
                <option value="other" <?php echo ($content_data && $content_data['content_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
            </select>


            <label for="image">Upload Image (Optional)</label>
            <input type="file" name="image" id="image">


            <?php if ($content_data && !empty($content_data['image'])): ?>
                <div class="current-image">
                    <p>If you want to replace the current image, choose a new image above.</p>
                </div>
            <?php endif; ?>


            <label for="file">Upload File (Optional)</label>
            <input type="file" name="file" id="file">


            <?php if ($content_data && !empty($content_data['file_path'])): ?>
                <div class="current-file">
                    <p>If you want to replace the current file, choose a new file above.</p>
                </div>
            <?php endif; ?>


            <button class="finish-btn" type="submit"><?php echo $content_id ? 'Update' : 'Create'; ?> Content</button>
        </form>
    </div>
</body>
</html>
<?php include '../includes/footer.php'; ?>
