<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all membership plans from the database
$sql = "SELECT * FROM membership_plans ORDER BY plan_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Membership Plans</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: white;
            margin-top: 80px;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #1f1f1f;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 15px rgba(255, 255, 0, 0.8);
        }

        h1 {
            color: rgba(255, 255, 0, 0.8);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #2c2c2c;
        }

        table, th, td {
            border: 1px solid rgba(255, 255, 0, 0.8);
        }

        th, td {
            padding: 12px;
            text-align: center;
            color: white;
        }

        th {
            background: rgba(255, 255, 0, 0.8);
            color: black;
        }

        tr:nth-child(even) {
            background: #3c3c3c;
        }

        .action-btn {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            font-size: 16px;
        }

        .edit-btn {
            background: #28a745;
        }

        .edit-btn.disabled {
            background: #6c757d;
            pointer-events: none;
            cursor: not-allowed;
        }

        .delete-btn {
            background: #dc3545;
        }

        .enable-btn {
            background: #007BFF;
        }

        .add-btn {
            display: inline-block;
            background: rgba(255, 255, 0, 0.8);
            color: black;
            padding: 10px 15px;
            margin-top: 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .add-btn:hover {
            background: #e0a800;
        }
    </style>

    <script>
        function disablePlan(planId) {
            if (confirm("Are you sure you want to disable this plan?")) {
                window.location.href = "disable-plan.php?id=" + planId;
            }
        }

        function enablePlan(planId) {
            if (confirm("Do you want to enable this plan again?")) {
                window.location.href = "enable-plan.php?id=" + planId;
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Membership Plan List</h1>

    <a href="create-plan.php" class="add-btn">+ Add New Plan</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Plan Name</th>
            <th>Duration (Days)</th>
            <th>Price</th>
            <th>Free Training Sessions</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $edit_button_class = ($row["status"] == "disabled") ? 'edit-btn disabled' : 'edit-btn';
                $edit_button_icon = ($row["status"] == "disabled") ? '✏️ (Disabled)' : '✏️';
                $status_text = ucfirst($row["status"]);

                echo "<tr>";
                echo "<td>" . $row["plan_id"] . "</td>";
                echo "<td>" . $row["name"] . "</td>";
                echo "<td>" . $row["duration_days"] . "</td>";
                echo "<td>" . $row["price"] . "</td>";
                echo "<td>" . $row["free_training_session"] . "</td>";
                echo "<td>" . $status_text . "</td>";
                echo "<td>";

                // Edit button
                echo "<a href='edit-plan.php?id=" . $row["plan_id"] . "' class='action-btn $edit_button_class'>" . $edit_button_icon . "</a>";

                // Delete (Disable) button
                if ($row["status"] == "active") {
                    echo "<a href='#' onclick='disablePlan(" . $row["plan_id"] . ")' class='action-btn delete-btn'>❌</a>";
                } else {
                    // Enable (Re-enable) button
                    echo "<a href='#' onclick='enablePlan(" . $row["plan_id"] . ")' class='action-btn enable-btn'>🔄</a>";
                }

                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No plans found.</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</div>
</body>
</html>
