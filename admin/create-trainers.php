<?php
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Initialize success/error messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate and process form data
        $first_name = trim($_POST["first_name"]);
        $last_name = trim($_POST["last_name"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $confirm_password = $_POST["confirm_password"];
        $age = intval($_POST["age"]);
        $gender = $_POST["gender"];
        $availability_status = $_POST["availability_status"];
        $status = "active";
        
        // Validate passwords match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        
        // Validate age
        if ($age < 18 || $age > 100) {
            throw new Exception("Age must be between 18 and 100.");
        }
        
        // Process image upload
        $image = "default.png";
        if (!empty($_FILES["image"]["name"])) {
            $target_dir = "../uploads/trainers/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $image = uniqid("trainer_") . "." . $file_extension;
            $target_path = $target_dir . $image;
            
            // Check if image file is actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                throw new Exception("File is not an image.");
            }
            
            // Check file size (max 2MB)
            if ($_FILES["image"]["size"] > 2000000) {
                throw new Exception("Image size must be less than 2MB.");
            }
            
            // Allow certain file formats
            $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
            }
            
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
                throw new Exception("Error uploading image.");
            }
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert trainer
        $conn->begin_transaction();
        
        $sql = "INSERT INTO trainers (first_name, last_name, email, password, age, gender, availability_status, status, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissss", $first_name, $last_name, $email, $password_hash, $age, $gender, $availability_status, $status, $image);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving trainer: " . $conn->error);
        }
        
        $trainer_id = $conn->insert_id;
        
        // Process specialties
        if (!empty($_POST['specialty'])) {
            foreach ($_POST['specialty'] as $specialty_name) {
                $specialty_name = trim($specialty_name);
                if (empty($specialty_name)) continue;
                
                // Check if specialty exists
                $check_sql = "SELECT specialty_id FROM specialty WHERE name = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $specialty_name);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $specialty_id = $row['specialty_id'];
                } else {
                    $insert_sql = "INSERT INTO specialty (name) VALUES (?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("s", $specialty_name);
                    if (!$insert_stmt->execute()) {
                        throw new Exception("Error saving specialty: " . $conn->error);
                    }
                    $specialty_id = $conn->insert_id;
                }
                
                // Link trainer to specialty
                $relation_sql = "INSERT INTO trainer_specialty (trainer_id, specialty_id) VALUES (?, ?)";
                $relation_stmt = $conn->prepare($relation_sql);
                $relation_stmt->bind_param("ii", $trainer_id, $specialty_id);
                if (!$relation_stmt->execute()) {
                    throw new Exception("Error linking specialty: " . $conn->error);
                }
            }
        }
        
        $conn->commit();
        
        // Send email with credentials
        $to = $email;
        $subject = "Your FlexiFit Trainer Account";
        $message = "Hello $first_name $last_name,\n\n";
        $message .= "Your trainer account has been created successfully!\n\n";
        $message .= "Login Credentials:\n";
        $message .= "Email: $email\n";
        $message .= "Password: $password\n\n";
        $message .= "Login at: http://yourdomain.com/login.php\n\n";
        $message .= "Please change your password after first login.\n\n";
        $message .= "Best regards,\nFlexiFit Team";
        
        // Use PHPMailer for more reliable email sending
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';
        $mail->Password = 'dwnw xuwn baln ljbp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit Gym');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        if (!$mail->send()) {
            throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
        $_SESSION['success_message'] = "Trainer added successfully! Login credentials sent to trainer's email.";
        header("Location: create-trainers.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: create-trainers.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Trainer | FlexiFit Admin</title>
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
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
            color: var(--primary);
        }
        
        .password-strength {
            height: 4px;
            background: var(--bg-dark);
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .weak { background: var(--danger); width: 30%; }
        .medium { background: var(--warning); width: 60%; }
        .strong { background: var(--success); width: 100%; }
        
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
    </style>
</head>
<body>
<div class="main-container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-user-plus"></i> Add New Trainer</h1>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <div class="form-section">
            <form method="POST" enctype="multipart/form-data" id="trainerForm">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group password-container">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required 
                           oninput="checkPasswordStrength(this.value)">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                </div>
                
                <div class="form-group password-container">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                    <small id="passwordMatch" style="color: var(--danger); display: none;">Passwords don't match!</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" min="18" max="100" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Availability Status</label>
                    <select name="availability_status" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Specialties</label>
                    <div class="specialty-container" id="specialtyContainer">
                        <div class="specialty-input-group">
                            <input type="text" name="specialty[]" class="form-control" required>
                            <button type="button" class="btn btn-remove-specialty" onclick="removeSpecialty(this)">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
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
            
            <img id="preview" class="img-preview" src="#" alt="Image Preview" style="display: none;">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Trainer
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='view-trainers.php'">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
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
    
    function removeSpecialty(button) {
        const container = document.getElementById('specialtyContainer');
        if (container.children.length > 1) {
            button.parentElement.remove();
        } else {
            alert("At least one specialty is required.");
        }
    }
    
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling;
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    function checkPasswordStrength(password) {
        const strengthMeter = document.getElementById('strengthMeter');
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        
        strengthMeter.className = 'strength-meter';
        if (strength <= 1) {
            strengthMeter.classList.add('weak');
        } else if (strength <= 3) {
            strengthMeter.classList.add('medium');
        } else {
            strengthMeter.classList.add('strong');
        }
    }
    
    document.getElementById('trainerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchMessage = document.getElementById('passwordMatch');
        
        if (password !== confirmPassword) {
            e.preventDefault();
            matchMessage.style.display = 'block';
            document.getElementById('confirm_password').classList.add('is-invalid');
        } else {
            matchMessage.style.display = 'none';
        }
    });
</script>

</body>
</html>
<?php 
$conn->close();
ob_end_flush(); 
?>