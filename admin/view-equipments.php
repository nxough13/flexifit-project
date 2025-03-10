<?php
session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include "../includes/header.php";
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// neo
// Fetch all equipment from the equipment_inventory table
$sql = "SELECT ei.inventory_id, e.name AS equipment_name, e.description, ei.identifier, ei.status, ei.active_status, e.image
        FROM equipment_inventory ei
        JOIN equipment e ON ei.equipment_id = e.equipment_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(0, 0, 0);
            margin: 20px;
        }
        .container {
            width: 90%;
            margin: auto;
            background: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(11, 11, 11, 0.1);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: #FFFFFF;
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
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .equipment-card {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: opacity 0.3s ease-in-out;
        }
        .equipment-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
        }
        .equipment-info {
            margin-top: 10px;
        }
        .equipment-card button {
            margin-top: 10px;
            padding: 10px;
            width: 100%;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        
        /* Grayed out effect for disabled equipment */
        .disabled-equipment {
            background-color: #d3d3d3 !important;
            opacity: 0.5 !important;
            pointer-events: none;
            filter: grayscale(100%);
        }

        /* Blue color for re-enable button */
        .reenable-button {
            background-color: blue !important;
            color: white !important;
            pointer-events: auto !important; /* Ensure it remains clickable */
            opacity: 1 !important;
        }

        /* Disabled edit button styling */
        .disabled-edit {
            background-color: gray !important;
            color: white !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>Equipment List</h2>
    <div>
        <a href="add-equipment.php" class="btn">Add Equipment</a>
        <a href="index.php" class="btn">Home</a>
    </div>
</div>

<div class="grid-container">
    <?php while ($row = $result->fetch_assoc()) : ?>
        <?php $is_disabled = ($row['active_status'] == 'disabled'); ?>
        
        <div class="equipment-card <?= $is_disabled ? 'disabled-equipment' : '' ?>">
            <img src="<?= !empty($row['image']) && file_exists('uploads/' . $row['image']) 
                         ? 'uploads/' . htmlspecialchars($row['image']) 
                         : 'uploads/placeholder.png' ?>" 
                 alt="Equipment Image">
                
            <div class="equipment-info">
                <p><strong>Equipment ID:</strong> <?= htmlspecialchars($row['inventory_id']) ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($row['equipment_name']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                <p><strong>Identifier:</strong> <?= htmlspecialchars($row['identifier']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                <p><strong>Active Status:</strong> <?= htmlspecialchars($row['active_status']) ?></p>
            </div>

            <!-- Edit Button (Disabled when equipment is disabled) -->
            <a href="<?= $is_disabled ? '#' : 'edit-equipment.php?inventory_id=' . $row['inventory_id'] ?>">
                <button class="<?= $is_disabled ? 'disabled-edit' : '' ?>">
                    <span><?= $is_disabled ? '⛔' : '✏️' ?></span> <?= $is_disabled ? 'Disabled' : 'Edit' ?>
                </button>
            </a>

            <!-- Disable/Enable Button -->
            <?php if (!$is_disabled) : ?>
                <form action="disable-equipment.php" method="POST">
                    <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                    <button type="submit" style="background-color: red; color: white;">
                        <span>❌</span> Disable
                    </button>
                </form>
            <?php else : ?>
                <form action="enable-equipment.php" method="POST">
                    <input type="hidden" name="inventory_id" value="<?= $row['inventory_id'] ?>">
                    <button type="submit" class="reenable-button">
                        <span>🔄</span> Re-enable
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>

<?php $conn->close(); ?>
