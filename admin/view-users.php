<?php
ob_start(); // Start output buffering
session_start();
include '../includes/header.php';

require '../vendor/autoload.php'; // For PHPMailer
$conn = new mysqli("localhost", "root", "", "flexifit_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get current admin name
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS admin_name FROM users WHERE user_id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin_name = $admin_result->fetch_assoc()['admin_name'];

// Function to send email notification
function sendEmailNotification($email, $subject, $body) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexifit04@gmail.com';
        $mail->Password = 'dwnw xuwn baln ljbp';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
       
        // Recipients
        $mail->setFrom('flexifit04@gmail.com', 'FlexiFit Gym');
        $mail->addAddress($email);
       
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
       
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle user type update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_type'])) {
    $user_id = intval($_POST['user_id']);
    $new_user_type = $_POST['user_type'];
    $change_reason = $_POST['change_reason'];
    
    // Get user's current email and name
    $user_query = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) AS user_name, user_type FROM users WHERE user_id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update user type
        $update_stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $new_user_type, $user_id);
        
        if ($update_stmt->execute()) {
            // Record the change in UserTypeUpdate table
            $log_stmt = $conn->prepare("INSERT INTO UserTypeUpdate (user_id, name, user_type, change_reason) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("isss", $user_id, $user_data['user_name'], $new_user_type, $change_reason);
            $log_stmt->execute();
            
            // Send email notification
            $email_subject = 'Your Account Type Has Been Updated';
            $email_body = "
                <h2>Account Type Update</h2>
                <p>Hello {$user_data['user_name']},</p>
                <p>Your account type has been updated from <strong>{$user_data['user_type']}</strong> to: <strong>{$new_user_type}</strong></p>
                <p><strong>Reason:</strong> {$change_reason}</p>
                <p>Changed by: <strong>{$admin_name}</strong></p>
                <p>If you believe this is a mistake, please contact our support team.</p>
                <p>Thank you,<br>FlexiFit Team</p>
            ";
            
            $email_sent = sendEmailNotification($user_data['email'], $email_subject, $email_body);
            
            // If changing to member and has pending membership, update membership status
            if ($new_user_type === 'member') {
                $membership_check = $conn->prepare("SELECT member_id FROM members WHERE user_id = ? AND membership_status = 'pending'");
                $membership_check->bind_param("i", $user_id);
                $membership_check->execute();
                
                if ($membership_check->get_result()->num_rows > 0) {
                    $update_membership = $conn->prepare("UPDATE members SET membership_status = 'active' WHERE user_id = ?");
                    $update_membership->bind_param("i", $user_id);
                    $update_membership->execute();
                    
                    // Send membership activation email
                    $membership_email_subject = 'Your Membership Has Been Activated';
                    $membership_email_body = "
                        <h2>Membership Activated</h2>
                        <p>Hello {$user_data['user_name']},</p>
                        <p>Your membership status has been updated to: <strong>active</strong></p>
                        <p>You can now enjoy all the benefits of being a FlexiFit member!</p>
                        <p>If you have any questions, please contact our support team.</p>
                        <p>Thank you,<br>FlexiFit Team</p>
                    ";
                    
                    sendEmailNotification($user_data['email'], $membership_email_subject, $membership_email_body);
                }
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "User type updated successfully! " . ($email_sent ? "Notification sent." : "Could not send email notification.");
        } else {
            throw new Exception("Error updating user type: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: view-users.php");
    exit();
}

// Handle membership status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_membership_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['membership_status'];
    $change_reason = $_POST['change_reason'];
    
    // Get user's current email and name
    $user_query = $conn->prepare("SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) AS user_name, m.membership_status 
                                 FROM users u 
                                 JOIN members m ON u.user_id = m.user_id 
                                 WHERE u.user_id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update membership status
        $update_stmt = $conn->prepare("UPDATE members SET membership_status = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $new_status, $user_id);
        
        if ($update_stmt->execute()) {
            // Send email notification if status changed to active
            if ($new_status === 'active') {
                $email_subject = 'Your Membership Has Been Activated';
                $email_body = "
                    <h2>Membership Status Update</h2>
                    <p>Hello {$user_data['user_name']},</p>
                    <p>Your membership status has been updated from <strong>{$user_data['membership_status']}</strong> to: <strong>{$new_status}</strong></p>
                    <p><strong>Reason:</strong> {$change_reason}</p>
                    <p>Changed by: <strong>{$admin_name}</strong></p>
                    <p>You can now enjoy all the benefits of being a FlexiFit member!</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p>Thank you,<br>FlexiFit Team</p>
                ";
                
                $email_sent = sendEmailNotification($user_data['email'], $email_subject, $email_body);
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Membership status updated successfully! " . 
                ($new_status === 'active' && $email_sent ? "Notification sent." : "");
        } else {
            throw new Exception("Error updating membership status: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: view-users.php");
    exit();
}

// Determine view type
$viewType = isset($_GET['view']) && in_array($_GET['view'], ['card', 'table']) ? $_GET['view'] : 'card';

// Fetch filter values
$filter_user_type = isset($_GET['filter_user_type']) ? $_GET['filter_user_type'] : 'all';
$filter_membership = isset($_GET['filter_membership']) ? $_GET['filter_membership'] : 'all';

// Base query for users
if ($viewType === 'table') {
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.user_type, u.image,
                   u.age, u.gender, u.username, u.address, u.description,
                   m.membership_status, m.member_id
            FROM users u
            LEFT JOIN members m ON u.user_id = m.user_id";
} else {
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.user_type, u.image,
                   u.age, u.gender, u.username, u.address, u.description, u.created_at, u.birthdate,
                   u.height, u.weight, u.weight_goal, u.medical_condition, u.medical_conditions,
                   m.membership_status, m.member_id
            FROM users u
            LEFT JOIN members m ON u.user_id = m.user_id";
}

// Add filters to query
$where = [];
$params = [];
$types = '';

if ($filter_user_type !== 'all') {
    $where[] = "u.user_type = ?";
    $params[] = $filter_user_type;
    $types .= 's';
}

if ($filter_membership !== 'all') {
    $where[] = "m.membership_status = ?";
    $params[] = $filter_membership;
    $types .= 's';
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get data for charts
$userTypeQuery = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
$userTypeResult = $conn->query($userTypeQuery);
$userTypeData = [];
while ($row = $userTypeResult->fetch_assoc()) {
    $userTypeData[$row['user_type']] = $row['count'];
}

$membershipStatusQuery = "SELECT membership_status, COUNT(*) as count FROM members GROUP BY membership_status";
$membershipStatusResult = $conn->query($membershipStatusQuery);
$membershipStatusData = [];
while ($row = $membershipStatusResult->fetch_assoc()) {
    $membershipStatusData[$row['membership_status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users | FlexiFit Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FFA000;
            --secondary: #212121;
            --dark: #000000;
            --light: #f8f9fa;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #fd7e14;
            --info: #17a2b8;
            --text-light: #ffffff;
            --text-dark: #121212;
            --bg-dark: #111111;
            --bg-light: #1e1e1e;
            --border-color: #333333;
            --card-shadow: 0 4px 8px rgba(255, 193, 7, 0.1);
        }


        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            margin: 0;
            padding: 0;
        }


        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }


        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--primary);
        }


        .page-title {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
            text-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
        }


        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }


        .chart-card {
            background: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            border: 1px solid var(--primary);
            height: 300px;
        }


        .chart-title {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary);
        }


        .btn {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            color: var(--text-dark);
            background-color: var(--primary);
            border: none;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }


        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }


        .btn i {
            font-size: 14px;
        }


        .view-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }


        .view-toggle-btn {
            padding: 8px 15px;
            border-radius: 5px;
            background-color: var(--secondary);
            color: var(--text-light);
            border: 1px solid var(--primary);
            cursor: pointer;
            transition: all 0.3s;
        }


        .view-toggle-btn.active {
            background-color: var(--primary);
            color: var(--text-dark);
        }


        .view-toggle-btn:hover {
            background-color: var(--primary-dark);
            color: var(--text-dark);
        }


        /* Card View Styles */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }


        .user-card {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            text-align: center;
            color: var(--text-light);
            border: 1px solid var(--primary);
            transition: all 0.3s;
        }


        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }


        .user-card img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid var(--primary);
        }


        .user-info {
            text-align: left;
            font-size: 14px;
        }


        .user-info p {
            margin: 8px 0;
            padding-bottom: 8px;
            border-bottom: 1px dashed var(--border-color);
        }


        .user-info strong {
            color: var(--primary);
        }


        .see-more {
            background-color: var(--primary);
            padding: 8px 15px;
            border-radius: 5px;
            color: var(--text-dark);
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            width: 100%;
            text-align: center;
        }


        .see-more:hover {
            background-color: var(--primary-dark);
        }


        .hidden {
            display: none;
        }


        /* Table View Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid var(--primary);
            margin-bottom: 30px;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }


        th {
            background-color: var(--primary);
            color: var(--text-dark);
            padding: 12px 15px;
            text-align: left;
            position: sticky;
            top: 0;
            font-weight: 700;
        }


        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-light);
        }


        tr {
            background-color: var(--bg-light);
            transition: all 0.2s ease;
        }


        tr:hover {
            background-color: var(--secondary);
        }


        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }


        .status-admin {
            background-color: var(--danger);
            color: var(--text-light);
        }


        .status-member {
            background-color: var(--success);
            color: var(--text-light);
        }


        .status-non-member {
            background-color: var(--warning);
            color: var(--text-dark);
        }


        .action-btn {
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }


        .edit-btn {
            background-color: var(--info);
            color: var(--text-light);
        }


        .edit-btn:hover {
            background-color: #138496;
        }


        .profile-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }


        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
           
            .grid-container {
                grid-template-columns: 1fr;
            }
           
            .view-toggle {
                flex-direction: column;
            }
           
            .view-toggle-btn {
                width: 100%;
            }
           
            table {
                font-size: 12px;
            }
           
            th, td {
                padding: 8px 10px;
            }
        }


        .chart-card {
    /* Add these properties to your existing .chart-card class */
    position: relative;
    min-height: 350px; /* Increased from 300px to give more vertical space */
    padding: 25px; /* Increased padding */
}


