<?php
ob_start(); // Turn on output buffering

session_start();
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include "../includes/header.php";
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure member access
if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
}

// Fetch all equipment from the equipment_inventory table
$sql = "SELECT ei.inventory_id, e.name AS equipment_name, e.description, ei.identifier, ei.status, ei.active_status, e.image
        FROM equipment_inventory ei
        JOIN equipment e ON ei.equipment_id = e.equipment_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipments</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #000000;
            --accent: #1a1a1a;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --card-bg: #1e1e1e;
            --disabled: #3a3a3a;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--accent);
        }
        
        h2 {
            color: var(--primary);
            font-size: 2rem;
            margin: 0;
            font-weight: 600;
        }
        
        .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            color: var(--secondary);
            background-color: var(--primary);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 10px 0;
        }
        
        .equipment-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--accent);
        }
        
        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.3);
        }
        
        .equipment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary);
        }
        
        .equipment-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--accent);
        }
        
        .equipment-info {
            margin-top: 10px;
            text-align: left;
        }
        
        .equipment-info p {
            margin: 8px 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .equipment-info strong {
            color: var(--primary);
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 5px;
        }
        
        .status-available {
            background-color: #28a745;
            color: white;
        }
        
        .status-in_use {
            background-color: #ffc107;
            color: black;
        }
        
        .status-maintenance {
            background-color: #dc3545;
            color: white;
        }
        
        .status-disabled {
            background-color: var(--disabled);
            color: var(--text-secondary);
        }
        
        /* Grayed out effect for disabled equipment */
        .disabled-equipment {
            background-color: var(--disabled) !important;
            opacity: 0.7 !important;
            pointer-events: none;
            filter: grayscale(80%);
        }
        
        .disabled-equipment::before {
            background: var(--text-secondary) !important;
        }
        
        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 15px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 30px;
            border: none;
            background: var(--card-bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            border: 1px solid var(--accent);
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 10px 15px;
            border-radius: 30px;
            background: var(--card-bg);
            color: var(--text);
            border: 1px solid var(--accent);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: var(--secondary);
            border-color: var(--primary);
        }
        
        .filter-btn:hover {
            background: var(--accent);
        }
        
        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .search-filter {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Our Equipment</h2>
        <!-- You can add buttons or other elements here if needed -->
    </div>
    
    <div class="search-filter">
        <div class="search-box">
            <input type="text" placeholder="Search equipment..." id="searchInput">
        </div>
        <div class="filter-options">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="available">Available</button>
            <button class="filter-btn" data-filter="in_use">In Use</button>
            <button class="filter-btn" data-filter="maintenance">Maintenance</button>
        </div>
    </div>

    <div class="grid-container" id="equipmentGrid">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <?php 
                $is_disabled = ($row['active_status'] == 'disabled');
                $status_class = strtolower(str_replace(' ', '-', $row['status']));
            ?>
            
            <div class="equipment-card <?= $is_disabled ? 'disabled-equipment' : '' ?>" 
                 data-status="<?= $status_class ?>" 
                 data-name="<?= htmlspecialchars(strtolower($row['equipment_name'])) ?>">
                <img src="<?= !empty($row['image']) && file_exists('../admin/uploads/' . $row['image']) 
                             ? '../admin/uploads/' . htmlspecialchars($row['image']) 
                             : '../admin/uploads/placeholder.png' ?>" 
                     alt="<?= htmlspecialchars($row['equipment_name']) ?>">
                    
                <div class="equipment-info">
                    <p><strong>Name:</strong> <?= htmlspecialchars($row['equipment_name']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                    <p><strong>ID:</strong> <?= htmlspecialchars($row['identifier']) ?>
                        <span class="status-badge status-<?= $status_class ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </p>
                    <?php if($is_disabled): ?>
                        <p><strong>Status:</strong> <span class="status-badge status-disabled">Disabled</span></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.equipment-card');
        
        cards.forEach(card => {
            const equipmentName = card.getAttribute('data-name');
            if (equipmentName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            const cards = document.querySelectorAll('.equipment-card');
            
            cards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>

</body>
</html>

<?php $conn->close(); ?>
<?php ob_end_flush(); // At the end of file ?>