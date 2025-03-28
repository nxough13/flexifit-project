<?php
session_start();
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Initialize variables
$content_creation_successful = false;
$content_id = isset($_GET['content_id']) ? $_GET['content_id'] : null;
$content_data = null;

// Check admin permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo "<script>alert('You must be logged in as an admin to edit content.'); window.location.href='../index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch content data if editing
if ($content_id) {
    $stmt = $conn->prepare("SELECT * FROM content WHERE content_id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $content_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $content_data = $result->fetch_assoc();
    } else {
        echo "<script>alert('Content not found or you do not have permission to edit it.'); window.location.href='content.php';</script>";
        exit();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $content_type = $conn->real_escape_string($_POST['content_type']);
    $image_path = $content_data ? $content_data['image'] : '';
    $file_path = $content_data ? $content_data['file_path'] : '';

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        // Delete old image if exists
        if (!empty($content_data['image']) && file_exists($content_data['image'])) {
            unlink($content_data['image']);
        }
        
        // Upload new image
        $upload_dir = '../admin/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_name = basename($_FILES['image']['name']);
        $image_path = $upload_dir . uniqid() . '_' . $image_name;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            echo "<script>alert('Error uploading the image.');</script>";
        }
    }

    // Handle file upload
    if (!empty($_FILES['file']['name'])) {
        // Delete old file if exists
        if (!empty($content_data['file_path']) && file_exists($content_data['file_path'])) {
            unlink($content_data['file_path']);
        }
        
        // Upload new file
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name;
        
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            echo "<script>alert('Error uploading the file.');</script>";
        }
    }

    // Update or insert content
    if ($content_id) {
        $query = "UPDATE content SET title=?, description=?, content_type=?, image=?, file_path=? WHERE content_id=? AND admin_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssii", $title, $description, $content_type, $image_path, $file_path, $content_id, $user_id);
    } else {
        $query = "INSERT INTO content (admin_id, title, description, content_type, image, file_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $user_id, $title, $description, $content_type, $image_path, $file_path);
    }

    if ($stmt->execute()) {
        $content_creation_successful = true;
        header("Location: content.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $content_id ? 'Edit' : 'Create'; ?> Content - FlexiFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #FFC000;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F5F5F5;
            --gray: #333333;
            --yellow-glow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }

        .edit-container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .page-header h2 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .page-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background-color: var(--primary);
        }

        .content-form {
            background: linear-gradient(135deg, var(--darker) 0%, var(--gray) 100%);
            border-radius: 15px;
            padding: 3rem;
            box-shadow: var(--yellow-glow);
            border: 1px solid var(--primary);
        }

        .form-row {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-column {
            flex: 1;
            min-width: 0;
        }

        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            background-color: var(--darker);
            border: 2px solid var(--primary);
            border-radius: 8px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
            border-color: var(--primary-dark);
        }

        textarea.form-control {
            min-height: 220px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FFD700'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5rem;
        }

        .file-upload-section {
            margin-top: 2.5rem;
            padding: 2rem;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 12px;
            border: 1px dashed var(--primary);
        }

        .file-upload {
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px dashed var(--primary);
            background: rgba(255, 215, 0, 0.05);
        }

        .file-upload:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }

        .file-upload i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .file-upload p {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .file-upload small {
            color: #aaa;
        }

        .file-upload input {
            display: none;
        }

        .file-name {
            margin-top: 1rem;
            font-size: 1rem;
            color: var(--primary);
            font-style: italic;
        }

        .current-asset {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background-color: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .current-asset p {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .current-asset a {
            color: var(--primary);
            text-decoration: underline;
            transition: all 0.3s ease;
        }

        .current-asset a:hover {
            color: var(--primary-dark);
        }

        .preview-image {
            max-width: 100%;
            height: auto;
            max-height: 250px;
            margin-top: 1rem;
            border-radius: 8px;
            border: 2px solid var(--primary);
        }

        .submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            width: 100%;
            padding: 1.2rem;
            background-color: var(--primary);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        @media (max-width: 992px) {
            .edit-container {
                max-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .edit-container {
                padding: 0 1.5rem;
                max-width: 100%;
            }
            
            .content-form {
                padding: 2rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .page-header h2 {
                font-size: 2rem;
            }
            
            .file-upload-section {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .content-form {
                padding: 1.5rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .file-upload {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="page-header">
            <h2><?php echo $content_id ? 'Edit Content' : 'Create New Content'; ?></h2>
        </div>

        <form class="content-form" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" class="form-control" name="title" id="title" placeholder="Enter content title" required
                               value="<?php echo $content_data ? htmlspecialchars($content_data['title']) : ''; ?>">
                    </div>
                </div>
                <div class="form-column">
                    <div class="form-group">
                        <label for="content_type">Content Type</label>
                        <select class="form-control" name="content_type" id="content_type" required>
                            <option value="guide" <?php echo ($content_data && $content_data['content_type'] == 'guide') ? 'selected' : ''; ?>>Guide</option>
                            <option value="tip" <?php echo ($content_data && $content_data['content_type'] == 'tip') ? 'selected' : ''; ?>>Tip</option>
                            <option value="announcement" <?php echo ($content_data && $content_data['content_type'] == 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                            <option value="workout_plan" <?php echo ($content_data && $content_data['content_type'] == 'workout_plan') ? 'selected' : ''; ?>>Workout Plan</option>
                            <option value="other" <?php echo ($content_data && $content_data['content_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" name="description" id="description" placeholder="Enter content description" required><?php 
                    echo $content_data ? htmlspecialchars($content_data['description']) : ''; 
                ?></textarea>
            </div>

            <div class="file-upload-section">
                <div class="form-row">
                    <div class="form-column">
                        <label>Content Image</label>
                        <div class="file-upload" onclick="document.getElementById('image').click()">
                            <input type="file" name="image" id="image" accept="image/*">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload image</p>
                            <small>(JPG, PNG, GIF - Max 5MB)</small>
                            <div class="file-name" id="imageFileName"></div>
                        </div>
                        
                        <?php if ($content_data && !empty($content_data['image'])): ?>
                            <div class="current-asset">
                                <p>Current Image:</p>
                                <img src="<?php echo htmlspecialchars($content_data['image']); ?>" class="preview-image" alt="Current content image">
                                <p>Upload a new image above to replace this one.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-column">
                        <label>Additional File</label>
                        <div class="file-upload" onclick="document.getElementById('file').click()">
                            <input type="file" name="file" id="file">
                            <i class="fas fa-file-upload"></i>
                            <p>Click to upload file</p>
                            <small>(PDF, DOCX, etc. - Max 10MB)</small>
                            <div class="file-name" id="fileFileName"></div>
                        </div>
                        
                        <?php if ($content_data && !empty($content_data['file_path'])): ?>
                            <div class="current-asset">
                                <p>Current File: <a href="<?php echo htmlspecialchars($content_data['file_path']); ?>" target="_blank">View File</a></p>
                                <p>Upload a new file above to replace this one.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-<?php echo $content_id ? 'save' : 'plus'; ?>"></i>
                <?php echo $content_id ? 'Update Content' : 'Create Content'; ?>
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

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!title || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>
<?php include '../includes/footer.php'; ?>