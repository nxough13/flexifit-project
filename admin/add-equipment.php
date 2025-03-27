<?php
session_start();
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";

$conn = new mysqli($host, $user, $password, $dbname);

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = $_POST['quantity'];
    $upload_dir = "uploads/";

    // Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image = $_FILES['image']['name'];
    $target_file = $upload_dir . basename($image);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image type and size
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif ($_FILES['image']['size'] > 5000000) { // 5MB limit
        $error = "File size is too large. Maximum allowed size is 5MB.";
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Insert data into the equipment table
            $stmt = $conn->prepare("INSERT INTO equipment (name, description, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $description, $image);

            if ($stmt->execute()) {
                // Get the last inserted equipment ID
                $equipment_id = $stmt->insert_id;

                // Insert inventory data for each equipment
                for ($i = 1; $i <= $quantity; $i++) {
                    $identifier = $_POST["identifier_$i"];
                    $status = $_POST["status_$i"];

                    // Insert into equipment_inventory
                    $inventory_stmt = $conn->prepare("INSERT INTO equipment_inventory (equipment_id, identifier, status) VALUES (?, ?, ?)");
                    $inventory_stmt->bind_param("iss", $equipment_id, $identifier, $status);
                    $inventory_stmt->execute();
                    $inventory_stmt->close();
                }

                $success = "Equipment and inventory added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $error = "Error uploading file.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700; /* Gold/Yellow */
            --primary-dark: #e6c200;
            --secondary: #000000; /* Black */
            --secondary-light: #1a1a1a;
            --danger: #d9534f;
            --success: #5cb85c;
            --warning: #f0ad4e;
            --info: #5bc0de;
            --light: #f8f9fa;
            --dark: #212121;
            --text-light: #f8f8f8;
            --text-dark: #333;
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --card-bg: #1e1e1e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            margin: 0;
            padding: 20px;
            color: var(--text-light);
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border: 1px solid var(--secondary-light);
        }
        
        h2 {
            color: var(--primary);
            margin: 0 0 25px 0;
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            color: var(--primary);
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            background-color: var(--secondary-light);
            border: 1px solid var(--secondary-light);
            border-radius: 6px;
            color: var(--text-light);
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
        }
        
        .file-input-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 15px;
            background-color: var(--secondary-light);
            border: 1px dashed var(--primary);
            border-radius: 6px;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-button:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }
        
        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #aaa;
            display: none;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            color: var(--secondary);
            cursor: pointer;
            background-color: var(--primary);
            border: none;
            transition: all 0.3s ease;
            font-size: 15px;
            text-decoration: none;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .btn-secondary {
            background-color: var(--secondary-light);
            color: var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
        }
        
        .success {
            background-color: rgba(92, 184, 92, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .error {
            background-color: rgba(217, 83, 79, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .inventory-container {
            margin-top: 30px;
            border-top: 1px solid var(--secondary-light);
            padding-top: 20px;
        }
        
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .inventory-title {
            color: var(--primary);
            font-weight: 500;
            font-size: 18px;
        }
        
        .inventory-count {
            background-color: var(--primary);
            color: var(--secondary);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .inventory-boxes {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .inventory-box {
            background-color: var(--secondary-light);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid var(--secondary);
        }
        
        .inventory-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .inventory-box-title {
            color: var(--primary);
            font-weight: 500;
            font-size: 14px;
        }
        
        .inventory-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                width: 90%;
            }
            
            .inventory-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-dumbbell"></i> Add New Equipment</h2>
    
    <?php if (isset($success)) : ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Equipment Name</label>
            <input type="text" name="name" id="name" required placeholder="Enter equipment name">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" required placeholder="Enter equipment description"></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Equipment Image</label>
            <div class="file-input-wrapper">
                <div class="file-input-button" id="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i> Choose an image file
                </div>
                <input type="file" name="image" id="image" accept="image/*" required>
                <div class="file-name" id="file-name-display">No file chosen</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" min="1" required placeholder="Enter quantity" value="1">
        </div>

        <div class="inventory-container">
            <div class="inventory-header">
                <span class="inventory-title">Inventory Items</span>
                <span class="inventory-count" id="inventory-count">1 item</span>
            </div>
            <div class="inventory-boxes" id="inventory-container"></div>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-plus-circle"></i> Add Equipment
        </button>
    </form>

    <a href="view-equipments.php" class="btn btn-secondary" style="margin-top: 15px;">
        <i class="fas fa-arrow-left"></i> Back to Equipment List
    </a>
</div>

<script>
    // File input display
    document.getElementById('image').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
        const fileDisplay = document.getElementById('file-name-display');
        fileDisplay.textContent = fileName;
        fileDisplay.style.display = 'block';
    });

    // Inventory generation based on quantity
    document.getElementById('quantity').addEventListener('input', function() {
        let quantity = parseInt(this.value) || 0;
        let inventoryContainer = document.getElementById('inventory-container');
        let inventoryCount = document.getElementById('inventory-count');
        
        inventoryContainer.innerHTML = '';
        
        if (quantity > 0) {
            inventoryCount.textContent = quantity + (quantity === 1 ? ' item' : ' items');
            
            for (let i = 1; i <= quantity; i++) {
                let inventoryBox = document.createElement('div');
                inventoryBox.classList.add('inventory-box');
                
                inventoryBox.innerHTML = `
                    <div class="inventory-box-header">
                        <span class="inventory-box-title">Item #${i}</span>
                    </div>
                    <div class="inventory-fields">
                        <div>
                            <label for="identifier_${i}">Identifier</label>
                            <input type="text" name="identifier_${i}" id="identifier_${i}" required placeholder="Enter unique identifier">
                        </div>
                        <div>
                            <label for="status_${i}">Status</label>
                            <select name="status_${i}" id="status_${i}" required>
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                `;
                inventoryContainer.appendChild(inventoryBox);
            }
        } else {
            inventoryCount.textContent = '0 items';
        }
    });

    // Trigger the input event on page load to initialize the inventory
    document.getElementById('quantity').dispatchEvent(new Event('input'));
</script>

</body>
</html>