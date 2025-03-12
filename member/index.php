<?php
session_start();
include '../includes/header.php';
if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
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
    background: url('../images/background.jpg') no-repeat center center/cover;
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
    background: url('../images/contacts.jpg') no-repeat center center/cover;
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

    </style>
</head>
<body>
    

    <!-- Home Section -->
    <section class="home" id="home">
        <h1>BE FIT</h1>
        <h2>BE STRONGER</h2>
        
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="text">
            <h2>ABOUT OUR FIT FAMILY</h2>
            <p>FlexiFit was founded in 2001 by a husband and wife team, Neo and Emma Graff. Since then, we have expanded to over 115 locations nationwide!</p>
        </div>
        <img src="../images/about1.jpg" alt="Trainer">
        <img src="../images/about2.jpg" alt="Workout">
    </section>

    <!-- Offers Section -->
    <section class="offers" id="offers">
        <h2>WHAT WE OFFER</h2>
        <p>We're committed to bringing you the best workout experience.</p>
        <div class="grid">
            <img src="../images/offers1.jpg" alt="Tour Our Gym">
            <img src="../images/offers2.jpg" alt="Group Classes">
            <img src="../images/offers3.jpg" alt="Personal Training">
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

    <?php include '../includes/footer.php'; ?>
</body>
</html>
