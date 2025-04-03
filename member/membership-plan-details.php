<?php
include '../includes/config.php';


// Validate input
if (!isset($_GET['plan_id']) || !is_numeric($_GET['plan_id'])) {
    header("HTTP/1.0 400 Bad Request");
    die("Invalid plan ID specified");
}


$plan_id = intval($_GET['plan_id']);


// Use prepared statement
$stmt = $conn->prepare("SELECT * FROM membership_plans WHERE plan_id = ? AND status = 'active'");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    die("Membership plan not found or no longer available");
}


$plan = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plan['name']); ?> - FlexiFit</title>
    <style>
    /* Black, Yellow & White Theme */
    :root {
        --black: #121212;
        --dark-black: #0a0a0a;
        --yellow: #FFD700;
        --bright-yellow: #ffea00;
        --white: #ffffff;
        --light-gray: #e0e0e0;
        --dark-gray: #2a2a2a;
    }


    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: var(--white);
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background-color: var(--black);
    }


    .plan-container {
        background: var(--dark-black);
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.1);
        border: 1px solid var(--dark-gray);
    }


    .plan-header {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 30px;
    }


    .plan-image {
        flex: 1;
        min-width: 300px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--yellow);
    }


    .plan-image img {
        width: 100%;
        height: 100%;
        max-height: 400px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }


    .plan-image:hover img {
        transform: scale(1.02);
    }


    .plan-info {
        flex: 2;
        min-width: 300px;
    }


    h1 {
        color: var(--yellow);
        margin-top: 0;
        font-size: 2.2em;
        text-transform: uppercase;
        letter-spacing: 1px;
    }


    .price {
        font-size: 1.8em;
        color: var(--bright-yellow);
        font-weight: bold;
        margin: 20px 0;
    }


    .duration {
        font-weight: bold;
        font-size: 1.2em;
        color: var(--light-gray);
        background: var(--dark-gray);
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
    }


    .features {
        margin: 30px 0;
        padding: 20px;
        background: rgba(255, 215, 0, 0.05);
        border-radius: 8px;
        border-left: 3px solid var(--yellow);
    }


    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        color: var(--light-gray);
    }


    .feature-item:before {
        content: "•";
        color: var(--yellow);
        font-weight: bold;
        display: inline-block;
        width: 1em;
        margin-left: -1em;
    }


    .back-link {
        display: inline-block;
        margin-top: 30px;
        padding: 12px 25px;
        background: var(--yellow);
        color: var(--black);
        text-decoration: none;
        border-radius: 5px;
        transition: all 0.3s ease;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 2px solid transparent;
    }


    .back-link:hover {
        background: transparent;
        color: var(--yellow);
        border-color: var(--yellow);
        transform: translateY(-2px);
    }


    @media (max-width: 768px) {
        .plan-header {
            flex-direction: column;
        }
       
        .plan-image {
            min-width: 100%;
        }
       
        h1 {
            font-size: 1.8em;
        }
    }


    /* Animation for visual feedback */
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255, 215, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
    }


    .plan-container:hover {
        animation: pulse 1.5s infinite;
    }
</style>
</head>
<body>
    <div class="plan-container">
        <div class="plan-header">
            <?php if(!empty($plan['image'])): ?>
            <div class="plan-image">
                <img src="../images/<?php echo htmlspecialchars($plan['image']); ?>" alt="<?php echo htmlspecialchars($plan['name']); ?>">
            </div>
            <?php endif; ?>
           
            <div class="plan-info">
                <h1><?php echo htmlspecialchars($plan['name']); ?></h1>
                <p class="duration"><?php echo htmlspecialchars($plan['duration_days']); ?> day program</p>
                <p class="price">$<?php echo htmlspecialchars($plan['price']); ?></p>
               
                <div class="features">
                    <h3>Plan Features:</h3>
                    <p><?php echo htmlspecialchars($plan['description']); ?></p>
                   
                    <?php if($plan['free_training_session'] > 0): ?>
                    <div class="feature-item">
                        <span>✔ Includes <?php echo $plan['free_training_session']; ?> free training sessions</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
       
        <a href="/flexifit-project/index.php" class="back-link">← Back to All Membership Plans</a>
    </div>
</body>
</html>