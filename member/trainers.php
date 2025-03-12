<?php
session_start(); // Start session at the very beginning


if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();

}


include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    // No user is logged in, redirect to main index.php
    header("Location: ../index.php");
    exit();
} elseif ($_SESSION['user_type'] == 'non-member') {
    // Non-members cannot access members or admin areas
    header("Location: ../index.php");
    exit();
} elseif ($_SESSION['user_type'] == 'admin') {
    // Admin can access admin area
    header("Location: ../admin/index.php");
    exit();
}


// Ensure trainer_id is set and valid
if (!isset($_GET['trainer_id']) || !is_numeric($_GET['trainer_id'])) {
    echo "<script>alert('Invalid trainer ID!'); window.location.href='view-trainers.php';</script>";
    exit();
}


$trainer_id = intval($_GET['trainer_id']);


// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $comments = htmlspecialchars($_POST['comments'], ENT_QUOTES, 'UTF-8');
    $user_id = $_SESSION['user_id'];


    // Insert into trainer_reviews
    $review_query = $conn->prepare("INSERT INTO trainer_reviews (user_id, trainer_id, rating, comments) VALUES (?, ?, ?, ?)");
    $review_query->bind_param("iiis", $user_id, $trainer_id, $rating, $comments);


    if ($review_query->execute()) {
        // Redirect back to the same page to show updated reviews
        header("Location: view-trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        error_log("Error executing review submission: " . $review_query->error);
        echo "<script>alert('Error submitting review. Please try again later.');</script>";
    }
}


// Handle review update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
    $review_id = intval($_POST['review_id']);
    $rating = $_POST['rating'];
    $comments = htmlspecialchars($_POST['comments'], ENT_QUOTES, 'UTF-8');
    $user_id = $_SESSION['user_id'];


    // Update the review
    $update_query = $conn->prepare("UPDATE trainer_reviews SET rating = ?, comments = ? WHERE review_id = ? AND user_id = ?");
    $update_query->bind_param("isii", $rating, $comments, $review_id, $user_id);


    if ($update_query->execute()) {
        // Redirect back to the same page to show updated reviews
        header("Location: view-trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        error_log("Error updating review: " . $update_query->error);
        echo "<script>alert('Error updating review. Please try again later.');</script>";
    }
}


// Handle review deletion
if (isset($_GET['delete_review'])) {
    $review_id = intval($_GET['delete_review']);
    $delete_query = $conn->prepare("DELETE FROM trainer_reviews WHERE review_id = ? AND user_id = ?");
    $delete_query->bind_param("ii", $review_id, $_SESSION['user_id']);


    if ($delete_query->execute()) {
        header("Location: view-trainers.php?trainer_id=" . urlencode($trainer_id));
        exit();
    } else {
        echo "<script>alert('Error deleting review.');</script>";
    }
}


// Fetch trainer details
$query = $conn->prepare("SELECT trainer_id, first_name, last_name, email, age, gender, image, status FROM trainers WHERE trainer_id = ?");
$query->bind_param("i", $trainer_id);
$query->execute();
$result = $query->get_result();


if ($result->num_rows > 0) {
    $trainer = $result->fetch_assoc();
} else {
    echo "<script>alert('Trainer not found!'); window.location.href='view-trainers.php';</script>";
    exit();
}


