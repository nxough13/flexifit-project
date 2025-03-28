<?php
ob_start(); // Turn on output buffering
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check admin authorization before any output
// if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'admin') {
//     $_SESSION['message'] = "You need to log in first or are not authorized to access this page.";
//     header("Location: ../login.php");
//     exit();
// }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $duration_days = $_POST['duration_days'];
    $price = $_POST['price'];
    $free_training_session = $_POST['free_training_session'];
    $description = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target = "uploads/plans/" . basename($image);
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads/plans')) {
        mkdir('uploads/plans', 0777, true);
    }
    
    $sql = "INSERT INTO membership_plans (name, duration_days, price, free_training_session, description, image) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        header("Location: create-plan.php");
        exit();
    }
    
    $stmt->bind_param("siidss", 
        $name, 
        $duration_days, 
        $price, 
        $free_training_session, 
        $description, 
        $image
    );
    
    if ($stmt->execute()) {
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        $_SESSION['success'] = "Membership plan created successfully!";
        header("Location: view-plans.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating plan: " . $stmt->error;
        header("Location: create-plan.php");
        exit();
    }
}

// Now include the header after all potential redirects
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Membership Plan | FlexiFit Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FFA000;
            --secondary: #212121;
            --dark: #000000;
            --light: #f8f9fa;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #fd7e14;
            --info: #17a2b8;
            --text-light: #ffffff;
            --text-dark: #121212;
            --bg-dark: #111111;
            --bg-light: #1e1e1e;
            --border-color: #333333;
            --card-shadow: 0 4px 8px rgba(255, 193, 7, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--primary);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary);
        }
        
        .page-title {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
            text-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
        }
        
        .page-subtitle {
            color: #aaa;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-light);
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .file-upload {
            position: relative;
            margin-bottom: 20px;
        }
        
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background-color: var(--bg-dark);
            border: 2px dashed var(--primary);
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-label:hover {
            background-color: var(--secondary);
        }
        
        .file-upload-icon {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .file-upload-text {
            color: var(--text-light);
        }
        
        .file-upload-preview {
            margin-top: 15px;
            display: none;
            text-align: center;
        }
        
        .file-upload-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-return {
            background-color: var(--secondary);
            color: var(--text-light);
        }
        
        .btn-return:hover {
            background-color: #333;
        }
        
        .btn-submit {
            background-color: var(--primary);
            color: var(--text-dark);
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-id-card"></i> Create Membership Plan</h1>
        <p class="page-subtitle">Add a new membership plan to your system</p>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <form action="create-plan.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name" class="form-label">Plan Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="duration_days" class="form-label">Duration (Days)</label>
            <input type="number" id="duration_days" name="duration_days" class="form-control" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="price" class="form-label">Price</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label for="free_training_session" class="form-label">Free Training Sessions</label>
            <input type="number" id="free_training_session" name="free_training_session" class="form-control" min="0" required>
        </div>
        
        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control" required></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Plan Image</label>
            <div class="file-upload">
                <input type="file" id="image" name="image" class="file-upload-input" accept=".jpg,.jpeg,.png" required>
                <label for="image" class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                    <span class="file-upload-text">Choose an image file...</span>
                </label>
                <div class="file-upload-preview" id="imagePreview">
                    <img id="previewImage" src="#" alt="Image Preview">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-return" onclick="location.href='view-plans.php'">
                <i class="fas fa-arrow-left"></i> Back to Plans
            </button>
            <button type="submit" class="btn btn-submit">
                <i class="fas fa-plus-circle"></i> Create Plan
            </button>
        </div>
    </form>
</div>

<script>
    // Image preview functionality
    document.getElementById('image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('previewImage');
        const previewContainer = document.getElementById('imagePreview');
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
            
            // Update the label text
            document.querySelector('.file-upload-text').textContent = file.name;
        } else {
            previewContainer.style.display = 'none';
            document.querySelector('.file-upload-text').textContent = 'Choose an image file...';
        }
    });
</script>

</body>
</html>
<?php ob_end_flush(); // At the end of file ?>