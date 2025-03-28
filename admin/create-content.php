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




    // Handle image upload (Make this optional)
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_path = "";


    // Handle file upload (Make this optional)
    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_path = "";


    if (!empty($image_name)) {
        $image_path = "uploads/" . basename($image_name);
        if (!move_uploaded_file($image_tmp, $image_path)) {
            echo "Error uploading the image.";
            exit();
        }
    }


    if (!empty($file_name)) {
        $file_path = "uploads/" . basename($file_name);
        if (!move_uploaded_file($file_tmp, $file_path)) {
            echo "Error uploading the file.";
            exit();
        }
    }


    // Insert content data into the database using the logged-in user's user_id
    $query = "INSERT INTO content (admin_id, title, description, content_type, image, file_path)
              VALUES ('$user_id', '$title', '$description', '$content_type', '$image_path', '$file_path')";


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
    :root {
        --primary: #FFD700;
        --primary-dark: #FFC000;
        --primary-light: rgba(255, 215, 0, 0.1);
        --dark: #121212;
        --darker: #0A0A0A;
        --dark-gray: #1E1E1E;
        --light: #F5F5F5;
        --lighter: #FFFFFF;
        --gray: #333333;
        --light-gray: #444;
        --yellow-glow: 0 0 15px rgba(255, 215, 0, 0.5);
        --success: #4CAF50;
        --danger: #F44336;
        --info: #2196F3;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        --border-radius: 8px;
        --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        background-color: var(--dark);
        color: var(--light);
        line-height: 1.6;
        min-height: 100vh;
        padding: 20px;
        background-image: 
            radial-gradient(circle at 10% 20%, rgba(255, 215, 0, 0.03) 0%, transparent 20%),
            radial-gradient(circle at 90% 80%, rgba(255, 215, 0, 0.03) 0%, transparent 20%);
    }

    .container {
        max-width: 1200px;
        width: 95%;
        margin: 2rem auto;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
        position: relative;
    }

    .page-header h2 {
        color: var(--primary);
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .page-header h2::after {
        content: '';
        position: absolute;
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
        width: 150px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        border-radius: 2px;
    }

    .content-form {
        background: var(--dark-gray);
        border-radius: var(--border-radius);
        padding: 3rem;
        box-shadow: var(--box-shadow);
        border: 1px solid var(--light-gray);
        transition: var(--transition);
    }

    .content-form:hover {
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        border-color: var(--primary);
    }

    .form-group {
        margin-bottom: 2rem;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.8rem;
        color: var(--primary);
        font-weight: 600;
        font-size: 1.1rem;
        letter-spacing: 0.3px;
    }

    .form-control {
        width: 100%;
        padding: 1.2rem;
        background-color: var(--darker);
        border: 2px solid var(--light-gray);
        border-radius: var(--border-radius);
        color: var(--lighter);
        font-size: 1.1rem;
        transition: var(--transition);
    }

    .form-control:hover {
        border-color: var(--primary);
    }

    .form-control:focus {
        outline: none;
        box-shadow: 0 0 0 3px var(--primary-light);
        border-color: var(--primary);
        background-color: var(--gray);
    }

    textarea.form-control {
        min-height: 200px;
        resize: vertical;
        line-height: 1.6;
    }

    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FFD700'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.5rem;
        cursor: pointer;
    }

    .file-upload-wrapper {
        position: relative;
        margin-top: 1.5rem;
    }

    .file-upload {
        padding: 2.5rem;
        border: 2px dashed var(--light-gray);
        border-radius: var(--border-radius);
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        background-color: var(--darker);
        position: relative;
        overflow: hidden;
    }

    .file-upload:hover {
        background-color: var(--primary-light);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .file-upload i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 1rem;
        transition: var(--transition);
    }

    .file-upload:hover i {
        transform: scale(1.1);
    }

    .file-upload p {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--light);
    }

    .file-upload small {
        color: var(--light-gray);
        font-size: 0.85rem;
    }

    .file-upload input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-name {
        margin-top: 1rem;
        padding: 0.8rem;
        background-color: var(--darker);
        border-radius: var(--border-radius);
        color: var(--primary);
        font-size: 0.9rem;
        text-align: center;
        border: 1px dashed var(--primary);
        transition: var(--transition);
    }

    .submit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        width: 100%;
        padding: 1.3rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: var(--dark);
        border: none;
        border-radius: var(--border-radius);
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 1.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        transform: translateY(-3px);
        box-shadow: 0 7px 14px rgba(255, 215, 0, 0.3);
    }

    .submit-btn:active {
        transform: translateY(1px);
    }

    .message {
        padding: 1.2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        text-align: center;
        font-weight: 500;
        animation: slideDown 0.4s ease-out;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .success-message {
        color: var(--success);
        background-color: rgba(76, 175, 80, 0.1);
        border-left: 4px solid var(--success);
    }

    .error-message {
        color: var(--danger);
        background-color: rgba(244, 67, 54, 0.1);
        border-left: 4px solid var(--danger);
    }

    /* Floating label effect */
    .floating-label-group {
        position: relative;
        margin-bottom: 1.8rem;
    }

    .floating-label {
        position: absolute;
        top: 1rem;
        left: 1rem;
        color: var(--light-gray);
        transition: var(--transition);
        pointer-events: none;
    }

    .form-control:focus + .floating-label,
    .form-control:not(:placeholder-shown) + .floating-label {
        top: -0.8rem;
        left: 0.8rem;
        font-size: 0.8rem;
        color: var(--primary);
        background-color: var(--dark-gray);
        padding: 0 0.5rem;
    }

    /* Animation for form elements */
    .form-group {
        animation: fadeInUp 0.5s ease-out forwards;
        opacity: 0;
    }

    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.2s; }
    .form-group:nth-child(3) { animation-delay: 0.3s; }
    .form-group:nth-child(4) { animation-delay: 0.4s; }
    .form-group:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .container {
            max-width: 1000px;
        }
    }

    @media (max-width: 992px) {
        .container {
            max-width: 900px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 0 1.5rem;
            max-width: 100%;
        }
        
        .content-form {
            padding: 2.5rem;
        }
        
        .page-header h2 {
            font-size: 2.2rem;
        }
    }

    @media (max-width: 576px) {
        body {
            padding: 15px;
        }
        
        .content-form {
            padding: 2rem;
        }
        
        .page-header h2 {
            font-size: 2rem;
        }
        
        .form-control {
            padding: 1rem;
        }
        
        .file-upload {
            padding: 2rem;
        }
    }