/* Add this new class for the chart container */
.chart-container {
    position: relative;
    height: calc(100% - 40px);
    width: 100%;
}


.view-toggle-btn.active {
            background-color: var(--primary) !important;
            color: var(--text-dark) !important;
        }


        /* Ensure proper display of views */
        .view-content {
            display: none;
        }
       
        .view-content.active {
            display: block;
        }
       
        .grid-container {
            display: none;
        }
       
        .grid-container.active {
            display: grid;
        }
        .view-container {
            display: none;
        }
       
        .view-container.active {
            display: block;
        }
       
        .grid-container {
            display: none;
        }
       
        .grid-container.active {
            display: grid;
        }
       
        .table-container {
            display: none;
        }
       
        .table-container.active {
            display: block;
        }
        .filter-section {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--primary);
        }
       
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
       
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }
       
        .filter-label {
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--primary);
        }
       
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid var(--primary);
            background-color: var(--bg-dark);
            color: var(--text-light);
        }
       
        .apply-filters {
            background-color: var(--primary);
            color: var(--text-dark);
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            align-self: flex-end;
        }
       
        .apply-filters:hover {
            background-color: var(--primary-dark);
        }
       
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
       
        .modal-content {
            background-color: var(--bg-light);
            margin: 10% auto;
            padding: 20px;
            border: 1px solid var(--primary);
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
       
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--primary);
        }
       
        .modal-title {
            color: var(--primary);
            margin: 0;
        }
       
        .close-modal {
            color: var(--primary);
            font-size: 24px;
            cursor: pointer;
        }
       
        .modal-body {
            margin-bottom: 20px;
        }
       
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-users"></i> User Management</h1>
        <a href="index.php" class="btn"><i class="fas fa-home"></i> Dashboard</a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
   
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Charts Section -->
    <div class="chart-grid">
        <div class="chart-card">
            <h3 class="chart-title">User Type Distribution</h3>
            <canvas id="userTypeChart"></canvas>
        </div>
        <div class="chart-card">
            <h3 class="chart-title">Membership Status</h3>
            <canvas id="membershipStatusChart"></canvas>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="get" action="view-users.php">
            <input type="hidden" name="view" value="<?= $viewType ?>">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">User Type</label>
                    <select name="filter_user_type" class="filter-select">
                        <option value="all" <?= $filter_user_type === 'all' ? 'selected' : '' ?>>All User Types</option>
                        <option value="non-member" <?= $filter_user_type === 'non-member' ? 'selected' : '' ?>>Non-member</option>
                        <option value="member" <?= $filter_user_type === 'member' ? 'selected' : '' ?>>Member</option>
                        <option value="admin" <?= $filter_user_type === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="trainer" <?= $filter_user_type === 'trainer' ? 'selected' : '' ?>>Trainer</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Membership Status</label>
                    <select name="filter_membership" class="filter-select">
                        <option value="all" <?= $filter_membership === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $filter_membership === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="expired" <?= $filter_membership === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="pending" <?= $filter_membership === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="apply-filters">Apply Filters</button>
        </form>
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
        <a href="?view=card&filter_user_type=<?= $filter_user_type ?>&filter_membership=<?= $filter_membership ?>" class="view-toggle-btn <?= $viewType === 'card' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Card View
        </a>
        <a href="?view=table&filter_user_type=<?= $filter_user_type ?>&filter_membership=<?= $filter_membership ?>" class="view-toggle-btn <?= $viewType === 'table' ? 'active' : '' ?>">
            <i class="fas fa-table"></i> Table View
        </a>
    </div>

    <!-- Card View -->
    <div id="cardView" class="grid-container <?= $viewType === 'card' ? 'active' : '' ?>">
        <?php if (!empty($users)) : ?>
            <?php foreach ($users as $user) : ?>
                <div class="user-card">
                    <?php
                    $imageFileName = !empty($user['image']) ? htmlspecialchars($user['image']) : null;
                    $imageFolder = '../images/';
                    $imagePath = ($imageFileName && file_exists($imageFolder . $imageFileName))
                        ? $imageFolder . $imageFileName
                        : $imageFolder . 'default.png';
                    ?>
                    <img src="<?= $imagePath ?>" alt="Profile Image">

                    <div class="user-info">
                        <p><strong>ID:</strong> <?= htmlspecialchars($user['user_id']) ?></p>
                        <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])) ?></p>
                        <p><strong>Type:</strong>
                            <span class="status-badge status-<?= str_replace(' ', '-', strtolower(htmlspecialchars($user['user_type']))) ?>">
                                <?= htmlspecialchars($user['user_type']) ?>
                            </span>
                            <button class="edit-type-btn" onclick="openEditModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['user_type']) ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($user['gender']) ?></p>
                        <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>

                        <?php if (isset($user['member_id'])) : ?>
                            <p><strong>Membership:</strong>
                                <span class="status-badge status-<?= htmlspecialchars($user['membership_status']) ?>">
                                    <?= htmlspecialchars($user['membership_status']) ?>
                                </span>
                                <?php if ($user['membership_status'] === 'pending') : ?>
                                    <button class="edit-membership-btn" onclick="openMembershipModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['membership_status']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <!-- Full details only shown in card view -->
                        <?php if ($viewType === 'card') : ?>
                            <div class="<?= $viewType === 'table' ? 'hidden' : '' ?>">
                                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
                                <p><strong>Joined:</strong> <?= htmlspecialchars($user['created_at'] ?? 'N/A') ?></p>
                                <p><strong>Birthdate:</strong> <?= htmlspecialchars($user['birthdate'] ?? 'N/A') ?></p>
                                <p><strong>Height:</strong> <?= htmlspecialchars($user['height'] ?? 'N/A') ?> cm</p>
                                <p><strong>Weight:</strong> <?= htmlspecialchars($user['weight'] ?? 'N/A') ?> kg</p>
                                <p><strong>Weight Goal:</strong> <?= htmlspecialchars($user['weight_goal'] ?? 'N/A') ?> kg</p>
                                <p><strong>Medical Condition:</strong> <?= htmlspecialchars($user['medical_condition'] ?? 'N/A') ?></p>
                                <p><strong>Medical Conditions:</strong> <?= htmlspecialchars($user['medical_conditions'] ?? 'N/A') ?></p>
                                <p><strong>Description:</strong> <?= htmlspecialchars($user['description'] ?? 'N/A') ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Toggle Button (only in card view) -->
                        <?php if ($viewType === 'card') : ?>
                            <button class="see-more" onclick="toggleDetails(this)">
                                <i class="fas fa-chevron-down"></i> More Details
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p style="color: var(--danger); text-align: center; grid-column: 1/-1;">
                <i class="fas fa-exclamation-circle"></i> No users found matching your criteria.
            </p>
        <?php endif; ?>
    </div>

    <!-- Table View -->
    <div id="tableView" class="table-container <?= $viewType === 'table' ? 'active' : '' ?>">
        <?php if (!empty($users)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Membership</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td>
                                <?php
                                $imageFileName = !empty($user['image']) ? htmlspecialchars($user['image']) : null;
                                $imageFolder = '../images/';
                                $imagePath = ($imageFileName && file_exists($imageFolder . $imageFileName))
                                    ? $imageFolder . $imageFileName
                                    : $imageFolder . 'default.png';
                                ?>
                                <img src="<?= $imagePath ?>" class="profile-img" alt="Profile">
                            </td>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])) ?></td>
                            <td><?= htmlspecialchars($user['gender']) ?></td>
                            <td><?= htmlspecialchars($user['age']) ?></td>
                            <td>
                                <span class="status-badge status-<?= str_replace(' ', '-', strtolower(htmlspecialchars($user['user_type']))) ?>">
                                    <?= htmlspecialchars($user['user_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><?= htmlspecialchars($user['address']) ?></td>
                            <td>
                                <?php if (isset($user['membership_status'])) : ?>
                                    <span class="status-badge status-<?= htmlspecialchars($user['membership_status']) ?>">
                                        <?= htmlspecialchars($user['membership_status']) ?>
                                    </span>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-btn edit-btn" onclick="openEditModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['user_type']) ?>')" title="Edit User Type">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (isset($user['member_id']) && $user['membership_status'] === 'pending') : ?>
                                    <button class="action-btn edit-btn" onclick="openMembershipModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['membership_status']) ?>')" title="Update Membership">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="color: var(--danger); text-align: center; padding: 20px;">
                <i class="fas fa-exclamation-circle"></i> No users found matching your criteria.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit User Type Modal -->
<div id="editUserTypeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User Type</h3>
            <span class="close-modal" onclick="closeModal('editUserTypeModal')">&times;</span>
        </div>
        <form method="post" id="userTypeForm">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="update_user_type" value="1">
                <div class="form-group">
                    <label class="form-label">Select New User Type:</label>
                    <select name="user_type" id="modalUserType" class="form-control">
                        <option value="non-member">Non-member</option>
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                        <option value="trainer">Trainer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Change:</label>
                    <textarea name="change_reason" id="changeReason" class="form-control" rows="5" style="width: 100%;" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserTypeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Membership Status Modal -->
<div id="editMembershipModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Update Membership Status</h3>
            <span class="close-modal" onclick="closeModal('editMembershipModal')">&times;</span>
        </div>
        <form method="post" id="membershipForm">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modalMembershipUserId">
                <input type="hidden" name="update_membership_status" value="1">
                <div class="form-group">
                    <label class="form-label">Select New Status:</label>
                    <select name="membership_status" id="modalMembershipStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Change:</label>
                    <textarea name="change_reason" id="membershipChangeReason" class="form-control" rows="5" style="width: 100%;" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editMembershipModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // User Type Distribution Chart
    const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
    const userTypeChart = new Chart(userTypeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Admin', 'Member', 'Non-Member', 'Trainer'],
            datasets: [{
                data: [
                    <?php echo $userTypeData['admin'] ?? 0; ?>,
                    <?php echo $userTypeData['member'] ?? 0; ?>,
                    <?php echo $userTypeData['non-member'] ?? 0; ?>,
                    <?php echo $userTypeData['trainer'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 110, 253, 0.8)'
                ],
                borderColor: [
                    'rgba(220, 53, 69, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(13, 110, 253, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#fff',
                        font: {
                            size: 12
                        },
                        padding: 15,
                        boxWidth: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            label += context.raw + ' (' + percentage + '%)';
                            return label;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    left: 20,
                    right: 20,
                    top: 20,
                    bottom: 20
                }
            },
            cutout: '60%'
        }
    });

    // Membership Status Chart
    const membershipStatusCtx = document.getElementById('membershipStatusChart').getContext('2d');
    const membershipStatusChart = new Chart(membershipStatusCtx, {
        type: 'bar',
        data: {
            labels: ['Active', 'Expired', 'Pending'],
            datasets: [{
                label: 'Members',
                data: [
                    <?php echo $membershipStatusData['active'] ?? 0; ?>,
                    <?php echo $membershipStatusData['expired'] ?? 0; ?>,
                    <?php echo $membershipStatusData['pending'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff',
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Modal functions
    function openEditModal(userId, currentType) {
        document.getElementById('modalUserId').value = userId;
        document.getElementById('modalUserType').value = currentType;
        document.getElementById('editUserTypeModal').style.display = 'block';
    }

    function openMembershipModal(userId, currentStatus) {
        document.getElementById('modalMembershipUserId').value = userId;
        document.getElementById('modalMembershipStatus').value = currentStatus;
        document.getElementById('editMembershipModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    }

    // Toggle details in card view
    function toggleDetails(button) {
        const moreInfo = button.previousElementSibling;
        const icon = button.querySelector('i');
       
        if (moreInfo.classList.contains('hidden')) {
            moreInfo.classList.remove('hidden');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            button.innerHTML = '<i class="fas fa-chevron-up"></i> Less Details';
        } else {
            moreInfo.classList.add('hidden');
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            button.innerHTML = '<i class="fas fa-chevron-down"></i> More Details';
        }
    }
</script>

</body>
</html>

<?php $conn->close(); ?>
<?php ob_end_flush(); // At the end of file ?>