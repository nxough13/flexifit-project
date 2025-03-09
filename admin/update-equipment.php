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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST['equipment_id']; 
    $name = $_POST['name'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];

    // Start transaction
    $conn->begin_transaction();

try {
    // Check if a new image is uploaded
    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = "uploads/" . basename($image_name);
$image_db_name = basename($image_name); // Store only the filename

        // Ensure the upload folder exists
        if (!file_exists("uploads/")) {
            mkdir("uploads/", 0777, true);
        }

        // Move uploaded file to the destination folder
        if (move_uploaded_file($image_tmp_name, $image_folder)) {
            // Update equipment image in the database
            $update_image_query = "UPDATE equipment SET image = ? WHERE equipment_id = ?";
$stmt = $conn->prepare($update_image_query);
$stmt->bind_param("si", $image_db_name, $equipment_id); // Use $image_db_name
$stmt->execute();
$stmt->close();
        } else {
            echo "Error uploading the image.";
        }
    }

    // Update equipment details (name, description)
    $update_equipment_query = "UPDATE equipment SET name = ?, description = ? WHERE equipment_id = ?";
    $stmt = $conn->prepare($update_equipment_query);
    $stmt->bind_param("ssi", $name, $description, $equipment_id);
    $stmt->execute();
    $stmt->close();

        // Count existing inventory
        $inventory_count_query = "SELECT COUNT(*) AS total FROM equipment_inventory WHERE equipment_id = ?";
        $stmt = $conn->prepare($inventory_count_query);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $stmt->bind_result($current_inventory_count);
        $stmt->fetch();
        $stmt->close();

        // If quantity reduced, delete excess rows
        if ($current_inventory_count > $quantity) {
            $delete_count = $current_inventory_count - $quantity;
            $delete_inventory_query = "DELETE FROM equipment_inventory WHERE equipment_id = ? ORDER BY inventory_id DESC LIMIT ?";
            $stmt = $conn->prepare($delete_inventory_query);
            $stmt->bind_param("ii", $equipment_id, $delete_count);
            $stmt->execute();
            $stmt->close();            
        }

        // If quantity increased, insert new rows
        if ($current_inventory_count < $quantity) {
            for ($i = $current_inventory_count + 1; $i <= $quantity; $i++) {
                if (!empty($_POST["identifier_$i"]) && !empty($_POST["status_$i"])) {
                    $identifier = $_POST["identifier_$i"];
                    $status = $_POST["status_$i"];

                    $insert_inventory_query = "INSERT INTO equipment_inventory (equipment_id, identifier, status) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($insert_inventory_query);
                    $stmt->bind_param("iss", $equipment_id, $identifier, $status);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Update existing inventory items
        for ($i = 1; $i <= $quantity; $i++) {
            if (!empty($_POST["identifier_$i"]) && !empty($_POST["status_$i"]) && !empty($_POST["inventory_id_$i"])) {
                $identifier = $_POST["identifier_$i"];
                $status = $_POST["status_$i"];
                $inventory_id_to_update = $_POST["inventory_id_$i"];

                $update_inventory_query = "UPDATE equipment_inventory SET identifier = ?, status = ? WHERE inventory_id = ? AND equipment_id = ?";
                $stmt = $conn->prepare($update_inventory_query);
                $stmt->bind_param("ssii", $identifier, $status, $inventory_id_to_update, $equipment_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Commit transaction if all queries succeed
        $conn->commit();
        echo "Equipment updated successfully!";
        header("Location: view-equipments.php"); // Redirect to the view-trainers.php page
        exit();
    } catch (Exception $e) {
        // Rollback transaction if any query fails
        $conn->rollback();
        echo "Error updating equipment: " . $e->getMessage();
    }

    $conn->close();
} else {
    echo "Invalid request!";
}
?>
