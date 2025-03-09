<?php
// Add your server-side logic to fetch and process data here
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - FlexiFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet"> <!-- Google Font -->
    <style>
      body, html {
    margin: 0;
    padding: 0;
    overflow-x: hidden; /* Prevent horizontal overflow */
    width: 100%;
    background-color: #121212;
}

header {
    background-color: #000;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
}

header a {
    color: white;
    font-weight: bold;
    text-decoration: none;
}

/* Left Image Sidebar */
.left-image-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 20%;
    height: 100vh;
    background-image: url('../images/Left-image.jpg');
    background-size: cover;
    background-position: center;
    border-right: 5px solid yellow;
    z-index: 1;
}

/* Container for the main content */
.container {
    display: flex;
    justify-content: space-between; /* Adjust space between the content boxes */
    align-items: flex-start; /* Align items to the top */
    gap: 20px; /* Reduce gap between left and right boxes */
    margin: 0 auto; /* Keep the content centered */
    padding-left: 22%; /* Add left padding to adjust for fixed sidebar image */
    width: 75%; /* Set width of the content area */
}

/* Left Box (Content box for Chosen Plan) */
.left-box {
    width: 48%; /* Adjust width for better alignment */
    background-color: #d3d3d3; /* Light gray background */
    border-radius: 10px;
    padding: 20px;
    color: black;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
}

.left-box h2 {
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: bold;
}

.left-box p {
    font-size: 16px;
    font-weight: normal;
    margin-bottom: 10px;
}

/* Right Box (Payment Method box) */
.right-box {
    width: 48%; /* Adjusted width */
    background-color: #d3d3d3; /* Light gray background */
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(255, 255, 0, 0.5);
    color: black;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.right-box h2 {
    color: black;
    font-size: 28px;
    margin-bottom: 15px;
    font-weight: bold;
}

.right-box label {
    font-size: 18px;
    color: black;
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}

.right-box input[type="text"],
.right-box input[type="date"],
.right-box select {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid yellow;
    border-radius: 5px;
    background-color: yellow;
    color: black;
}

.right-box button {
    background-color: yellow;
    color: black;
    padding: 15px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-weight: bold;
    margin-top: 10px;
}

.right-box button:hover {
    background-color: #e0a800;
}

.radio-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.radio-container input[type="radio"]:checked {
    background-color: yellow;
}

.radio-container label {
    color: black;
    font-weight: bold;
}

h1 {
    margin-top: 1%;
    color: black;
    font-size: larger;
    margin-top: 5%;
    margin-bottom: 10px;
    margin-left: 21%;
}

hr {
    width: 48%;
    margin-left: 7%;
}

.note {
    font-style: italic;
    color: black;
    margin-top: 10px;
}

/* Adjustments for responsiveness */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
        align-items: center;
    }

    .left-box, .right-box {
        width: 80%; /* Adjust both boxes to 80% on smaller screens */
    }
}


    </style>
</head>
<body>


<!-- Left Image Sidebar -->
<div class="left-image-container"></div>

<div class="container" style="margin-top: 15%;">
    <div class="left-box">
        <h2>Chosen Plan: 7-Day Plan</h2>
        <p>Price: ₱820.00</p>
        <p>Starting Date: 03-01-2025</p>
        <p>End Date: 03-08-2025</p>
        <p>Description: Enjoy full access to our gym facilities, explore fitness tips on our content dashboard, read member reviews, and benefit from a free one-day training session with a professional trainer.</p>
    </div>

    <div class="right-box">
        <h2>Choose Your Payment Method</h2>

        <div class="radio-container">
            <div>
                <input type="radio" id="payOnSite" name="paymentMethod" onclick="togglePaymentFields()" checked>
                <label for="payOnSite">Pay On Site</label>
            </div>
            <div>
                <input type="radio" id="gcash" name="paymentMethod" onclick="togglePaymentFields()">
                <label for="gcash">G-Cash</label>
            </div>
            <div>
                <input type="radio" id="payWithCard" name="paymentMethod" onclick="togglePaymentFields()">
                <label for="payWithCard">Pay With Card</label>
            </div>
        </div>

        <div id="payOnSiteNote" class="note">
    Note: Membership Application will be set as pending until paid and activated by an Admin.
</div>

<div id="gcashFields">
    <label for="gcashNumber">G-Cash Number:</label>
    <input type="text" id="gcashNumber" placeholder="Enter GCash Number">

    <label for="referenceNumber">Reference Number:</label>
    <input type="text" id="referenceNumber" placeholder="Enter Reference Number">
</div>

<div id="cardFields">
    <label for="cardType">Type of Card:</label>
    <input type="text" id="cardType" placeholder="Enter Type of Card">

    <label for="cardID">Card ID Number:</label>
    <input type="text" id="cardID" placeholder="Enter Card ID Number">
</div>


        <button type="submit">Submit</button>
    </div>
</div>

<script>
    function togglePaymentFields() {
        const gcashNumber = document.getElementById('gcashNumber');
        const referenceNumber = document.getElementById('referenceNumber');
        const cardType = document.getElementById('cardType');
        const cardID = document.getElementById('cardID');

        const isGcash = document.getElementById('gcash').checked;
        const isCard = document.getElementById('payWithCard').checked;
        const isPayOnSite = document.getElementById('payOnSite').checked;

        // Enable fields for selected method, disable others
        gcashNumber.disabled = !isGcash;
        referenceNumber.disabled = !isGcash;

        cardType.disabled = !isCard;
        cardID.disabled = !isCard;

        // No input fields for pay on site, just show a note
        document.getElementById('payOnSiteNote').style.display = isPayOnSite ? 'block' : 'none';
    }

    // Run function on page load to initialize correct state
    document.addEventListener('DOMContentLoaded', togglePaymentFields);
</script>

</body>
</html>
