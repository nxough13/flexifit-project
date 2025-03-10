<?php
session_start();

// Include database connection and header
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure `trainer_id` is in the URL
if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
    die("Error: Trainer ID is missing from the URL.");
}
$trainer_id = $_GET['trainer_id'];

// Fetch trainer's data
$sql = "SELECT * FROM trainers WHERE trainer_id = '$trainer_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Error: No trainer found with ID $trainer_id.");
}
$trainer = $result->fetch_assoc();

// Fetch trainer's specialties
$sql_specialties = "SELECT s.name FROM specialty s
                    JOIN trainer_specialty ts ON s.specialty_id = ts.specialty_id
                    WHERE ts.trainer_id = '$trainer_id'";
$specialties_result = $conn->query($sql_specialties);
$specialties = [];
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row['name'];
}
// neo
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
            background-color: #121212;
            color: yellow;
            padding: 20px;
        }
        h2 {
            color: yellow;
            text-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            margin-bottom: 20px;
            font-size: 30px;
            font-weight: bold;
            text-align: center;
            margin-top: 50px;
        }
        .container {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: auto;
            background: #1e1e1e;
            padding: 40px;
            border-radius: 10px;
            border: 2px solid yellow;
            box-shadow: 0 0 15px rgba(255, 255, 0, 0.8);
        }
        .form-container {
            flex: 1;
            margin-right: 40px;
        }
        .profile-container {
            flex: 0 0 300px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            border: 2px solid yellow;
            padding: 20px;
            border-radius: 8px;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid yellow;
            border-radius: 4px;
            background: #1e1e1e;
            color: yellow;
        }
        .btn, .back-btn, .specialty-btn {
            background: yellow;
            color: black;
            padding: 12px;
            border: none;
            cursor: pointer;
            width: 50%;
            border-radius: 4px;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            margin-bottom: 10px;
        }
        .btn:hover, .back-btn:hover, .specialty-btn:hover {
            background: black;
            color: yellow;
            border: 2px solid yellow;
            box-shadow: 0 0 15px rgba(255, 255, 0, 1);
        }
        .img-preview {
            width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 50%;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .specialty-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .specialty-input {
            width: 80%;
            margin-bottom: 10px;
        }
        .remove-btn {
            background: red;
            color: white;
            border: none;
            padding: 5px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
        }
        .remove-btn:hover {
            background: darkred;
        }
    </style>
</head>
<body>
    <br><br>
<h2>Edit Trainer</h2>

<div class="container">
    <!-- Form container -->
    <div class="form-container">
        <form method="POST" action="update-trainers.php?trainer_id=<?php echo $trainer_id; ?>" enctype="multipart/form-data">
            <label>First Name:</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($trainer['first_name']); ?>" required>

            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($trainer['last_name']); ?>" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($trainer['email']); ?>" required>

            <label>Age:</label>
            <input type="number" name="age" min="18" value="<?php echo htmlspecialchars($trainer['age']); ?>" required>

            <label>Gender:</label>
            <select name="gender" required>
                <option value="male" <?php if ($trainer['gender'] == 'male') echo 'selected'; ?>>Male</option>
                <option value="female" <?php if ($trainer['gender'] == 'female') echo 'selected'; ?>>Female</option>
                <option value="other" <?php if ($trainer['gender'] == 'other') echo 'selected'; ?>>Other</option>
            </select>

            <label>Specialty:</label>
            <div class="specialty-container">
                <?php
                foreach ($specialties as $specialty) {
                    echo "<div><input type='text' name='specialty[]' class='specialty-input' value='".htmlspecialchars($specialty)."'>
                          <button type='button' class='remove-btn' onclick='removeSpecialty(this)'>Remove</button></div>";
                }
                ?>
                <button type="button" class="specialty-btn" onclick="addSpecialty()">Add Specialty</button>
            </div>

            <label>Availability Status:</label>
            <select name="availability_status" required>
                <option value="available" <?php if ($trainer['availability_status'] == 'available') echo 'selected'; ?>>Available</option>
                <option value="unavailable" <?php if ($trainer['availability_status'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
            </select>

            <button type="submit" class="btn">Update Trainer</button>
        
    </div>

    <!-- Profile container -->
    <div class="profile-container">
        <h3>Profile Image</h3>
        <input type="file" name="image" id="imageInput">
        <img id="preview" class="img-preview" src="uploads/<?php echo $trainer['image']; ?>" alt="Image Preview">
                    <br><br><br><br><br>
        <button type="button" class="back-btn" onclick="window.location.href='view-trainers.php'">Return</button>
        </form>
    </div>
</div>

<script>
    function addSpecialty() {
        const specialtyContainer = document.querySelector('.specialty-container');
        const div = document.createElement('div');
        div.innerHTML = "<input type='text' name='specialty[]' class='specialty-input'> <button type='button' class='remove-btn' onclick='removeSpecialty(this)'>Remove</button>";
        specialtyContainer.appendChild(div);
    }

    function removeSpecialty(button) {
        button.parentElement.remove();
    }

    document.getElementById('imageInput').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById('preview').src = reader.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    });
</script>

</body>
</html>
