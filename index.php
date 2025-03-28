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


.services {
    text-align: center;
    padding: 50px;
    background: black;
}
.services h2 {
    color: yellow;
    font-size: 30px;
    margin-bottom: 20px;
}
.services .grid {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}
.services .service-item {
    background: #222;
    padding: 20px;
    border-radius: 10px;
    width: 300px;
    text-align: center;
    color: white;
}
.services .service-item h3 {
    color: yellow;
    font-size: 22px;
    margin-bottom: 10px;
}
.services .service-item img {
    width: 120px;
    height: auto;
    border-radius: 10px;
    margin-bottom: 10px;
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
        <h2>OUR MEMBERSHIP PLANS</h2>
        <div class="grid">
            <?php
            $query = "SELECT * FROM membership_plans";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<div class="service-item">';
                    echo '<img src="images/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '" style="width: 259px; height: auto;">';
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                    echo '<p><strong>Price:</strong> $' . htmlspecialchars($row['price']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>No membership plans available.</p>';
            }
            ?>
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