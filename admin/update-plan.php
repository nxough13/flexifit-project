<?php
ob_start(); // Turn on output buffering
session_start();
include('../includes/config.php');

if (!isset($_POST['plan_id'])) {
    $_SESSION['message'] = "No plan selected for updating.";
    header("Location: index.php");
    exit();
}

$plan_id = $_POST['plan_id'];
$name = $_POST['name'];
$duration_days = $_POST['duration_days'];
$price = $_POST['price'];
$free_training_session = $_POST['free_training_session'];
$description = $_POST['description'];
$existing_image = $_POST['existing_image'];

// Handle file upload
$image_to_save = $existing_image; // Default to existing image

if (!empty($_FILES['image']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $new_filename = "plan_" . $plan_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;
    
    // Check if upload is successful
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_to_save = $new_filename;
        
        // Delete old image if it exists and is different
        if (!empty($existing_image) && $existing_image != $new_filename && file_exists($target_dir . $existing_image)) {
            unlink($target_dir . $existing_image);
        }
    }
}

// Update database
$sql = "UPDATE membership_plans SET 
        name = ?, 
        duration_days = ?, 
        price = ?, 
        free_training_session = ?, 
        description = ?, 
        image = ? 
        WHERE plan_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "siisssi", $name, $duration_days, $price, $free_training_session, $description, $image_to_save, $plan_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "Plan updated successfully.";
} else {
    $_SESSION['message'] = "Error updating plan: " . mysqli_error($conn);
}

header("Location: view-plans.php");
exit();
?>
<?php ob_end_flush(); // At the end of file ?>