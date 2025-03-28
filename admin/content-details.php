<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';

$conn = new mysqli("localhost", "root", "", "flexifit_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['content_id']) && is_numeric($_GET['content_id'])) {
    $content_id = $_GET['content_id'];
} else {
    echo "<script>alert('Invalid content ID!'); window.location.href='content.php';</script>";
    exit();
}

$query = $conn->prepare("SELECT c.content_id, c.title, c.description, c.content_type, c.file_path, c.image, c.created_at, u.first_name, u.last_name, u.image as user_image
                         FROM content c
                         JOIN users u ON c.admin_id = u.user_id
                         WHERE c.content_id = ?");
$query->bind_param("i", $content_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $content = $result->fetch_assoc();
} else {
    echo "<script>alert('Content not found!'); window.location.href='content.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - FlexiFit</title>
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
            --yellow-glow: 0 0 20px rgba(255, 215, 0, 0.3);
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .content-card {
            background: linear-gradient(135deg, var(--darker) 0%, var(--gray) 100%);
            border-radius: 16px;
            box-shadow: var(--yellow-glow);
            width: 100%;
            max-width: 900px;
            padding: 3rem;
            border: 1px solid var(--primary);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .content-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .content-type-badge {
            display: inline-block;
            background-color: var(--primary);
            color: var(--dark);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-title {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .content-description {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2.5rem;
            padding: 0 1rem;
        }

        .content-image-container {
            margin: 2rem 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-height: 500px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--darker);
            border: 2px solid var(--primary);
        }

        .content-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .content-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 215, 0, 0.2);
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .author-details {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 600;
            color: var(--primary);
        }

        .post-date {
            font-size: 0.9rem;
            color: #aaa;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--dark);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 215, 0, 0.1);
            transform: translateY(-2px);
        }

        .no-image {
            padding: 2rem;
            text-align: center;
            color: #777;
        }

        .no-image i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .content-card {
                padding: 2rem;
            }
            
            .content-title {
                font-size: 1.8rem;
            }
            
            .content-meta {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
            
            .action-buttons {
                width: 100%;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .content-card {
                padding: 1.5rem;
            }
            
            .content-title {
                font-size: 1.5rem;
            }
            
            .content-description {
                font-size: 1rem;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="content-container">
        <div class="content-card">
            <div class="content-header">
                <span class="content-type-badge"><?php echo ucfirst(str_replace('_', ' ', $content['content_type'])); ?></span>
                <h1 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h1>
            </div>

            <div class="content-description">
                <p><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
            </div>

            <?php if (!empty($content['image'])): ?>
                <div class="content-image-container">
                    <img src="<?php echo htmlspecialchars($content['image']); ?>" alt="Content Image" class="content-image">
                </div>
            <?php else: ?>
                <div class="no-image">
                    <i class="fas fa-image"></i>
                    <p>No image available for this content</p>
                </div>
            <?php endif; ?>

            <div class="content-meta">
                <div class="author-info">
                    <img src="<?php echo !empty($content['user_image']) ? htmlspecialchars($content['user_image']) : '../assets/default-avatar.jpg'; ?>" 
                         alt="Author" class="author-avatar">
                    <div class="author-details">
                        <span class="author-name"><?php echo htmlspecialchars($content['first_name'] . " " . $content['last_name']); ?></span>
                        <span class="post-date">Posted on <?php echo date('F j, Y', strtotime($content['created_at'])); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin'): ?>
                        <a href="edit-content.php?content_id=<?php echo $content['content_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="content.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Content
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any interactive JavaScript here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Animation for card entrance
            const card = document.querySelector('.content-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>

<?php
$query->close();
$conn->close();
include '../includes/footer.php'; 
?>

<?php ob_end_flush(); // At the end of file ?>
