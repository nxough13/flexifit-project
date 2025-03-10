<?php
session_start(); // Start session to access the logged-in user's details
include '../includes/header.php';


$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// neo
// Ensure content_id is set and valid
if (!isset($_GET['content_id']) || !is_numeric($_GET['content_id'])) {
    die("Invalid content ID!");
}


$content_id = intval($_GET['content_id']); // Convert to integer to avoid errors










// Ensure the user is logged in before submitting a review
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to submit a review.'); window.location.href='login.php';</script>";
    exit();
}


// Handle form submission for reviews
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];
    $user_id = $_SESSION['user_id']; // Assuming user is logged in and user_id is stored in session


    // Sanitize inputs to prevent XSS
    $comments = htmlspecialchars($comments, ENT_QUOTES, 'UTF-8');


    // Insert the review into the database
    $review_query = $conn->prepare("INSERT INTO reviews (user_id, content_id, rating, comments) VALUES (?, ?, ?, ?)");
    $review_query->bind_param("iiis", $user_id, $content_id, $rating, $comments);


    if ($review_query->execute()) {
        echo "<script>alert('Review submitted successfully!');</script>";
    } else {
        error_log("Error executing review submission: " . $review_query->error);
        echo "<script>alert('Error submitting review. Please try again later.');</script>";
    }
}


// Prepare and bind SQL query to fetch content details
$query = $conn->prepare("SELECT c.content_id, c.title, c.description, c.content_type, c.file_path, c.image, c.created_at, u.first_name, u.last_name
                         FROM content c
                         JOIN users u ON c.admin_id = u.user_id
                         WHERE c.content_id = ?");
$query->bind_param("i", $content_id); // Bind content_id as integer


// Execute query
$query->execute();


// Get result
$result = $query->get_result();


// Fetch content details
if ($result->num_rows > 0) {
    $content = $result->fetch_assoc();
} else {
    echo "Content not found!";
    exit();
}


// Fetch reviews for this content_id
$review_query = $conn->prepare("SELECT r.review_id, r.rating, r.comments, r.review_date, r.user_id, u.first_name, u.last_name
                               FROM reviews r
                               JOIN users u ON r.user_id = u.user_id
                               WHERE r.content_id = ?");
$review_query->bind_param("i", $content_id); // Bind content_id for the reviews query


// Execute the reviews query
$review_query->execute();
$reviews_result = $review_query->get_result();


// Handle review edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_review'])) {
    $review_id = $_POST['review_id'];
    $new_rating = $_POST['rating'];
    $new_comments = $_POST['comments'];


    // Sanitize inputs
    $new_comments = htmlspecialchars($new_comments, ENT_QUOTES, 'UTF-8');


    // Update the review in the database
    $edit_query = $conn->prepare("UPDATE reviews SET rating = ?, comments = ? WHERE review_id = ? AND user_id = ?");
    $edit_query->bind_param("issi", $new_rating, $new_comments, $review_id, $_SESSION['user_id']);


    if ($edit_query->execute()) {
        echo "<script>alert('Review updated successfully!');</script>";
    } else {
        error_log("Error updating review: " . $edit_query->error);
        echo "<script>alert('Error updating review. Please try again later.');</script>";
    }
}


// Handle review delete
if (isset($_GET['delete_review_id']) && isset($_SESSION['user_id'])) {
    $delete_review_id = $_GET['delete_review_id'];
    $user_id = $_SESSION['user_id'];


    // Prepare and execute delete query
    $delete_query = $conn->prepare("DELETE FROM reviews WHERE review_id = ? AND user_id = ?");
    $delete_query->bind_param("ii", $delete_review_id, $user_id);


    if ($delete_query->execute()) {
        echo "<script>alert('Review deleted successfully!'); window.location.href='view-content.php?content_id=$content_id';</script>";
        exit();
    } else {
        error_log("Error deleting review: " . $delete_query->error);
        echo "<script>alert('Error deleting review. Please try again later.');</script>";
    }
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: #fff;
            text-align: center;
        }


        /* Main Content Box */
        .content-container {
            max-width: 800px;
            width: 90%;
            margin: 50px auto;
            background: #e0e0e0;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 0px 15px rgba(255, 255, 0, 0.6);
            border-top: 6px solid yellow;
        }


        .content-header {
            font-size: 1.8em;
            font-weight: bold;
            background: #b3b3b3;
            padding: 10px;
            border-radius: 10px 10px 0 0;
            color: #000;
        }


        .content-details {
            display: flex;
            flex-direction: column;
            align-items: center;
        }


        .content-image {
            width: 100%;
            max-width: 300px;
            border-radius: 10px;
            margin-bottom: 15px;
        }


        .content-text {
            font-size: 1.1em;
            color: #000;
            max-width: 80%;
        }


        .content-author {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            font-size: 0.9em;
            font-weight: bold;
            color: #000;
        }


        .timestamp {
            font-size: 0.8em;
            color: #333;
        }


        /* Reviews Section */
        .reviews-container {
            max-width: 800px;
            width: 90%;
            margin: 30px auto;
        }


        .review-card {
            background-color: #fff;
            color: #000;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            text-align: left;
            display: flex;
            flex-direction: column;
        }


        .review-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }


        .btn-edit, .btn-delete {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }


        .btn-edit {
            background-color: #0d6efd;
            color: white;
        }


        .btn-edit:hover {
            background-color: #0056b3;
        }


        .btn-delete {
            background-color: #dc3545;
            color: white;
        }


        .btn-delete:hover {
            background-color: #b52b35;
        }


        .edit-form {
    background: #f0f0f0;
    padding: 15px;
    border-radius: 10px;
    margin-top: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}


