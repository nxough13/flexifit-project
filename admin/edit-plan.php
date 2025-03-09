<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

if (!isset($_SESSION['id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['message'] = "You need to log in first or are not authorized to access this page.";
    header("Location: ../users/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No plan selected for editing.";
    header("Location: index.php");
    exit();
}

$plan_id = $_GET['id'];

$sql = "SELECT * FROM membership_plans WHERE plan_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $plan_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$plan = mysqli_fetch_assoc($result);

if (!$plan) {
    $_SESSION['message'] = "Plan not found.";
    header("Location: index.php");
    exit();
}

// Store existing image path
$existing_image = !empty($plan['image']) ? 'uploads/' . htmlspecialchars($plan['image']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Membership Plan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 60%;
            margin: 50px auto;
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border: 2px solid yellow;
        }

        .title {
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            color: #fcd100;
            margin-bottom: 20px;
        }

        form label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #fcd100;
        }

        form input, form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #fcd100;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            resize: none;
        }

        .image-preview {
            margin-top: 10px;
            text-align: center;
        }

        .image-preview img {
            max-width: 200px;
            border-radius: 5px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .return-button,
        .update-button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .return-button {
            background-color: #333;
            color: white;
        }

        .update-button {
            background-color: #fcd100;
            color: black;
        }

        .return-button:hover {
            background-color: #555;
        }

        .update-button:hover {
            background-color: #ffcc00;
        }
    </style>
</head>
<br><br><br><br><br>
<body>
    <div class="container">
        <h1 class="title">Edit Membership Plan</h1>
        <form method="POST" action="update-plan.php" enctype="multipart/form-data">
            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['plan_id']); ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($plan['image']); ?>">

            <label for="name">Plan Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($plan['name']); ?>" required>

            <label for="duration_days">Duration (Days):</label>
            <input type="number" id="duration_days" name="duration_days" value="<?php echo htmlspecialchars($plan['duration_days']); ?>" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($plan['price']); ?>" required>

            <label for="free_training_session">Free Training Sessions:</label>
            <input type="number" id="free_training_session" name="free_training_session" value="<?php echo htmlspecialchars($plan['free_training_session']); ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($plan['description']); ?></textarea>

            <label for="image">Upload New Image (Optional):</label>
            <input type="file" name="image" id="image" accept=".jpg, .png">

            <div class="image-preview">
                <?php if ($existing_image) { ?>
                    <img id="currentImage" src="<?php echo $existing_image; ?>" alt="Current Image">
                    <br>
                    <span>Current Image</span>
                <?php } ?>
            </div>

            <div class="buttons">
                <button type="button" class="return-button" onclick="location.href='index.php'">Return</button>
                <button type="submit" class="update-button">Update Plan</button>
            </div>
        </form>
    </div>
</body>
</html>