</style>
</head>
<body>

<br><br><br><b></b>
<div class="container">
    <div class="page-header">
        <h2>Create New Content</h2>
    </div>

    <?php if ($content_creation_successful): ?>
        <div class="success-message">
            Content created successfully! You will be redirected to the content page.
        </div>
    <?php endif; ?>

    <form class="content-form" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" name="title" id="title" placeholder="Enter content title" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" id="description" placeholder="Enter content description" required></textarea>
        </div>

        <div class="form-group">
            <label for="content_type">Content Type</label>
            <select class="form-control" name="content_type" id="content_type" required>
                <option value="guide">Guide</option>
                <option value="tip">Tip</option>
                <option value="announcement">Announcement</option>
                <option value="workout_plan">Workout Plan</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Content Image (Optional)</label>
            <div class="file-upload" onclick="document.getElementById('image').click()">
                <input type="file" name="image" id="image" accept="image/*">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload image</p>
                <small>(JPG, PNG, GIF - Max 5MB)</small>
                <div class="file-name" id="imageFileName"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Additional File (Optional)</label>
            <div class="file-upload" onclick="document.getElementById('file').click()">
                <input type="file" name="file" id="file">
                <i class="fas fa-file-upload"></i>
                <p>Click to upload file</p>
                <small>(PDF, DOCX, etc. - Max 10MB)</small>
                <div class="file-name" id="fileFileName"></div>
            </div>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-plus"></i> Create Content
        </button>
    </form>
</div>

<script>
    // Display file names when selected
    document.getElementById('image').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
        document.getElementById('imageFileName').textContent = fileName;
    });

    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
        document.getElementById('fileFileName').textContent = fileName;
    });
</script>


</body>
</html>


<?php include '../includes/footer.php'; // neo ?>