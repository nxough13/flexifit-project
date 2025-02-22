<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all trainers from the database (active & inactive)
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
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .action-btn {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            font-size: 14px;
        }
        .edit-btn {
            background: #28a745;
        }
        .edit-btn.disabled {
            background: #6c757d; /* Gray color for disabled */
            pointer-events: none; /* Prevent clicking */
        }
        .delete-btn {
            background: #dc3545;
        }
        .enable-btn {
            background: #007BFF;
        }
        .add-btn {
            display: inline-block;
            background: #007BFF;
            color: white;
            padding: 10px 15px;
            margin-top: 10px;
            text-decoration: none;
            border-radius: 4px;
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
            <th>Specialty</th>
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
                    echo "<img src='uploads/default.png' width='50' height='50' style='border-radius:50px;'>"; // Default image
                }
                echo "</td>";
                echo "<td>" . $row["first_name"] . "</td>";
                echo "<td>" . $row["last_name"] . "</td>";
                echo "<td>" . $row["email"] . "</td>";
                echo "<td>" . $row["specialty"] . "</td>";
                echo "<td>" . ucfirst($row["status"]) . "</td>";
                echo "<td>";

                // Edit button (disabled if trainer is inactive)
                if ($row["status"] == "active") {
                    echo "<a href='edit-trainers.php?id=" . $row["trainer_id"] . "' 
                            class='action-btn edit-btn' 
                            style='text-decoration: none; font-size: 20px;'>✏️</a>";
                } else {
                    echo "<a href='#' class='action-btn edit-btn disabled' 
                            style='text-decoration: none; font-size: 20px;'>✏️</a>";
                }

                // Delete/Restore button
                if ($row["status"] == "active") {
                    echo "<a href='#' onclick='disableTrainer(" . $row["trainer_id"] . ")' 
                            class='action-btn delete-btn' 
                            style='text-decoration: none; font-size: 20px;'>❌</a>";
                } else {
                    echo "<a href='#' onclick='enableTrainer(" . $row["trainer_id"] . ")' 
                            class='action-btn enable-btn' 
                            style='text-decoration: none; font-size: 20px;'>🔄</a>";
                }

                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No trainers found.</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</div>

</body>
</html>
