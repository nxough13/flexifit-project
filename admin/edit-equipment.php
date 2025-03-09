<?php
session_start();
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

// Get the inventory_id (which is the identifier of the equipment in inventory) from URL
$inventory_id = $_GET['inventory_id'] ?? null;

if ($inventory_id === null) {
    header("Location: view-equipments.php");
    exit();
}

// Fetch the equipment inventory based on inventory_id (identifier)
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

// Fetch associated inventory details for equipment
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(0, 0, 0);
            margin: 20px;
        }

        .container {
            width: 50%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(11, 11, 11, 0.1);
            text-align: center;
        }

        h2 {
            color: #FFFFFF;
        }

        label {
            color: white;
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            cursor: pointer;
            background-color: rgb(255, 215, 0);
            border: none;
            display: inline-block;
            margin: 10px;
        }

        .message {
            font-weight: bold;
            margin-bottom: 15px;
            color: green;
        }

        .error {
            font-weight: bold;
            margin-bottom: 15px;
            color: red;
        }

        .btn-container {
            margin-top: 15px;
        }

        .inventory-container {
            margin-top: 20px;
        }

        .inventory-box {
            margin-bottom: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #333;
            border-radius: 5px;
        }

        .inventory-box input {
            width: 48%;
            display: inline-block;
            margin-right: 4%;
        }

        .inventory-box input:last-child {
            margin-right: 0;
        }

        input[type="file"] {
            background-color: white;
            /* Makes input field white */
            color: black;
            /* Ensures text is visible */
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Edit Equipment</h2>

        <?php if (isset($success)) : ?>
            <p class="message"><?= $success ?></p>
        <?php endif; ?>

        <?php if (isset($error)) : ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form action="update-equipment.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="inventory_id" value="<?= $inventory['inventory_id'] ?>">
            <input type="hidden" name="equipment_id" value="<?= $inventory['equipment_id'] ?>">

            <label for="name">Equipment Name:</label>
            <input type="text" name="name" id="name" value="<?= $inventory['equipment_name'] ?>" required>

            <label for="description">Description:</label>
            <textarea name="description" id="description" rows="4" required><?= $inventory['description'] ?></textarea>

            <label for="image">Upload Image (Leave blank to keep current image):</label>
            <input type="file" name="image" id="image" accept="image/*">

            <label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1" value="<?= count($equipment_inventory) ?>" required>

            <div class="inventory-container" id="inventory-container">
                <?php foreach ($equipment_inventory as $index => $inv) : ?>
                    <div class="inventory-box">
                        <label for="identifier_<?= $index + 1 ?>">Identifier <?= $index + 1 ?>:</label>
                        <input type="text" name="identifier_<?= $index + 1 ?>" id="identifier_<?= $index + 1 ?>" value="<?= $inv['identifier'] ?>" required>

                        <label for="status_<?= $index + 1 ?>">Status <?= $index + 1 ?>:</label>
                        <select name="status_<?= $index + 1 ?>" id="status_<?= $index + 1 ?>" required>
                            <option value="available" <?= ($inv['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                            <option value="in_use" <?= ($inv['status'] == 'in_use') ? 'selected' : '' ?>>In Use</option>
                            <option value="maintenance" <?= ($inv['status'] == 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                        </select>

                        <!-- Add Hidden Inventory ID field -->
                        <input type="hidden" name="inventory_id_<?= $index + 1 ?>" value="<?= $inv['inventory_id'] ?>">
                    </div>
                <?php endforeach; ?>
            </div>


            <button type="submit" class="btn">Update Equipment</button>
        </form>

        <div class="btn-container">
            <a href="view-equipments.php" class="btn">View Equipment List</a>
        </div>
    </div>

    <script>
        document.getElementById('quantity').addEventListener('input', function() {
            let quantity = this.value;
            let inventoryContainer = document.getElementById('inventory-container');
            inventoryContainer.innerHTML = ''; // Clear the previous inventory inputs

            // Create input fields for inventory based on quantity
            for (let i = 1; i <= quantity; i++) {
                let inventoryBox = document.createElement('div');
                inventoryBox.classList.add('inventory-box');

                inventoryBox.innerHTML = ` 
                <label for="identifier_${i}">Identifier ${i}:</label>
                <input type="text" name="identifier_${i}" id="identifier_${i}" required>

                <label for="status_${i}">Status ${i}:</label>
                <select name="status_${i}" id="status_${i}" required>
                    <option value="available">Available</option>
                    <option value="in_use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            `;
                inventoryContainer.appendChild(inventoryBox);
            }
        });
    </script>

</body>

</html>