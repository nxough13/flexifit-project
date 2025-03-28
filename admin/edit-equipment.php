<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get the inventory_id from URL
$inventory_id = $_GET['inventory_id'] ?? null;

if ($inventory_id === null) {
    header("Location: view-equipments.php");
    exit();
}

// Fetch the equipment inventory
$query = "SELECT ei.*, e.name AS equipment_name, e.description, e.image 
          FROM equipment_inventory ei
          JOIN equipment e ON ei.equipment_id = e.equipment_id
          WHERE ei.inventory_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $inventory_id);
$stmt->execute();
$inventory_result = $stmt->get_result();
$inventory = $inventory_result->fetch_assoc();

if (!$inventory) {
    die("Equipment not found.");
}

// Fetch associated inventory details
$equipment_query = "SELECT * FROM equipment_inventory WHERE equipment_id = ?";
$equipment_stmt = $conn->prepare($equipment_query);
$equipment_stmt->bind_param("i", $inventory['equipment_id']);
$equipment_stmt->execute();
$equipment_result = $equipment_stmt->get_result();

$equipment_inventory = [];
while ($row = $equipment_result->fetch_assoc()) {
    $equipment_inventory[] = $row;
}

$stmt->close();
$equipment_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment</title>
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
        
        .current-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid var(--primary);
            margin-bottom: 10px;
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
    <h2><i class="fas fa-edit"></i> Edit Equipment</h2>
    
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

    <form action="update-equipment.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="inventory_id" value="<?= $inventory['inventory_id'] ?>">
        <input type="hidden" name="equipment_id" value="<?= $inventory['equipment_id'] ?>">

        <!-- Current Image Display -->
        <div class="current-image">
            <img src="<?= !empty($inventory['image']) && file_exists('uploads/' . $inventory['image']) 
                        ? 'uploads/' . htmlspecialchars($inventory['image']) 
                        : 'https://via.placeholder.com/200?text=No+Image' ?>" 
                 alt="Current Equipment Image">
            <small>Current Image</small>
        </div>

        <div class="form-group">
            <label for="name">Equipment Name</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($inventory['equipment_name']) ?>" required placeholder="Enter equipment name">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" required placeholder="Enter equipment description"><?= htmlspecialchars($inventory['description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Update Image (Leave blank to keep current)</label>
            <div class="file-input-wrapper">
                <div class="file-input-button" id="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i> Choose an image file
                </div>
                <input type="file" name="image" id="image" accept="image/*">
                <div class="file-name" id="file-name-display">No file chosen</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" min="1" required placeholder="Enter quantity" value="<?= count($equipment_inventory) ?>">
        </div>

        <div class="inventory-container">
            <div class="inventory-header">
                <span class="inventory-title">Inventory Items</span>
                <span class="inventory-count" id="inventory-count"><?= count($equipment_inventory) ?> item<?= count($equipment_inventory) > 1 ? 's' : '' ?></span>
            </div>
            <div class="inventory-boxes" id="inventory-container">
                <?php foreach ($equipment_inventory as $index => $inv) : ?>
                    <div class="inventory-box">
                        <div class="inventory-box-header">
                            <span class="inventory-box-title">Item #<?= $index + 1 ?></span>
                        </div>
                        <div class="inventory-fields">
                            <div>
                                <label for="identifier_<?= $index + 1 ?>">Identifier</label>
                                <input type="text" name="identifier_<?= $index + 1 ?>" id="identifier_<?= $index + 1 ?>" value="<?= htmlspecialchars($inv['identifier']) ?>" required>
                            </div>
                            <div>
                                <label for="status_<?= $index + 1 ?>">Status</label>
                                <select name="status_<?= $index + 1 ?>" id="status_<?= $index + 1 ?>" required>
                                    <option value="available" <?= ($inv['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                                    <option value="in_use" <?= ($inv['status'] == 'in_use') ? 'selected' : '' ?>>In Use</option>
                                    <option value="maintenance" <?= ($inv['status'] == 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="inventory_id_<?= $index + 1 ?>" value="<?= $inv['inventory_id'] ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-save"></i> Update Equipment
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
</script>
</body>
</html>

<?php $conn->close(); ?>
<?php ob_end_flush(); // At the end of file ?>