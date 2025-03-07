<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all trainers from the database
$sql = "SELECT * FROM trainers ORDER BY trainer_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Trainers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: white;
            margin-top: 50px;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #1f1f1f;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 15px rgba(255, 193, 7, 0.8);
        }
        h2 {
            color: #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #2c2c2c;
        }
        table, th, td {
            border: 1px solid #ffc107;
        }
        th, td {
            padding: 12px;
            text-align: center;
            color: white;
        }
        th {
            background: #ffc107;
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
        }
        .delete-btn {
            background: #dc3545;
        }
        .enable-btn {
            background: #007BFF;
        }
        .add-btn {
            display: inline-block;
            background: #ffc107;
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
        function disableTrainer(trainerId) {
            if (confirm("Are you sure you want to disable this trainer?")) {
                window.location.href = "disable-trainer.php?id=" + trainerId;
            }
        }

        function enableTrainer(trainerId) {
            if (confirm("Do you want to enable this trainer again?")) {
                window.location.href = "enable-trainer.php?id=" + trainerId;
            }
        }
    </script>

</head>
<body>

<div class="container">
    <h2>Trainer List</h2>
    <a href="create-trainers.php" class="add-btn">+ Add New Trainer</a>
    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Specialty</th>
            <th>Availability</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["trainer_id"] . "</td>";
                echo "<td>";
                if (!empty($row["image"]) && file_exists(__DIR__ . "/uploads/" . $row["image"])) {
                    echo "<img src='uploads/" . $row["image"] . "' width='50' height='50' style='border-radius:50px;'>";
                } else {
                    echo "<img src='uploads/default.png' width='50' height='50' style='border-radius:50px;'>";
                }
                echo "</td>";
                echo "<td>" . $row["first_name"] . "</td>";
                echo "<td>" . $row["last_name"] . "</td>";
                echo "<td>" . $row["email"] . "</td>";
                echo "<td>" . ($row["age"] ? $row["age"] : "N/A") . "</td>";
                echo "<td>" . ucfirst($row["gender"]) . "</td>";
                echo "<td>" . ($row["specialty"] ? $row["specialty"] : "N/A") . "</td>";
                echo "<td>" . ucfirst($row["availability_status"]) . "</td>";
                echo "<td>" . ucfirst($row["status"]) . "</td>";
                echo "<td>";

                if ($row["status"] == "active") {
                    echo "<a href='edit-trainers.php?id=" . $row["trainer_id"] . "' 
                            class='action-btn edit-btn' 
                            style='text-decoration: none;'>✏️</a>";
                } else {
                    echo "<a href='#' class='action-btn edit-btn disabled' 
                            style='text-decoration: none;'>✏️</a>";
                }

                if ($row["status"] == "active") {
                    echo "<a href='#' onclick='disableTrainer(" . $row["trainer_id"] . ")' 
                            class='action-btn delete-btn' 
                            style='text-decoration: none;'>❌</a>";
                } else {
                    echo "<a href='#' onclick='enableTrainer(" . $row["trainer_id"] . ")' 
                            class='action-btn enable-btn' 
                            style='text-decoration: none;'>🔄</a>";
                }

                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='11'>No trainers found.</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</div>

</body>
</html>
