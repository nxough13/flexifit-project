<?php
session_start();
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
    header("Location: ../index.php");
    exit();
}

// Fetch all active trainers with their specialties
$sql = "SELECT t.*, GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties
        FROM trainers t
        LEFT JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
        LEFT JOIN specialty s ON ts.specialty_id = s.specialty_id
        WHERE t.status = 'active'
        GROUP BY t.trainer_id
        ORDER BY t.first_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Trainers</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #121212;
            --accent: #2c2c2c;
            --card-bg: #1e1e1e;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --available: #28a745;
            --unavailable: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header-divider {
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            border: none;
            margin: 0 auto 30px;
        }
        
        .trainers-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Exactly 2 columns */
            gap: 30px;
            justify-items: center; /* Center items horizontally */
        }
        
        .trainer-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            width: 100%; /* Take full width of grid cell */
            max-width: 450px; /* Maximum width for each card */
        }
        
        .trainer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .trainer-image-container {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .trainer-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .trainer-card:hover .trainer-image {
            transform: scale(1.05);
        }
        
        .availability-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .available {
            background-color: var(--available);
            color: white;
        }
        
        .unavailable {
            background-color: var(--unavailable);
            color: white;
        }
        
        .trainer-info {
            padding: 20px;
        }
        
        .trainer-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 10px;
            color: var(--primary);
        }
        
        .trainer-meta {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .trainer-age {
            background: rgba(255, 215, 0, 0.1);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 10px;
        }
        
        .trainer-gender {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .trainer-specialties {
            margin: 15px 0;
        }
        
        .specialty-tag {
            display: inline-block;
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .trainer-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .details-btn {
            flex: 1;
            padding: 10px;
            background: var(--primary);
            color: var(--secondary);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .details-btn:hover {
            background: #e6c200;
        }
        
        .no-trainers {
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 15px;
            grid-column: 1 / -1;
        }
        
        .no-trainers i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .trainers-grid {
                grid-template-columns: 1fr; /* Single column on mobile */
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .trainer-card {
                max-width: 100%; /* Full width on mobile */
            }
        }
    </style>
</head>
<body>

<div class="page-container">
    <div class="page-header">
        <h1>Our Professional Trainers</h1>
        <div class="header-divider"></div>
    </div>
    
    <div class="trainers-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="trainer-card">
                    <div class="trainer-image-container">
                        <img src="<?= !empty($row['image']) ? '../admin/uploads/' . $row['image'] : '../admin/uploads/default-trainer.jpg' ?>" 
                             alt="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>" 
                             class="trainer-image">
                        <span class="availability-badge <?= strtolower($row['availability_status']) ?>">
                            <?= htmlspecialchars($row['availability_status']) ?>
                        </span>
                    </div>
                    
                    <div class="trainer-info">
                        <h3 class="trainer-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></h3>
                        
                        <div class="trainer-meta">
                            <span class="trainer-age">Age: <?= htmlspecialchars($row['age']) ?></span>
                            <span class="trainer-gender"><?= htmlspecialchars(ucfirst($row['gender'])) ?></span>
                        </div>
                        
                        <?php if (!empty($row['specialties'])): ?>
                            <div class="trainer-specialties">
                                <?php 
                                $specialties = explode(', ', $row['specialties']);
                                foreach ($specialties as $specialty): 
                                ?>
                                    <span class="specialty-tag"><?= htmlspecialchars($specialty) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="trainer-actions">
                            <a href="trainers.php?trainer_id=<?= $row['trainer_id'] ?>" class="details-btn">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-trainers">
                <i class="fas fa-user-slash"></i>
                <h3>No Trainers Available</h3>
                <p>Currently there are no active trainers. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>
</body>
</html>