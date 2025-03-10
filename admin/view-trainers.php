<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "flexifit_db");
include '../includes/header.php';
//hello

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// neo
// Fetch all trainers from the database
$sql = "SELECT t.trainer_id, t.first_name, t.last_name, t.email, t.age, t.gender, t.image, t.status, t.availability_status,
        GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties
        FROM trainers t
        LEFT JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
        LEFT JOIN specialty s ON ts.specialty_id = s.specialty_id
        GROUP BY t.trainer_id ORDER BY t.trainer_id ASC";
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
    background-color: #121212; /* Dark background */
    color: white;
    margin-top: 80px; /* Adjust margin-top to avoid header overlap */
    padding: 20px;
    text-align: center;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    background: #1f1f1f; /* Dark background for container */
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 0px 15px rgba(255, 255, 0, 0.8); /* Yellow glow */
}

h1 {
    color:rgba(255, 255, 0, 0.8); /* Yellow color for headings */
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #2c2c2c; /* Dark background for table */
}

table, th, td {
    border: 1px solid rgba(255, 255, 0, 0.8); /* Yellow border for table */
}

th, td {
    padding: 12px;
    text-align: center;
    color: white;
}

th {
    background:rgba(255, 255, 0, 0.8); /* Yellow background for table header */
    color: black;
}

tr:nth-child(even) {
    background: #3c3c3c; /* Slightly lighter background for even rows */
}

.action-btn {
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    color: white;
    font-size: 16px;
}

.edit-btn {
    background: #28a745; /* Green background for edit button */
}

.edit-btn.disabled {
    background: #6c757d;
    pointer-events: none;
}

.delete-btn {
    background: #dc3545; /* Red background for delete button */
}

.enable-btn {
    background: #007BFF; /* Blue background for enable button */
}

.add-btn {
    display: inline-block;
    background:rgba(255, 255, 0, 0.8); /* Yellow background for add button */
    color: black;
    padding: 10px 15px;
    margin-top: 10px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
}

.add-btn:hover {
    background: #e0a800; /* Darker yellow on hover */
}

.success-message {
    background-color: green;
    color: white;
    padding: 15px;
    margin: 20px 0;
    font-size: 18px;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
}

.error-message {
    background-color: red;
    color: white;
    padding: 15px;
    margin: 20px 0;
    font-size: 18px;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
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
    <h1>Trainer List</h1>
    <?php
// Display success or error messages if available
if (isset($_SESSION['success_message'])) {
    echo "<div class='success-message'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Clear the message after displaying
}

if (isset($_SESSION['error_message'])) {
    echo "<div class='error-message'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the message after displaying
}
?>
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
            <th>Availability Status</th> <!-- New column for availability status -->
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $edit_class = ($row["status"] == "disabled") ? 'edit-btn disabled' : 'edit-btn'; // Disable edit button if status is "disabled"
                $edit_button = ($row["status"] == "disabled") ? '✏️' : '✏️'; // Change icon for disabled trainer

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
                echo "<td>" . ($row["specialties"] ? $row["specialties"] : "N/A") . "</td>";
                echo "<td>" . ucfirst($row["availability_status"]) . "</td>";
                echo "<td>" . ucfirst($row["status"]) . "</td>";
                echo "<td>";
                
               // Edit button
               echo "<a href='edit-trainers.php?trainer_id=" . $row["trainer_id"] . "' class='action-btn $edit_class' style='text-decoration: none;'>✏️</a>";

                // If active, show delete (disable) button
                if ($row["status"] == "active") {
                    echo "<a href='#' onclick='disableTrainer(" . $row["trainer_id"] . ")' 
                            class='action-btn delete-btn' 
                            style='text-decoration: none;'>❌</a>";
                } else {
                    // If disabled, show enable (reload) button
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
