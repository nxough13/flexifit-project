<?php
ob_start(); // Turn on output buffering
session_start();




// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'includes/config.php'; // Include your database connection
   
    // Get user and member status
    $user_id = $_SESSION['user_id'];
    $query = "SELECT m.member_id, m.membership_status
              FROM members m
              JOIN users u ON m.user_id = u.user_id
              WHERE m.user_id = ? AND m.membership_status = 'active'";
   
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
   
    // If active member found, redirect to member area
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $_SESSION['member_id'] = $member['member_id'];
        header("Location: member/index.php");
        exit();
    }
   
    $stmt->close();
    $conn->close();
}
?>








<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlexiFit Gym</title>
   




    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: black;
    color: white;
 
}




.logo img {
    height: 50px;
}
.logo span {
    font-weight: bold;
    font-size: 20px;
    color: yellow;
    margin-left: 10px;
}
nav ul {
    list-style: none;
    display: flex;
    gap: 20px;
}
nav ul li a {
    text-decoration: none;
    color: white;
    font-weight: bold;
}
.home {
    background: url('images/background.jpg') no-repeat center center/cover;
    text-align: center;
    padding: 200px 20px;
}
.home h1, .home h2 {
    font-size: 60px;
    font-weight: bold;
    text-transform: uppercase;
}
.home h1 { color: white; }
.home h2 { color: yellow; }
.btn {
    display: inline-block;
    padding: 15px 30px;
    background: yellow;
    color: black;
    font-weight: bold;
    text-decoration: none;
    margin-top: 20px;
}
.about {
    display: flex;
    align-items: center;
    padding: 50px;
    background: black;
}
.about .text {
    flex: 1;
}
.about h2 {
    color: yellow;
    font-size: 30px;
}
.about img {
    width: 250px;
    margin-left: 20px;
}
.offers {
    text-align: center;
    padding: 50px;
}
.offers h2 {
    color: yellow;
    font-size: 30px;
}
.offers .grid {
    display: flex;
    justify-content: center;
    gap: 20px;
}
.offers .grid img {
    width: 250px;
}
#contact {
    background: url('images/contacts.jpg') no-repeat center center/cover;
    padding: 50px 0;
    color: #FFD700;
    text-align: left;
    position: relative;
}
.contact-container {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 50px;
    max-width: 80%;
    margin: auto;
}
.contact-title {
    font-size: 2.5rem;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 20px;
}
.contact-info-container {
    background: #FFD700;
    color: black;
    padding: 30px 50px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    width: 94.5%;
    margin-top: 20px;
}
.contact-item {
    flex: 1;
    text-align: center;
    font-weight: bold;
    min-width: 250px;
}
.contact-item h3 {
    font-size: 1.2rem;
    text-transform: uppercase;
    font-style: italic;
    margin-bottom: 5px;
}
.contact-item p {
    font-size: 1rem;
}




/* Mission & Vision Section */
.mission-vision {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: url('images/basta.jpg') center/cover no-repeat; /* Background image */
    padding: 100px 10%;
    position: relative;
}


/* Overlay effect for better readability */
.mission-vision::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Dark overlay */
    z-index: 1;
}


/* Text container */
.mission-vision .text {
    position: relative;
    z-index: 2;
    color: #FFD700; /* Yellow text */
    max-width: 45%;
}


/* Positioning Vision & Mission */
.mission-vision .vision {
    text-align: left;
}


.mission-vision .mission {
    text-align: right;
}


/* Larger Heading */
.mission-vision h2 {
    font-size: 3rem;  /* Bigger Heading */
    margin-bottom: 20px;
    text-transform: uppercase;
    font-weight: bold;
}


/* Larger Paragraph */
.mission-vision p {
    font-size: 1.5rem;  /* Bigger Text */
    line-height: 1.7;
    margin-bottom: 15px;
}




    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>




    <!-- Home Section -->
    <section class="home" id="home">
        <h1>BE FIT</h1>
        <h2>BE STRONGER</h2>
        <a href="member/membership-plans.php" class="btn">JOIN TODAY</a>
    </section>
 <!-- Services Section -->
<section class="services" id="services">
    <h2 class="section-title">OUR MEMBERSHIP PLANS</h2>
    <div class="plans-grid">
        <?php
        $query = "SELECT * FROM membership_plans WHERE status = 'active'";
        $result = mysqli_query($conn, $query);
       
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <a href="member/membership-plan-details.php?plan_id=<?php echo $row['plan_id']; ?>" class="plan-card">
                    <div class="card-inner">
                        <?php if(!empty($row['image'])): ?>
                            <div class="card-image">
                                <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <div class="duration-badge"><?php echo htmlspecialchars($row['duration_days']); ?> DAYS</div>
                            </div>
                        <?php endif; ?>
                        <div class="card-content">
                            <h3 class="plan-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="plan-description"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="price-section">
                                <span class="price">$<?php echo htmlspecialchars($row['price']); ?></span>
                                <?php if($row['free_training_session'] > 0): ?>
                                    <span class="free-sessions">+<?php echo $row['free_training_session']; ?> FREE SESSIONS</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php
            }
        } else {
            echo '<p class="no-plans">No active membership plans available at this time.</p>';
        }
        ?>
    </div>