.edit-form input, .edit-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}


.edit-form button {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}


.edit-form button:hover {
    background: #218838;
}


    </style>
</head>
<body>


    <h1 class="text-warning">FLEXIFIT GYM</h1>


    <div class="content-container">
        <div class="content-header">TRANSFORM YOUR ROUTINE</div>


        <div class="content-details">
            <img src="../images/Left-image.jpg" alt="Content Image" class="content-image">


            <p class="content-text">
                <?php echo nl2br(htmlspecialchars($content['description'])); ?>
            </p>


            <div class="content-author">
                <span><?php echo htmlspecialchars($content['first_name']); ?></span>
            </div>
            <div class="timestamp"><?php echo $content['created_at']; ?></div>
        </div>
    </div>


    <div class="reviews-container">
    <h3 class="text-light">REVIEWS:</h3>


    <?php while ($review = $reviews_result->fetch_assoc()): ?>
        <div class="review-card">
            <div class="review-content">
                <strong><?php echo $review['first_name']; ?></strong>  
                <span>Rating: <?php echo $review['rating']; ?> / 5</span>
                <p><?php echo nl2br(htmlspecialchars($review['comments'])); ?></p>
                <small><?php echo $review['review_date']; ?></small>
            </div>


            <?php if ($review['user_id'] == $_SESSION['user_id']): ?>
                <div class="review-buttons">
                    <!-- Edit Button -->
                    <form method="GET">
                        <input type="hidden" name="content_id" value="<?php echo $content_id; ?>">
                        <input type="hidden" name="edit_review_id" value="<?php echo $review['review_id']; ?>">
                        <button type="submit" class="btn-edit">✏️ Edit</button>
                    </form>


                    <form method="GET" action="view-content.php" onsubmit="return confirm('Are you sure you want to delete this review?')">
    <input type="hidden" name="content_id" value="<?php echo htmlspecialchars($content_id); ?>"> <!-- Ensure content_id is included -->
    <input type="hidden" name="delete_review_id" value="<?php echo $review['review_id']; ?>">
    <button type="submit" class="btn-delete">Delete</button>
</form>


                </div>
            <?php endif; ?>


            <!-- ✅ Edit Review Form (Styled) -->
            <?php if (isset($_GET['edit_review_id']) && $_GET['edit_review_id'] == $review['review_id']): ?>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                    <label for="rating">Rating (1-5):</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo $review['rating']; ?>" required>
                    <label for="comments">Comments:</label>
                    <textarea id="comments" name="comments" rows="4" required><?php echo htmlspecialchars($review['comments']); ?></textarea>
                    <button type="submit" name="edit_review">Update Review</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>


    <!-- ✅ Submit Review Form (Styled to Match Edit Review Form) -->
    <div class="review-card">
        <form method="POST" class="edit-form">
            <h4 class="text-dark">Submit Your Review:</h4>
            <input type="hidden" name="content_id" value="<?php echo $content_id; ?>">


            <label for="rating" class="text-dark">Rating (1-5):</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>


            <label for="comments" class="text-dark">Comments:</label>
            <textarea id="comments" name="comments" rows="4" required></textarea>


            <button type="submit" name="submit_review">✅ Submit Review</button>
        </form>
    </div>
</div>


</body>
</html>




<?php
$query->close();
$review_query->close();
$conn->close();
?>
