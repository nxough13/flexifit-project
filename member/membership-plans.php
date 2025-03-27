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

if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();

}

// neo
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['selected_plan'] = $_POST['selected_plan'];
    $_SESSION['start_date'] = $_POST['start_date'];
    $_SESSION['end_date'] = $_POST['end_date'];

    header('Location: process-payment.php');
    exit();
}
// include '../includes/header.php';
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
            align-items: flex-start;
            gap: 80px;
            width: 90%;
            margin: 0 auto;
        }

        .membership-plans {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
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
            cursor: pointer;
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

        .form-box {
            width: 25%;
            padding: 20px;
            background-color: #2c2c2c;
            color: yellow;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.8);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 1.2%;
        }

        .form-box input[type="date"], .form-box select {
            width: 80%;
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
            border: 4px solid #FFD700;
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
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $image_path = !empty($row['image']) ? '../admin/uploads/' . $row['image'] : 'admin/uploads/default-image.jpg';
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
            <input type="date" id="start_date" name="start_date" required>

            <label for="end_date">Membership End Date:</label>
            <input type="date" id="end_date" name="end_date" readonly required>

            <input type="hidden" id="selected_plan_input" name="selected_plan">

            <button type="submit">Proceed to Payment</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const plans = document.querySelectorAll(".selectable");
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        const selectedPlanInput = document.getElementById("selected_plan_input");

        function updateEndDate() {
            const startDate = startDateInput.value;
            const selectedPlan = document.querySelector(".selected-plan");

            if (startDate && selectedPlan) {
                const duration = parseInt(selectedPlan.getAttribute("data-duration"));
                const startDateObj = new Date(startDate);
                const endDateObj = new Date(startDateObj);
                endDateObj.setDate(startDateObj.getDate() + duration);
                endDateInput.value = endDateObj.toISOString().split('T')[0];
            }
        }

        plans.forEach(plan => {
            plan.addEventListener("click", function () {
                plans.forEach(p => p.classList.remove("selected-plan"));
                this.classList.add("selected-plan");

                selectedPlanInput.value = this.getAttribute("data-plan-id");
                updateEndDate();
            });
        });

        startDateInput.addEventListener("change", updateEndDate);
    });
</script>

</body>
</html>
