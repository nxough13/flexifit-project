<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$trainer_id = $_GET['id'];
$sql = "SELECT * FROM trainers WHERE trainer_id=$trainer_id";
$result = $conn->query($sql);
$trainer = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Trainer</title>
    <style>
        body { font-family: Arial; background-color: #f4f4f4; text-align: center; }
        form { background: white; padding: 20px; margin: 50px auto; width: 300px; border-radius: 5px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; }
        img { border-radius: 50%; margin-bottom: 10px; }
    </style>
</head>
<body>

<h2>Edit Trainer</h2>

<form action="update-trainers.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="trainer_id" value="<?= $trainer['trainer_id'] ?>">
    
    <label>Current Image:</label><br>
    <?php if (!empty($trainer['image']) && file_exists("uploads/" . $trainer['image'])): ?>
        <img src="uploads/<?= $trainer['image'] ?>" width="80" height="80">
    <?php else: ?>
        <img src="uploads/default.png" width="80" height="80">
    <?php endif; ?>
    
    <input type="text" name="first_name" value="<?= $trainer['first_name'] ?>" required>
    <input type="text" name="last_name" value="<?= $trainer['last_name'] ?>" required>
    <input type="email" name="email" value="<?= $trainer['email'] ?>" required>
    <input type="text" name="specialty" value="<?= $trainer['specialty'] ?>">
    
    <label>Availability:</label>
    <select name="availability_status">
        <option value="available" <?= $trainer['availability_status'] == 'available' ? 'selected' : '' ?>>Available</option>
        <option value="unavailable" <?= $trainer['availability_status'] == 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
    </select>

    <label>Update Image (Optional):</label>
    <input type="file" name="image" accept="image/*">

    <button type="submit" class="btn">Update</button>
</form>

</body>
</html>
