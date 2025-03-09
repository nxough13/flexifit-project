<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

// if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'admin') {
//     $_SESSION['message'] = "You need to log in first or are not authorized to access this page.";
//     header("Location: ../users/login.php");
//     exit();
// }

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
$new_image = $_FILES['image']['name'];

// If a new image is uploaded, replace the old one; otherwise, keep the existing image
$image_to_save = !empty($new_image) ? basename($new_image) : $existing_image;

if (!empty($new_image)) {
    move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_to_save);
}

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
    $_SESSION['message'] = "Error updating plan.";
}

header("Location: index.php");
exit();
?>
