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

// Retrieve form data
$inventory_id = $_POST['inventory_id'];
$equipment_id = $_POST['equipment_id'];
$name = $_POST['name'];
$description = $_POST['description'];
$image = $_FILES['image']['name'];
$quantity = $_POST['quantity'];

// Handle the image upload if provided
if (!empty($image)) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Update image path in equipment table
        $update_image_query = "UPDATE equipment SET image = ? WHERE equipment_id = ?";
        $stmt = $conn->prepare($update_image_query);
        $stmt->bind_param("si", $target_file, $equipment_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Update equipment information (name, description)
$update_query = "UPDATE equipment SET name = ?, description = ? WHERE equipment_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ssi", $name, $description, $equipment_id);
$stmt->execute();
$stmt->close();

// Handle the inventory updates
$current_inventory_count = count($_POST) - 3; // Exclude 'inventory_id', 'equipment_id', and 'quantity'
// Debug: Check values before executing queries
echo "<pre>";
var_dump($_POST);  // Print out POST data for debugging
echo "</pre>";

if ($current_inventory_count > $quantity) {
    // Delete excess inventory rows if the quantity has decreased
    $delete_inventory_query = "DELETE FROM equipment_inventory WHERE equipment_id = ? LIMIT ?";
    $stmt = $conn->prepare($delete_inventory_query);
    $stmt->bind_param("ii", $equipment_id, $current_inventory_count - $quantity);
    $stmt->execute();
    $stmt->close();
} elseif ($current_inventory_count < $quantity) {
    // Add new inventory rows if the quantity has increased
    for ($i = $current_inventory_count + 1; $i <= $quantity; $i++) {
        // Insert a new row for each new inventory item
        $insert_inventory_query = "INSERT INTO equipment_inventory (equipment_id, identifier, status) VALUES (?, ?, ?)";
        
        // Store the POST values in variables
        $identifier = $_POST['identifier_' . $i];
        $status = $_POST['status_' . $i];

        $stmt = $conn->prepare($insert_inventory_query);
        $stmt->bind_param("iss", $equipment_id, $identifier, $status);
        $stmt->execute();
        $stmt->close();
    }
}

// Update existing inventory items
// Loop through all inventory items and update them
for ($i = 1; $i <= $quantity; $i++) {
    if (isset($_POST['identifier_' . $i]) && isset($_POST['status_' . $i])) {
        // Store the POST values in variables to pass by reference
        $identifier = $_POST['identifier_' . $i];
        $status = $_POST['status_' . $i];

        // Get the inventory_id for the specific row to update
        // Now it will work since we added the inventory_id_1, inventory_id_2 etc. in the form
        $inventory_id_to_update = $_POST['inventory_id_' . $i]; // Get the specific inventory ID

        // Check if the inventory_id exists in the POST data
        if (!empty($inventory_id_to_update)) {
            // Prepare the update query
            $update_inventory_query = "UPDATE equipment_inventory SET identifier = ?, status = ? WHERE inventory_id = ? AND equipment_id = ?";

            // Prepare statement and bind parameters
            $stmt = $conn->prepare($update_inventory_query);
            $stmt->bind_param("ssii", $identifier, $status, $inventory_id_to_update, $equipment_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}


// Success message
$success = "Equipment and Inventory successfully updated!";
?>

<!-- Redirect back to the edit equipment page or show a success message -->
<p><?= $success ?></p>
<a href="edit-equipment.php?inventory_id=<?= $inventory_id ?>">Go back to edit</a>
