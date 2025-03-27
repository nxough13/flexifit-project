<?php
session_start();
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$user_id = $_SESSION['user_id']; // Logged-in user ID


// Fetch content from database including both image and file_path columns
$sql = "SELECT c.content_id, c.title, c.description, c.content_type, c.image, c.file_path, c.created_at,
               c.admin_id, u.first_name, u.last_name
        FROM content c
        JOIN users u ON c.admin_id = u.user_id
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Feed | FlexiFit</title>
    <style>
        :root {
            --primary-bg: #000;
            --primary-text: #FFD700;
            --secondary-bg: #222;
            --accent: #FFD700;
            --accent-hover: #FFC000;
            --card-bg: #1a1a1a;
            --border-color: #333;
            --text-light: #e0e0e0;
            --text-muted: #999;
        }
       
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--primary-bg);
            color: var(--primary-text);
            margin: 0;
            padding: 0;
        }


        .feed-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }


        .top-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }


        .create-btn {
            background-color: var(--accent);
            color: #000;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }


        .create-btn:hover {
            background-color: var(--accent-hover);
        }


        .post-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(255, 215, 0, 0.1);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            padding: 16px;
        }


        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }


        .user-info {
            display: flex;
            align-items: center;
        }


        .user-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent);
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
        }


        .post-user {
            font-weight: 600;
            color: var(--accent);
        }


        .post-time {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }


        .post-title {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: var(--accent);
        }


        .post-text {
            color: var(--text-light);
            line-height: 1.4;
            margin-bottom: 12px;
        }


        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
        }


        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }


        .post-type {
            display: inline-block;
            padding: 4px 8px;
            background-color: rgba(255, 215, 0, 0.2);
            color: var(--accent);
            border-radius: 4px;
            font-size: 0.8rem;
        }


        .btn-group {
            display: flex;
            gap: 10px;
        }


        .view-btn, .edit-btn {
            background-color: var(--accent);
            color: #000;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }


        .edit-btn {
            background-color: #28a745;
            color: #fff;
        }


        .edit-btn:hover {
            background-color: #218838;
        }


        .view-btn:hover {
            background-color: var(--accent-hover);
        }


        .empty-feed {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }


        .download-btn {
            background-color: #28a745;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }


        .download-btn:hover {
            background-color: #218838;
        }


        .file-name {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>


    <div class="feed-container">
        <div class="top-actions">
            <h1 style="color: var(--accent);">FlexiFit Content Feed</h1>
            <a href="create-content.php" class="create-btn">+ Create Content</a>
        </div>


        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="post-card">
                    <!-- Post Header -->
                    <div class="post-header">
                        <div class="user-info">
                            <div class="user-initials">
                                <?php
                                    $initials = substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1);
                                    echo strtoupper($initials);
                                ?>
                            </div>
                            <div>
                                <div class="post-user"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                <div class="post-time"><?php echo date('F j, Y \a\t g:i A', strtotime($row['created_at'])); ?></div>
                            </div>
                        </div>


                        <?php if ($row['admin_id'] == $user_id): ?>
                            <a href="edit-content.php?content_id=<?php echo $row['content_id']; ?>" class="edit-btn">Edit</a>
                        <?php endif; ?>
                    </div>
                   
                    <!-- Post Content -->
                    <h3 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="post-text"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                   
                    <?php
                    // Check for image in either column with fallback logic
                    $image_path = '';
                    if (!empty($row['image'])) {
                        $image_path = "uploads/" . basename($row['image']);
                    } elseif (!empty($row['file_path'])) {
                        $image_path = $row['file_path'];
                    }


                    if (!empty($image_path)): ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" class="post-image" alt="Post Image">
                    <?php endif; ?>
                   
                    <!-- Post Footer -->
                    <div class="post-footer">
                        <span class="post-type"><?php echo htmlspecialchars($row['content_type']); ?></span>
                       
                        <?php if (!empty($row['file_path'])): ?>
                            <div class="btn-group">
                                <a href="uploads/<?php echo basename($row['file_path']); ?>" class="download-btn" download>Download File</a>
                                <div class="file-name"><?php echo basename($row['file_path']); ?></div>
                            </div>
                        <?php endif; ?>
                       
                        <!-- View Details Button Redirecting to content-details.php -->
                        <a href="content-details.php?content_id=<?php echo $row['content_id']; ?>" class="view-btn">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-feed">
                <h3>No content available yet</h3>
                <p>Check back later for updates!</p>
            </div>
        <?php endif; ?>
    </div>


</body>
</html>


<?php
$conn->close();
include '../includes/footer.php';
?>
