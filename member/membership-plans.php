<?php
include '../includes/header.php';
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

// Check user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check user membership status
$user_id = $_SESSION['user_id'];
$membership_status = 'non-member';
$can_select_date = true;

// Query to check if user has active/pending membership
$membership_query = "SELECT membership_status FROM members WHERE user_id = ? ORDER BY end_date DESC LIMIT 1";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $membership_status = $row['membership_status'];
    $can_select_date = ($membership_status == 'expired');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_select_date) {
    $_SESSION['selected_plan'] = $_POST['selected_plan'];
    $_SESSION['start_date'] = $_POST['start_date'];
    $_SESSION['end_date'] = $_POST['end_date'];

    header('Location: process-payment.php');
    exit();
}

// Get all active membership plans
$sql = "SELECT * FROM membership_plans WHERE status = 'active' ORDER BY price ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Membership Plan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #121212;
            --accent: #2c2c2c;
            --card-bg: #1a1a1a;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
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
            margin: 0 auto;
        }
        
        .status-banner {
            background-color: var(--accent);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            border-left: 5px solid var(--primary);
        }
        
        .status-banner p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .status-active {
            border-left-color: var(--success);
        }
        
        .status-pending {
            border-left-color: var(--warning);
        }
        
        .status-expired {
            border-left-color: var(--danger);
        }
        
        .filter-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border-radius: 30px;
            background: var(--card-bg);
            color: var(--text);
            border: 1px solid var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: var(--secondary);
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        @media (min-width: 992px) {
            .main-content {
                flex-direction: row;
            }
        }
        
        .plans-container {
            flex: 3;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .plan-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .plan-card.selected {
            border: 3px solid var(--primary);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .plan-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .plan-details {
            padding: 20px;
        }
        
        .plan-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 10px;
            color: var(--primary);
        }
        
        .plan-price {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 15px 0;
            color: var(--primary);
        }
        
        .plan-duration {
            display: inline-block;
            background: rgba(255, 215, 0, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .plan-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        
        .plan-features {
            margin: 20px 0;
            padding-left: 20px;
        }
        
        .plan-features li {
            margin-bottom: 8px;
            position: relative;
        }
        
        .plan-features li::before {
            content: "✓";
            color: var(--primary);
            position: absolute;
            left: -20px;
        }
        
        .form-container {
            flex: 1;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .date-form {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .date-form h2 {
            color: var(--primary);
            margin-top: 0;
            text-align: center;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: var(--secondary);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:disabled {
            background: #333;
            color: #777;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: var(--secondary);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            font-size: 1.1rem;
        }
        
        .submit-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .submit-btn:disabled {
            background: #555;
            color: #999;
            cursor: not-allowed;
            transform: none;
        }
        
        .view-only-notice {
            text-align: center;
            padding: 15px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h1>Choose Your Membership Plan</h1>
        <div class="header-divider"></div>
    </div>
    
    <!-- Membership Status Banner -->
    <div class="status-banner status-<?= $membership_status ?>">
        <?php if ($membership_status == 'active'): ?>
            <p>You currently have an <strong>active</strong> membership. You can view plans but cannot apply for a new one until your current membership expires.</p>
        <?php elseif ($membership_status == 'pending'): ?>
            <p>Your membership application is <strong>pending</strong>. Please wait for admin approval before applying for another plan.</p>
        <?php elseif ($membership_status == 'expired'): ?>
            <p>Your membership has <strong>expired</strong>. You can renew or apply for a new plan.</p>
        <?php else: ?>
            <p>You don't have an active membership. Choose a plan to get started!</p>
        <?php endif; ?>
    </div>
    
    <!-- Filter Options -->
    <div class="filter-container">
        <button class="filter-btn active" data-filter="all">All Plans</button>
        <button class="filter-btn" data-filter="short">Short Term (≤ 15 days)</button>
        <button class="filter-btn" data-filter="medium">Medium Term (30-60 days)</button>
        <button class="filter-btn" data-filter="long">Long Term (> 60 days)</button>
    </div>
    
    <div class="main-content">
        <div class="plans-container">
            <div class="plans-grid" id="plansGrid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            $image_path = !empty($row['image']) ? '../admin/uploads/' . $row['image'] : '../admin/uploads/default-plan.jpg';
                            $duration_class = '';
                            if ($row['duration_days'] <= 15) {
                                $duration_class = 'short';
                            } elseif ($row['duration_days'] <= 30) {
                                $duration_class = 'medium';
                            } else {
                                $duration_class = 'long';
                            }
                        ?>
                        <div class="plan-card selectable" 
                             data-plan-id="<?= $row['plan_id'] ?>" 
                             data-duration="<?= $row['duration_days'] ?>"
                             data-duration-type="<?= $duration_class ?>">
                            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="plan-image">
                            <div class="plan-details">
                                <h3 class="plan-title"><?= htmlspecialchars($row['name']) ?></h3>
                                <div class="plan-price">₱<?= number_format($row['price'], 2) ?></div>
                                <span class="plan-duration"><?= $row['duration_days'] ?> Days</span>
                                <p class="plan-description"><?= htmlspecialchars($row['description']) ?></p>
                                <ul class="plan-features">
                                    <li>Gym Access during Open Hours</li>
                                    <li>View Gym Related Contents</li>
                                    <li>Book Scheduling Access</li>
                                    <?php if ($row['free_training_session'] > 0): ?>
                                        <li><?= $row['free_training_session'] ?> Free Training Session(s)</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No membership plans available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-container">
            <form method="POST" class="date-form" id="membershipForm">
                <h2>Membership Details</h2>
                
                <div class="form-group">
                    <label for="selected_plan">Selected Plan:</label>
                    <input type="text" id="selected_plan_display" readonly>
                    <input type="hidden" id="selected_plan_input" name="selected_plan">
                </div>
                
                <?php if ($can_select_date): ?>
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" readonly required>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">Proceed to Payment</button>
                <?php else: ?>
                    <div class="view-only-notice">
                        <p>You can only view plans at this time. <?= 
                            ($membership_status == 'active') ? 
                            'Your current membership must expire first.' : 
                            'Your pending application must be processed first.' 
                        ?></p>
                    </div>
                    <button type="button" class="submit-btn" disabled>Not Available</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Plan selection functionality
        const planCards = document.querySelectorAll(".selectable");
        const selectedPlanInput = document.getElementById("selected_plan_input");
        const selectedPlanDisplay = document.getElementById("selected_plan_display");
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        const submitBtn = document.getElementById("submitBtn");
        const filterBtns = document.querySelectorAll(".filter-btn");
        
        // Initialize min date for date picker
        if (startDateInput) {
            const today = new Date();
            const minDate = today.toISOString().split('T')[0];
            startDateInput.min = minDate;
            startDateInput.value = minDate;
        }
        
        // Plan selection
        planCards.forEach(card => {
            card.addEventListener("click", function() {
                // Remove selected class from all cards
                planCards.forEach(c => c.classList.remove("selected"));
                
                // Add selected class to clicked card
                this.classList.add("selected");
                
                // Update form inputs
                const planId = this.getAttribute("data-plan-id");
                const planName = this.querySelector(".plan-title").textContent;
                const duration = parseInt(this.getAttribute("data-duration"));
                
                selectedPlanInput.value = planId;
                selectedPlanDisplay.value = planName;
                
                // Calculate end date if start date is set
                if (startDateInput && startDateInput.value) {
                    updateEndDate(startDateInput.value, duration);
                }
                
                // Enable submit button if all conditions are met
                if (submitBtn && startDateInput && startDateInput.value) {
                    submitBtn.disabled = false;
                }
            });
        });
        
        // Date change handler
        if (startDateInput) {
            startDateInput.addEventListener("change", function() {
                const selectedCard = document.querySelector(".selected");
                if (selectedCard) {
                    const duration = parseInt(selectedCard.getAttribute("data-duration"));
                    updateEndDate(this.value, duration);
                    
                    // Enable submit button if plan is selected
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        }
        
        // Filter functionality
        filterBtns.forEach(btn => {
            btn.addEventListener("click", function() {
                // Update active filter button
                filterBtns.forEach(b => b.classList.remove("active"));
                this.classList.add("active");
                
                const filter = this.getAttribute("data-filter");
                const allCards = document.querySelectorAll(".plan-card");
                
                allCards.forEach(card => {
                    if (filter === "all") {
                        card.style.display = "block";
                    } else {
                        const durationType = card.getAttribute("data-duration-type");
                        card.style.display = (durationType === filter) ? "block" : "none";
                    }
                });
            });
        });
        
        // Helper function to update end date
        function updateEndDate(startDate, duration) {
            if (!startDate || !duration) return;
            
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(startDateObj);
            endDateObj.setDate(startDateObj.getDate() + duration);
            
            const endDateStr = endDateObj.toISOString().split('T')[0];
            endDateInput.value = endDateStr;
        }
        
        // Disable form submission if user can't select dates
        const membershipForm = document.getElementById("membershipForm");
        if (membershipForm && !<?= $can_select_date ? 'true' : 'false' ?>) {
            membershipForm.onsubmit = function(e) {
                e.preventDefault();
                return false;
            };
        }
    });
</script>
</body>
</html>

<?php $conn->close(); ?>