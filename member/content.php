<?php
ob_start(); // Turn on output buffering

session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();

}


// Fetch all content from the database
$sql = "SELECT * FROM content ORDER BY content_id ASC";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>
    <style>
    :root {
        --primary: #FFD700; /* Gold/Yellow */
        --primary-dark: #FFC000;
        --primary-light: #FFE44D;
        --dark: #121212; /* Dark background */
        --darker: #0A0A0A;
        --light: #1E1E1E;
        --lighter: #2D2D2D;
        --text: #FFFFFF;
        --text-secondary: #B0B0B0;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        --transition: all 0.3s ease;
    }

    body {
        background-color: var(--dark);
        color: var(--text);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 2rem auto;
        padding: 20px;
    }

    h2 {
        text-align: center;
        color: var(--primary);
        font-size: 2.5rem;
        margin-bottom: 2rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        padding-bottom: 15px;
    }

    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 3px;
        background: var(--primary);
    }

    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .content-item {
        background-color: var(--light);
        padding: 25px;
        border-radius: 10px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border: 1px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .content-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        border-color: var(--primary);
    }

    .content-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: var(--primary);
    }

    .content-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--lighter);
    }

    .content-description {
        color: var(--text-secondary);
        margin: 15px 0;
        font-size: 1rem;
        line-height: 1.7;
    }

    .content-meta {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        font-size: 0.9rem;
    }

    .content-type {
        background-color: var(--primary-dark);
        color: var(--text-dark);
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .content-created-at {
        color: var(--text-secondary);
        align-self: center;
    }

    .content-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin: 15px 0;
        transition: var(--transition);
    }

    .content-item:hover .content-image {
        transform: scale(1.02);
    }

    .details-btn {
        display: inline-block;
        background-color: var(--primary);
        color: var(--dark);
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 15px;
        font-weight: 600;
        transition: var(--transition);
        border: 2px solid transparent;
        text-align: center;
        width: 100%;
    }

    .details-btn:hover {
        background-color: transparent;
        color: var(--primary);
        border-color: var(--primary);
    }

    .no-content {
        text-align: center;
        color: var(--text-secondary);
        font-size: 1.2rem;
        grid-column: 1 / -1;
        padding: 50px 0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container {
            width: 95%;
            padding: 10px;
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        h2 {
            font-size: 2rem;
        }
    }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .content-item {
        animation: fadeIn 0.5s ease forwards;
    }

    /* Delay animations for grid items */
    .content-item:nth-child(1) { animation-delay: 0.1s; }
    .content-item:nth-child(2) { animation-delay: 0.2s; }
    .content-item:nth-child(3) { animation-delay: 0.3s; }
    .content-item:nth-child(4) { animation-delay: 0.4s; }
    .content-item:nth-child(5) { animation-delay: 0.5s; }
</style>
</head>
<body>


<div class="container">
    <h2>Explore the Feed</h2>


    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="content-item">
                <div class="content-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="content-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                <div class="content-type">Type: <?php echo htmlspecialchars($row['content_type']); ?></div>


                <?php if ($row['image']): ?>
                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Content Image" class="content-image">
                <?php endif; ?>


                <div class="content-created-at">Posted on: <?php echo htmlspecialchars($row['created_at']); ?></div>


                <!-- Details Button -->
                <a href="view-content.php?content_id=<?php echo $row['content_id']; ?>" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No content available.</p>
    <?php endif; ?>
</div>


</body>
</html>


<?php
$conn->close();
?>
<?php include '../includes/footer.php'; // neo?>
<?php ob_end_flush(); // At the end of file ?>
