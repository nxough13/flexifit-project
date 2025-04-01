<?php
ob_start(); // Turn on output buffering
session_start();

// Include database connection and header
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure `trainer_id` is in the URL
if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
    die("Error: Trainer ID is missing from the URL.");
}
$trainer_id = $_GET['trainer_id'];

// Fetch trainer's data
$sql = "SELECT * FROM trainers WHERE trainer_id = '$trainer_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Error: No trainer found with ID $trainer_id.");
}
$trainer = $result->fetch_assoc();

// Fetch trainer's specialties
$sql_specialties = "SELECT s.name FROM specialty s
                    JOIN trainer_specialty ts ON s.specialty_id = ts.specialty_id
                    WHERE ts.trainer_id = '$trainer_id'";
$specialties_result = $conn->query($sql_specialties);
$specialties = [];
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer | FlexiFit Admin</title>
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
        
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
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
        
        .form-container {
            display: flex;
            gap: 30px;
            background-color: var(--bg-light);
            border-radius: 8px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--primary);
        }
        
        .form-section {
            flex: 1;
        }
        
        .profile-section {
            flex: 0 0 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border-left: 1px solid var(--border-color);
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
        
        .img-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
            margin: 20px 0;
        }
        
        .file-upload {
            position: relative;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background-color: var(--bg-dark);
            border: 2px dashed var(--primary);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-label:hover {
            background-color: var(--secondary);
        }
        
        .file-upload-icon {
            color: var(--primary);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .file-upload-text {
            color: var(--text-light);
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
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--text-dark);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--text-light);
            border: 1px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary);
            color: var(--text-dark);
        }
        
        .specialty-container {
            margin-bottom: 15px;
        }
        
        .specialty-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .btn-add-specialty {
            background-color: var(--info);
            color: var(--text-light);
            padding: 8px 15px;
            margin-bottom: 15px;
        }
        
        .btn-add-specialty:hover {
            background-color: #138496;
        }
        
        .btn-remove-specialty {
            background-color: var(--danger);
            color: var(--text-light);
            padding: 8px 15px;
        }
        
        .btn-remove-specialty:hover {
            background-color: #c82333;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
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
            .form-container {
                flex-direction: column;
            }
            
            .profile-section {
                border-left: none;
                border-top: 1px solid var(--border-color);
                padding-top: 30px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        .password-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-user-edit"></i> Edit Trainer</h1>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <div class="form-section">
            <form method="POST" action="update-trainers.php?trainer_id=<?php echo $trainer_id; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($trainer['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($trainer['last_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($trainer['email']); ?>" required>
                </div>

                <div class="form-group password-container">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword(this)"></i>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" min="18" value="<?php echo htmlspecialchars($trainer['age']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="male" <?php if ($trainer['gender'] == 'male') echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if ($trainer['gender'] == 'female') echo 'selected'; ?>>Female</option>
                        <option value="other" <?php if ($trainer['gender'] == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Availability Status</label>
                    <select name="availability_status" class="form-control" required>
                        <option value="Available" <?php if ($trainer['availability_status'] == 'Available') echo 'selected'; ?>>Available</option>
                        <option value="Unavailable" <?php if ($trainer['availability_status'] == 'Unavailable') echo 'selected'; ?>>Unavailable</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Specialties</label>
                    <div class="specialty-container" id="specialtyContainer">
                        <?php foreach ($specialties as $specialty): ?>
                            <div class="specialty-input-group">
                                <input type="text" name="specialty[]" class="form-control" value="<?php echo htmlspecialchars($specialty); ?>" required>
                                <button type="button" class="btn btn-remove-specialty" onclick="removeSpecialty(this)">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-add-specialty" onclick="addSpecialty()">
                        <i class="fas fa-plus"></i> Add Specialty
                    </button>
                </div>
        </div>
        
        <div class="profile-section">
            <div class="file-upload">
                <label class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                    <span class="file-upload-text">Upload Profile Image</span>
                    <input type="file" name="image" style="display: none;" onchange="previewImage(this)" accept="image/*">
                </label>
            </div>
            
            <?php if (!empty($trainer['image']) && $trainer['image'] != 'default.png'): ?>
                <img id="preview" class="img-preview" src="uploads/trainers/<?php echo htmlspecialchars($trainer['image']); ?>" alt="Current Trainer Image">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($trainer['image']); ?>">
                <small>Leave empty to keep current image</small>
            <?php else: ?>
                <img id="preview" class="img-preview" src="#" alt="Image Preview" style="display: none;">
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Trainer
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='view-trainers.php'">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
            </div>
        </div>
    </form>
</div>
    </div>
</div>

<script>
    // Image preview function
    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Add specialty field
    function addSpecialty() {
        const container = document.getElementById('specialtyContainer');
        const div = document.createElement('div');
        div.className = 'specialty-input-group';
        div.innerHTML = `
            <input type="text" name="specialty[]" class="form-control" required>
            <button type="button" class="btn btn-remove-specialty" onclick="removeSpecialty(this)">
                <i class="fas fa-minus"></i>
            </button>
        `;
        container.appendChild(div);
    }
    
    // Remove specialty field
    function removeSpecialty(button) {
        const container = document.getElementById('specialtyContainer');
        if (container.children.length > 1) {
            button.parentElement.remove();
        } else {
            alert("At least one specialty is required.");
        }
    }

    function togglePassword(icon) {
        const passwordField = icon.previousElementSibling;
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

</script>

</body>
</html>
<?php ob_end_flush(); // At the end of file ?>