// Fetch all trainer reviews (removed LIMIT 5)
$review_query = $conn->prepare("SELECT r.review_id, r.user_id, r.rating, r.comments, r.review_date, u.first_name, u.last_name
                                FROM trainer_reviews r
                                JOIN users u ON r.user_id = u.user_id
                                WHERE r.trainer_id = ?
                                ORDER BY r.review_date DESC"); // Removed LIMIT 5 to show all reviews
$review_query->bind_param("i", $trainer_id);
$review_query->execute();
$reviews_result = $review_query->get_result();


// Fetch review data for editing
$edit_review = null;
if (isset($_GET['edit_review'])) {
    $review_id = intval($_GET['edit_review']);
    $edit_query = $conn->prepare("SELECT review_id, rating, comments FROM trainer_reviews WHERE review_id = ? AND user_id = ?");
    $edit_query->bind_param("ii", $review_id, $_SESSION['user_id']);
    $edit_query->execute();
    $edit_result = $edit_query->get_result();


    if ($edit_result->num_rows > 0) {
        $edit_review = $edit_result->fetch_assoc();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
       
        .profile-card {
            max-width: 500px;
            margin: auto;
            text-align: center;
            border-radius: 10px;
            overflow: hidden;
        }
        .profile-card img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        .stars {
            color: gold;
            font-size: 1.2rem;
        }
        .review-card {
            border-radius: 8px;
            background-color: #f8f9fa;
            padding: 15px;
        }
        .review-card h5 {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .submit-review {
            background: #ffc107;
            border: none;
            font-weight: bold;
        }
        .submit-review:hover {
            background: #ff9800;
        }
        .action-buttons {
            margin-top: 10px;
        }
        .action-buttons a {
            margin-right: 10px;
        }
        .edit-form {
            margin-top: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="profile-card card shadow">
            <img src="../images/<?php echo $trainer['image'] ?? 'default.jpg'; ?>" alt="Trainer Image">
            <div class="card-body">
                <h3 class="text-warning"><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h3>
                <p>Email: <strong><?php echo htmlspecialchars($trainer['email']); ?></strong></p>
                <p>Age: <strong><?php echo htmlspecialchars($trainer['age']); ?></strong></p>
                <p>Gender: <strong><?php echo htmlspecialchars($trainer['gender']); ?></strong></p>
                <p>Status: <strong><?php echo htmlspecialchars($trainer['status']); ?></strong></p>
            </div>
        </div>
    </div>


    <div class="container mt-4">
        <h3 class="text-warning">All Reviews</h3> <!-- Changed from "Latest Reviews" to "All Reviews" -->
        <?php if ($reviews_result->num_rows > 0): ?>
            <?php while ($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-card shadow-sm mt-3">
                    <h5><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h5>
                    <p class="stars"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($review['comments'])); ?></p>
                    <small class="text-muted"><?php echo $review['review_date']; ?></small>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                        <div class="action-buttons">
                            <a href="view-trainers.php?trainer_id=<?php echo $trainer_id; ?>&edit_review=<?php echo $review['review_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="view-trainers.php?trainer_id=<?php echo $trainer_id; ?>&delete_review=<?php echo $review['review_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                        </div>
                        <?php if (isset($_GET['edit_review']) && $_GET['edit_review'] == $review['review_id']): ?>
                            <div class="edit-form mt-3">
                                <form method="POST" class="p-3 bg-white shadow-sm rounded">
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <label class="fw-bold">Rating:</label>
                                    <select name="rating" required class="form-control">
                                        <option value="1" <?php echo $review['rating'] == 1 ? 'selected' : ''; ?>>★☆☆☆☆ (1)</option>
                                        <option value="2" <?php echo $review['rating'] == 2 ? 'selected' : ''; ?>>★★☆☆☆ (2)</option>
                                        <option value="3" <?php echo $review['rating'] == 3 ? 'selected' : ''; ?>>★★★☆☆ (3)</option>
                                        <option value="4" <?php echo $review['rating'] == 4 ? 'selected' : ''; ?>>★★★★☆ (4)</option>
                                        <option value="5" <?php echo $review['rating'] == 5 ? 'selected' : ''; ?>>★★★★★ (5)</option>
                                    </select>
                                    <label class="fw-bold mt-2">Comments:</label>
                                    <textarea name="comments" rows="4" required class="form-control"><?php echo htmlspecialchars($review['comments']); ?></textarea>
                                    <button type="submit" name="update_review" class="btn submit-review mt-3 w-100">Update Review</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to leave a review!</p>
        <?php endif; ?>
    </div>


    <div class="container mt-4">
        <h3 class="text-warning">Submit Your Review</h3>
        <form method="POST" class="p-3 bg-white shadow-sm rounded">
            <label class="fw-bold">Rating:</label>
            <select name="rating" required class="form-control">
                <option value="1">★☆☆☆☆ (1)</option>
                <option value="2">★★☆☆☆ (2)</option>
                <option value="3">★★★☆☆ (3)</option>
                <option value="4">★★★★☆ (4)</option>
                <option value="5">★★★★★ (5)</option>
            </select>
            <label class="fw-bold mt-2">Comments:</label>
            <textarea name="comments" rows="4" required class="form-control"></textarea>
            <button type="submit" name="submit_review" class="btn submit-review mt-3 w-100">Submit Review</button>
        </form>
    </div>


</body>
</html>


<?php
$query->close();
$review_query->close();
if (isset($edit_query)) $edit_query->close();
$conn->close();
?>


