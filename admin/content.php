<?php
session_start();
include '../includes/header.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all content from the database
$sql = "SELECT * FROM content ORDER BY created_at DESC"; // Newest content first
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlexiFit Content Feed</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #FFC000;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F5F5F5;
            --gray: #333333;
            --yellow-glow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .page-header h2 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .create-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: var(--primary);
            color: var(--dark);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--primary);
            box-shadow: var(--yellow-glow);
            margin: 1rem auto;
        }

        .create-btn:hover {
            background-color: transparent;
            color: var(--primary);
            transform: translateY(-2px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .content-card {
            background: linear-gradient(135deg, var(--darker) 0%, var(--gray) 100%);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--yellow-glow);
            border: 1px solid var(--primary);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 2px solid var(--primary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .content-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .content-description {
            color: var(--light);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .content-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #aaa;
        }

        .content-type {
            background-color: rgba(255, 215, 0, 0.2);
            color: var(--primary);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .content-date {
            font-style: italic;
        }

        .card-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: var(--primary);
            color: var(--dark);
        }

        .details-btn {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .edit-btn:hover {
            background-color: var(--primary-dark);
        }

        .details-btn:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }

        .no-content {
            text-align: center;
            grid-column: 1 / -1;
            padding: 2rem;
            color: #777;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h2>FlexiFit Content Feed</h2>
        <a href="create-content.php" class="create-btn">
            <i class="fas fa-plus"></i> Create New Content
        </a>
    </div>

    <div class="content-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="content-card">
                    <?php if ($row['image']): ?>
                        <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Content Image" class="card-image">
                    <?php else: ?>
                        <div class="card-image" style="background: var(--darker); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 3rem; color: var(--gray);"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h3 class="content-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="content-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                        
                        <div class="content-meta">
                            <span class="content-type"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($row['content_type']))); ?></span>
                            <span class="content-date"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        
                        <div class="card-actions">
                            <a href="edit-content.php?id=<?php echo $row['content_id']; ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="content-details.php?content_id=<?php echo $row['content_id']; ?>" class="action-btn details-btn">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-content">
                <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray);"></i>
                <p>No content available yet. Be the first to create some!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php'; 
?>
</body>
</html>