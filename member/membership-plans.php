<?php
include '../includes/header.php';
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);
$sql = "SELECT * FROM membership_plans";
$result = $conn->query($sql);

// Handle form submission to store selected plan in session
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store selected plan, start date, and end date in session
    $_SESSION['selected_plan'] = $_POST['selected_plan'];
    $_SESSION['start_date'] = $_POST['start_date'];
    $_SESSION['end_date'] = $_POST['end_date'];

    // Redirect to payment page
    header('Location: process-payment.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Membership Plan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: white;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align top vertically */
            gap: 80px;
            width: 90%;
            margin: 0 auto;
        }

        .membership-plans {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            justify-content: center;
            align-items: center;
            max-width: 900px;
            margin-top: 1%;
        }

        .plan-box {
            background-color: #1a1a1a;
            border: 2px solid yellow;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            border-radius: 10px;
        }

        .plan-box img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .plan-box h3 {
            font-size: 24px;
            font-weight: bold;
        }

        .plan-box p {
            font-weight: bold;
        }

        .plan-box button {
            background-color: yellow;
            color: black;
            padding: 12px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }

        .plan-box button:hover {
            background-color: #e0a800;
        }

        .form-box {
            width: 25%;
            padding: 20px;
            background-color: #2c2c2c;
            color: yellow;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex: 0.75;
            margin-top: 1.2%;
        }

        .form-box input[type="text"], .form-box input[type="date"], .form-box select {
            width: 50%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid yellow;
            border-radius: 5px;
            background-color: #1e1e1e;
            color: yellow;
        }

        .form-box button {
            background-color: yellow;
            color: black;
            padding: 12px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }

        .form-box button:hover {
            background-color: #e0a800;
        }

        .container-flex {
            display: flex;
            justify-content: space-between;
        }

        h1 {
            color: yellow;
            margin-top: 2%;
            margin-bottom: 10px;
            margin-left: 10%;
        }

        hr {
            width: 48%;
            margin-left: 9.5%;
            border: 2.5px solid white;
        }

        .selected-plan {
            border: 4px solid #FFD700; /* Highlight with gold */
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
        }
    </style>
</head>
<body>

<div class="title1">
    <h1>Select Membership Plan</h1>
    <hr>
</div>

<div class="container">
    <div class="membership-plans">
        <?php
        // Display membership plans fetched from the database
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $image_path = !empty($row['image']) ? '../admin/uploads/' . $row['image'] : 'admin/uploads/default-image.jpg'; // Ensure there's a fallback image
                echo '<div class="plan-box selectable" data-plan-id="' . $row["plan_id"] . '" data-duration="' . $row["duration_days"] . '">';
                echo '<img src="' . $image_path . '" alt="Plan Image">';  
                echo '<h3>' . htmlspecialchars($row["name"]) . '</h3>';
                echo '<p>' . htmlspecialchars($row["description"]) . '</p>';
                echo '<p>For only P' . number_format($row["price"], 2) . '</p>';
                echo '<p>Duration: ' . htmlspecialchars($row["duration_days"]) . ' days</p>';
                echo '</div>';
            }
        }
        ?>
    </div>

    <div class="form-box">
        <h2>Enter Start Date</h2>
        <form method="POST">
            <label for="start_date">Preferred Start Date:</label>
            <input style="margin-left: 5%;" type="date" id="start_date" name="start_date" required onchange="updateEndDate()">
            
            <label for="end_date">Membership End Date:</label>
            <input style="margin-left: 1.6%;" type="date" id="end_date" name="end_date" required readonly>

            <button style="margin-left: 55%;" type="submit">Proceed to Payment</button>
        </form>
    </div>
</div>

<script>
    function updateEndDate() {
        const startDate = document.getElementById('start_date').value;
        const selectedPlan = document.querySelector('.selected-plan');

        if (startDate && selectedPlan) {
            const duration = selectedPlan.getAttribute('data-duration');
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(startDateObj);
            endDateObj.setDate(startDateObj.getDate() + parseInt(duration));

            const endDateInput = document.getElementById('end_date');
            endDateInput.value = endDateObj.toISOString().split('T')[0];
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const plans = document.querySelectorAll(".selectable");

        plans.forEach(plan => {
            plan.addEventListener("click", function () {
                plans.forEach(p => p.classList.remove("selected-plan"));
                this.classList.add("selected-plan");

                const selectedPlanId = this.getAttribute("data-plan-id");
                document.getElementById("selected_plan_input").value = selectedPlanId;

                updateEndDate(parseInt(this.getAttribute("data-duration")));
            });
        });
    });

    function updateEndDate(duration) {
        const startDate = document.getElementById('start_date').value;
        if (startDate) {
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(startDateObj);
            endDateObj.setDate(startDateObj.getDate() + duration);
            document.getElementById('end_date').value = endDateObj.toISOString().split('T')[0];
        }
    }
</script>
</body>
</html>