</section>


<style>
    /* Theme Colors */
    :root {
        --black: #121212;
        --yellow: #FFD700;
        --white: #FFFFFF;
        --dark-gray: #1E1E1E;
        --light-gray: #2E2E2E;
    }


    /* Base Styles */
    .services {
        background-color: var(--black);
        padding: 60px 20px;
        color: var(--white);
    }


    .section-title {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 50px;
        color: var(--yellow);
        text-transform: uppercase;
        letter-spacing: 2px;
    }


    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }


    .plan-card {
        display: block;
        text-decoration: none;
        color: inherit;
        height: 100%;
        transition: transform 0.3s ease;
    }


    .plan-card:hover {
        transform: translateY(-10px);
    }


    .card-inner {
        background: var(--dark-gray);
        border-radius: 10px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(255, 215, 0, 0.2);
        transition: all 0.3s ease;
    }


    .plan-card:hover .card-inner {
        border-color: var(--yellow);
        box-shadow: 0 10px 25px rgba(255, 215, 0, 0.1);
    }


    .card-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }


    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }


    .plan-card:hover .card-image img {
        transform: scale(1.05);
    }


    .duration-badge {
        position: absolute;
        bottom: 15px;
        right: 15px;
        background: var(--yellow);
        color: var(--black);
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
        text-transform: uppercase;
    }


    .card-content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }


    .plan-name {
        color: var(--yellow);
        margin: 0 0 15px 0;
        font-size: 1.5rem;
    }


    .plan-description {
        color: #CCCCCC;
        margin: 0 0 20px 0;
        line-height: 1.5;
        flex-grow: 1;
    }


    .price-section {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 215, 0, 0.3);
    }


    .price {
        display: block;
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--yellow);
        margin-bottom: 5px;
    }


    .free-sessions {
        display: inline-block;
        background: rgba(255, 215, 0, 0.1);
        color: var(--yellow);
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }


    .no-plans {
        text-align: center;
        grid-column: 1 / -1;
        color: #AAAAAA;
        font-size: 1.2rem;
    }


    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .plans-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
       
        .section-title {
            font-size: 2rem;
            margin-bottom: 30px;
        }
    }
</style>


<script>
// Enhanced click effect
document.querySelectorAll('.plan-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // Add active class for visual feedback
        this.classList.add('active-click');
       
        // Remove the class after animation completes
        setTimeout(() => {
            this.classList.remove('active-click');
        }, 300);
       
        // Navigate after slight delay
        setTimeout(() => {
            window.location = this.href;
        }, 150);
    });
});
</script>


 <!-- Mission & Vision Section -->
<section class="mission-vision" id="mission-vision">
    <div class="text vision">
        <h2>OUR VISION</h2>
        <p>To be the leading fitness community that inspires and transforms lives, fostering a culture of health, discipline, and well-being.</p>
    </div>
    <div class="text mission">
        <h2>OUR MISSION</h2>
        <p>Our mission is to empower individuals to achieve their fitness goals by providing a welcoming, high-quality, and motivating environment.</p>
    </div>
</section>






    <!-- Contact Section -->
    <section id="contact">
        <div class="contact-container">
            <h2 class="contact-title">Get In Touch Today</h2>
        </div>




        <div class="contact-info-container">
            <div class="contact-item">
                <h3>Mailing Address</h3>
                <p>123 Anywhere St., Any City, ST 12345</p>
            </div>
            <div class="contact-item">
                <h3>Email Address</h3>
                <p>hello@reallygreatsite.com</p>
            </div>
            <div class="contact-item">
                <h3>Phone Number</h3>
                <p>(123) 456-7890</p>
            </div>
        </div>
    </section>






     <!-- About Section -->
     <section class="about" id="about">
        <div class="text">
            <h2>ABOUT OUR FIT FAMILY</h2>
            <p>FlexiFit was founded in 2001 by a husband and wife team, Neo and Emma Graff. Since then, we have expanded to over 115 locations nationwide!</p>
        </div>
        <img src="images/about1.jpg" alt="Trainer">
        <img src="images/about2.jpg" alt="Workout">
    </section>




    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); // At the end of file ?>