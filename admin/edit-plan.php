<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

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

$existing_image = !empty($plan['image']) ? 'uploads/' . htmlspecialchars($plan['image']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Membership Plan</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700; /* Gold/Yellow */
            --primary-dark: #e6c200;
            --secondary: #000000; /* Black */
            --secondary-light: #1a1a1a;
            --danger: #d9534f;
            --success: #5cb85c;
            --warning: #f0ad4e;
            --info: #5bc0de;
            --light: #f8f9fa;
            --dark: #212121;
            --text-light: #f8f8f8;
            --text-dark: #333;
            --bg-dark: #121212;
            --card-bg: #1e1e1e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border: 1px solid var(--primary);
        }
        
        .title {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 15px;
            margin-bottom: 8px;
            color: var(--primary);
            font-weight: 500;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--secondary-light);
            background-color: var(--secondary-light);
            color: var(--text-light);
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 15px;
            background-color: var(--secondary-light);
            border: 1px dashed var(--primary);
            border-radius: 6px;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-button:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
        }
        
        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #aaa;
        }
        
        .image-preview {
            margin-top: 20px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 250px;
            max-height: 250px;
            border-radius: 8px;
            border: 2px solid var(--primary);
            margin-bottom: 10px;
        }
        
        .current-image-label {
            color: var(--primary);
            font-size: 14px;
        }
        
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            text-decoration: none;
            flex: 1;
        }
        
        .btn-return {
            background-color: var(--secondary-light);
            color: var(--primary);
            border: none;
        }
        
        .btn-return:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .btn-update {
            background-color: var(--primary);
            color: var(--secondary);
            border: none;
        }
        
        .btn-update:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .container {
                width: 90%;
                padding: 20px;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title"><i class="fas fa-edit"></i> Edit Membership Plan</h1>
        <form method="POST" action="update-plan.php" enctype="multipart/form-data">
            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['plan_id']); ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($plan['image']); ?>">

            <div class="form-group">
                <label for="name">Plan Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($plan['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="duration_days">Duration (Days)</label>
                <input type="number" id="duration_days" name="duration_days" value="<?php echo htmlspecialchars($plan['duration_days']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($plan['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="free_training_session">Free Training Sessions</label>
                <input type="number" id="free_training_session" name="free_training_session" value="<?php echo htmlspecialchars($plan['free_training_session']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($plan['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Image</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button" id="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i> Choose new image (optional)
                    </div>
                    <input type="file" name="image" id="image" accept=".jpg, .png">
                </div>
                <div class="file-name" id="file-name-display">No file chosen</div>
            </div>

            <?php if ($existing_image) { ?>
                <div class="image-preview">
                    <img id="currentImage" src="<?php echo $existing_image; ?>" alt="Current Plan Image">
                    <div class="current-image-label">Current Image</div>
                </div>
            <?php } ?>

            <div class="buttons">
                <button type="button" class="btn btn-return" onclick="location.href='view-plans.php'">
                    <i class="fas fa-arrow-left"></i> Return
                </button>
                <button type="submit" class="btn btn-update">
                    <i class="fas fa-save"></i> Update Plan
                </button>
            </div>
        </form>
    </div>

    <script>
        // File input display
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            const fileDisplay = document.getElementById('file-name-display');
            fileDisplay.textContent = fileName;
            fileDisplay.style.display = 'block';
        });
    </script>
</body>
</html>