<?php
ob_start(); // Turn on output buffering
session_start();
include '../includes/header.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication checks
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../index.php");
//     exit();
// } elseif ($_SESSION['user_type'] == 'non-member') {
//     header("Location: ../index.php");
//     exit();
// } elseif ($_SESSION['user_type'] == 'admin') {
//     header("Location: ../admin/index.php");
//     exit();
// }

// Validate trainer_id
if (!isset($_GET['trainer_id']) || !is_numeric($_GET['trainer_id'])) {
    echo "<script>alert('Invalid trainer ID!'); window.location.href='view-trainers.php';</script>";
    exit();
}

$trainer_id = intval($_GET['trainer_id']);
$user_id = $_SESSION['user_id'];

// Check if member has completed sessions with this trainer
$can_review = false;
$review_check = $conn->prepare("SELECT COUNT(*) FROM schedules s 
                              JOIN schedule_trainer st ON s.schedule_id = st.schedule_id
                              WHERE s.member_id = ? AND st.trainer_id = ? AND s.status = 'completed'");
$review_check->bind_param("ii", $user_id, $trainer_id);
$review_check->execute();
$review_check->bind_result($completed_sessions);
$review_check->fetch();
$review_check->close();

$can_review = ($completed_sessions > 0);

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review']) && $can_review) {
    $rating = $_POST['rating'];
    $comments = htmlspecialchars($_POST['comments'], ENT_QUOTES, 'UTF-8');
    
    $review_query = $conn->prepare("INSERT INTO trainer_reviews (user_id, trainer_id, rating, comments) VALUES (?, ?, ?, ?)");
    $review_query->bind_param("iiis", $user_id, $trainer_id, $rating, $comments);
    
    if ($review_query->execute()) {
        header("Location: trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        error_log("Error executing review submission: " . $review_query->error);
        echo "<script>alert('Error submitting review. Please try again later.');</script>";
    }
    $review_query->close();
}

// Handle review update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
    $review_id = intval($_POST['review_id']);
    $rating = $_POST['rating'];
    $comments = htmlspecialchars($_POST['comments'], ENT_QUOTES, 'UTF-8');
    
    $update_query = $conn->prepare("UPDATE trainer_reviews SET rating = ?, comments = ? WHERE review_id = ? AND user_id = ?");
    $update_query->bind_param("isii", $rating, $comments, $review_id, $user_id);
    
    if ($update_query->execute()) {
        header("Location: trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        error_log("Error updating review: " . $update_query->error);
        echo "<script>alert('Error updating review. Please try again later.');</script>";
    }
    $update_query->close();
}

// Handle review deletion
if (isset($_GET['delete_review'])) {
    $review_id = intval($_GET['delete_review']);
    $delete_query = $conn->prepare("DELETE FROM trainer_reviews WHERE review_id = ? AND user_id = ?");
    $delete_query->bind_param("ii", $review_id, $user_id);
    
    if ($delete_query->execute()) {
        header("Location: trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        echo "<script>alert('Error deleting review.');</script>";
    }
    $delete_query->close();
}

// Fetch trainer details with specialties
$trainer_query = $conn->prepare("SELECT t.*, GROUP_CONCAT(s.name SEPARATOR ', ') AS specialties 
                                FROM trainers t
                                LEFT JOIN trainer_specialty ts ON t.trainer_id = ts.trainer_id
                                LEFT JOIN specialty s ON ts.specialty_id = s.specialty_id
                                WHERE t.trainer_id = ?
                                GROUP BY t.trainer_id");
$trainer_query->bind_param("i", $trainer_id);
$trainer_query->execute();
$trainer_result = $trainer_query->get_result();

if ($trainer_result->num_rows == 0) {
    echo "<script>alert('Trainer not found!'); window.location.href='view-trainers.php';</script>";
    exit();
}

$trainer = $trainer_result->fetch_assoc();
$trainer_query->close();

// Fetch trainer reviews
$review_query = $conn->prepare("SELECT r.review_id, r.user_id, r.rating, r.comments, r.review_date, u.first_name, u.last_name
                               FROM trainer_reviews r
                               JOIN users u ON r.user_id = u.user_id
                               WHERE r.trainer_id = ?
                               ORDER BY r.review_date DESC");
$review_query->bind_param("i", $trainer_id);
$review_query->execute();
$reviews_result = $review_query->get_result();

// Fetch trainer's schedule
$schedule_query = $conn->prepare("SELECT s.schedule_id, s.date, s.start_time, s.end_time, 
                                 e.name AS equipment_name, ei.identifier,
                                 m.first_name AS member_first, m.last_name AS member_last,
                                 s.status
                                 FROM schedules s
                                 JOIN schedule_trainer st ON s.schedule_id = st.schedule_id
                                 JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
                                 JOIN equipment e ON ei.equipment_id = e.equipment_id
                                 JOIN members mb ON s.member_id = mb.member_id
                                 JOIN users m ON mb.user_id = m.user_id
                                 WHERE st.trainer_id = ?
                                 ORDER BY s.date DESC, s.start_time DESC");
$schedule_query->bind_param("i", $trainer_id);
$schedule_query->execute();
$schedule_result = $schedule_query->get_result();

// Check if current user has any reviews for this trainer
$user_review_query = $conn->prepare("SELECT review_id FROM trainer_reviews WHERE user_id = ? AND trainer_id = ?");
$user_review_query->bind_param("ii", $user_id, $trainer_id);
$user_review_query->execute();
$has_review = ($user_review_query->get_result()->num_rows > 0);
$user_review_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?> | Trainer Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #121212;
            --accent: #2c2c2c;
            --card-bg: #1e1e1e;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --pending: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header-divider {
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            border: none;
            margin: 0 auto 30px;
        }
        
        .profile-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .profile-section {
                grid-template-columns: 1fr;
            }
        }
        
        .trainer-image-container {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            height: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .trainer-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .trainer-info {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .trainer-name {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px;
            color: var(--primary);
        }
        
        .trainer-title {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .trainer-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .meta-icon {
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .specialties-container {
            margin: 25px 0;
        }
        
        .specialty-tag {
            display: inline-block;
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .availability-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .available {
            background-color: var(--success);
            color: white;
        }
        
        .unavailable {
            background-color: var(--danger);
            color: white;
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.8rem;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .schedule-table th {
            background: var(--primary);
            color: var(--secondary);
            padding: 15px;
            text-align: left;
        }
        
        .schedule-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--accent);
        }
        
        .schedule-table tr:last-child td {
            border-bottom: none;
        }
        
        .schedule-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: var(--pending);
            color: white;
        }
        
        .status-completed {
            background-color: var(--success);
            color: white;
        }
        
        .status-upcoming {
            background-color: var(--warning);
            color: var(--secondary);
        }
        
        .status-cancelled {
            background-color: var(--danger);
            color: white;
        }
        
        .reviews-container {
            display: grid;
            gap: 20px;
        }
        
        .review-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-author {
            font-weight: 600;
            color: var(--primary);
        }
        
        .review-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .review-rating {
            color: var(--primary);
            margin: 10px 0;
            font-size: 1.1rem;
        }
        
        .review-content {
            line-height: 1.6;
        }
        
        .review-actions {
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            margin-right: 10px;
        }
        
        .edit-btn {
            background: var(--primary);
            color: var(--secondary);
        }
        
        .edit-btn:hover {
            background: #e6c200;
        }
        
        .delete-btn {
            background: var(--danger);
            color: white;
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .review-form {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: var(--secondary);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .submit-btn {
            background: var(--primary);
            color: var(--secondary);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #e6c200;
        }
        
        .submit-btn:disabled {
            background: #555;
            color: #999;
            cursor: not-allowed;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 10px;
            color: var(--text-secondary);
        }
        
        .no-data i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .edit-review-form {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .trainer-name {
                font-size: 1.8rem;
            }
            
            .schedule-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h1>Trainer Profile</h1>
        <div class="header-divider"></div>
    </div>
    
    <div class="profile-section">
        <div class="trainer-image-container">
            <img src="<?= !empty($trainer['image']) ? '../admin/uploads/' . $trainer['image'] : '../admin/uploads/default-trainer.jpg' ?>" 
                 alt="<?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?>" 
                 class="trainer-image">
        </div>
        
        <div class="trainer-info">
            <h1 class="trainer-name"><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></h1>
            <div class="trainer-title">Professional Trainer</div>
            
            <div class="trainer-meta">
                <div class="meta-item">
                    <i class="fas fa-envelope meta-icon"></i>
                    <span><?= htmlspecialchars($trainer['email']) ?></span>
                </div>
                
                <div class="meta-item">
                    <i class="fas fa-birthday-cake meta-icon"></i>
                    <span>Age: <?= htmlspecialchars($trainer['age']) ?></span>
                </div>
                
                <div class="meta-item">
                    <i class="fas fa-venus-mars meta-icon"></i>
                    <span><?= htmlspecialchars(ucfirst($trainer['gender'])) ?></span>
                </div>
                
                <div class="meta-item">
                    <i class="fas fa-calendar-check meta-icon"></i>
                    <span class="availability-status <?= strtolower($trainer['availability_status']) ?>">
                        <?= htmlspecialchars($trainer['availability_status']) ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($trainer['specialties'])): ?>
                <div class="specialties-container">
                    <h3>Specialties</h3>
                    <?php 
                    $specialties = explode(', ', $trainer['specialties']);
                    foreach ($specialties as $specialty): 
                    ?>
                        <span class="specialty-tag"><?= htmlspecialchars($specialty) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($trainer['description'])): ?>
                <div class="trainer-bio">
                    <h3>About Me</h3>
                    <p><?= nl2br(htmlspecialchars($trainer['description'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <h2 class="section-title">Training Schedule</h2>
    
    <?php if ($schedule_result->num_rows > 0): ?>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Member</th>
                    <th>Equipment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($session = $schedule_result->fetch_assoc()): 
                    $current_date = date('Y-m-d');
                    $session_date = $session['date'];
                    $status_class = '';
                    
                    if ($session['status'] == 'cancelled') {
                        $status_class = 'status-cancelled';
                    } elseif ($session_date < $current_date) {
                        $status_class = 'status-completed';
                    } elseif ($session_date == $current_date) {
                        $status_class = 'status-upcoming';
                    } else {
                        $status_class = 'status-pending';
                    }
                ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($session['date'])) ?></td>
                        <td><?= date('g:i A', strtotime($session['start_time'])) ?> - <?= date('g:i A', strtotime($session['end_time'])) ?></td>
                        <td><?= htmlspecialchars($session['member_first'] . ' ' . $session['member_last']) ?></td>
                        <td><?= htmlspecialchars($session['equipment_name']) ?> (<?= htmlspecialchars($session['identifier']) ?>)</td>
                        <td><span class="status-badge <?= $status_class ?>"><?= ucfirst($session['status']) ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <i class="fas fa-calendar-times"></i>
            <h3>No Scheduled Sessions</h3>
            <p>This trainer doesn't have any scheduled sessions yet.</p>
        </div>
    <?php endif; ?>
    
    <h2 class="section-title">Client Reviews</h2>
    
    <div class="reviews-container">
        <?php if ($reviews_result->num_rows > 0): ?>
            <?php while ($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-author"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></div>
                        <div class="review-date"><?= date('M j, Y', strtotime($review['review_date'])) ?></div>
                    </div>
                    <div class="review-rating">
                        <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                    </div>
                    <div class="review-content">
                        <?= nl2br(htmlspecialchars($review['comments'])) ?>
                    </div>
                    
                    <?php if ($user_id == $review['user_id']): ?>
                        <div class="review-actions">
                            <button class="action-btn edit-btn" onclick="document.getElementById('edit-form-<?= $review['review_id'] ?>').style.display='block'">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="trainers.php?trainer_id=<?= $trainer_id ?>&delete_review=<?= $review['review_id'] ?>" 
                               class="action-btn delete-btn"
                               onclick="return confirm('Are you sure you want to delete this review?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                        
                        <div id="edit-form-<?= $review['review_id'] ?>" class="edit-review-form" style="display:none;">
                            <form method="POST">
                                <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Rating</label>
                                    <select name="rating" class="form-control" required>
                                        <option value="1" <?= $review['rating'] == 1 ? 'selected' : '' ?>>★☆☆☆☆ (1)</option>
                                        <option value="2" <?= $review['rating'] == 2 ? 'selected' : '' ?>>★★☆☆☆ (2)</option>
                                        <option value="3" <?= $review['rating'] == 3 ? 'selected' : '' ?>>★★★☆☆ (3)</option>
                                        <option value="4" <?= $review['rating'] == 4 ? 'selected' : '' ?>>★★★★☆ (4)</option>
                                        <option value="5" <?= $review['rating'] == 5 ? 'selected' : '' ?>>★★★★★ (5)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Comments</label>
                                    <textarea name="comments" class="form-control" rows="4" required><?= htmlspecialchars($review['comments']) ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_review" class="submit-btn">
                                    <i class="fas fa-save"></i> Update Review
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-comment-slash"></i>
                <h3>No Reviews Yet</h3>
                <p>This trainer hasn't received any reviews yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($can_review && !$has_review): ?>
        <h2 class="section-title">Leave a Review</h2>
        <div class="review-form">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-control" required>
                        <option value="">Select a rating</option>
                        <option value="1">★☆☆☆☆ (1) - Poor</option>
                        <option value="2">★★☆☆☆ (2) - Fair</option>
                        <option value="3">★★★☆☆ (3) - Good</option>
                        <option value="4">★★★★☆ (4) - Very Good</option>
                        <option value="5">★★★★★ (5) - Excellent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Your Review</label>
                    <textarea name="comments" class="form-control" rows="5" required 
                              placeholder="Share your experience with this trainer..."></textarea>
                </div>
                
                <button type="submit" name="submit_review" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </form>
        </div>
    <?php elseif (!$can_review): ?>
        <div class="no-data">
            <i class="fas fa-info-circle"></i>
            <h3>Review Not Available</h3>
            <p>You can only review trainers after completing a session with them.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Simple script to handle edit form toggling
document.addEventListener('DOMContentLoaded', function() {
    // Close edit forms when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.edit-review-form') && !e.target.closest('.edit-btn')) {
            document.querySelectorAll('.edit-review-form').forEach(form => {
                form.style.display = 'none';
            });
        }
    });
});
</script>

</body>
</html>

<?php
// Close database connections
if (isset($schedule_query)) $schedule_query->close();
if (isset($review_query)) $review_query->close();
$conn->close();
?>
<?php ob_end_flush(); // At the end of file ?>