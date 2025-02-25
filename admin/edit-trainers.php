<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get trainer ID from URL
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$trainer = null;

// Fetch trainer data
if ($id > 0) {
    $sql = "SELECT * FROM trainers WHERE trainer_id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $trainer = $result->fetch_assoc();
    } else {
        die("Trainer not found.");
    }
}

// Handle form update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $specialty = $_POST["specialty"];
    $availability_status = $_POST["availability_status"];
    $status = $_POST["status"];

    // Handle image upload (keep old image if not updated)
    if (!empty($_FILES["image"]["name"])) {
        $image = basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $image);
    } else {
        $image = $trainer["image"];
    }

    // Update trainer data
    $sql = "UPDATE trainers SET first_name='$first_name', last_name='$last_name', email='$email', 
            age='$age', gender='$gender', specialty='$specialty', availability_status='$availability_status', 
            status='$status', image='$image' WHERE trainer_id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Trainer updated successfully!'); window.location.href = 'view-trainers.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000;
            color: yellow;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: #222;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px yellow;
        }
        h2 {
            color: yellow;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            text-align: left;
            color: yellow;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid yellow;
            border-radius: 5px;
            background: #333;
            color: yellow;
        }
        .btn {
            background: yellow;
            color: black;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-weight: bold;
        }
        .btn:hover {
            background: #ffd700;
        }
        .back-btn {
            display: block;
            margin-top: 10px;
            color: yellow;
            text-decoration: none;
            font-weight: bold;
        }
        .image-preview {
            display: block;
            margin: 10px auto;
            max-width: 150px;
            border: 3px solid yellow;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Trainer</h2>
    <?php if ($trainer): ?>
    <form method="POST" enctype="multipart/form-data">
        <label>First Name:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($trainer['first_name']) ?>" required>
        
        <label>Last Name:</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($trainer['last_name']) ?>" required>
        
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($trainer['email']) ?>" required>

        <label>Age:</label>
        <input type="number" name="age" value="<?= htmlspecialchars($trainer['age']) ?>" required>

        <label>Gender:</label>
        <select name="gender" required>
            <option value="male" <?= ($trainer['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($trainer['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= ($trainer['gender'] == 'other') ? 'selected' : '' ?>>Other</option>
        </select>

        <label>Specialty:</label>
        <input type="text" name="specialty" value="<?= htmlspecialchars($trainer['specialty']) ?>">

        <label>Availability:</label>
        <select name="availability_status" required>
            <option value="available" <?= ($trainer['availability_status'] == 'available') ? 'selected' : '' ?>>Available</option>
            <option value="unavailable" <?= ($trainer['availability_status'] == 'unavailable') ? 'selected' : '' ?>>Unavailable</option>
        </select>

        <label>Status:</label>
        <select name="status" required>
            <option value="active" <?= ($trainer['status'] == 'active') ? 'selected' : '' ?>>Active</option>
            <option value="disabled" <?= ($trainer['status'] == 'disabled') ? 'selected' : '' ?>>Disabled</option>
        </select>

        <label>Profile Image:</label>
        <?php if (!empty($trainer['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($trainer['image']) ?>" class="image-preview">
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">
        
        <button type="submit" class="btn">Update Trainer</button>
        <a href="view-trainers.php" class="back-btn">← Back to List</a>
    </form>
    <?php else: ?>
        <p style="color: red;">Trainer not found.</p>
    <?php endif; ?>
</div>

</body>
</html>
