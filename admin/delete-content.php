<style>
    body {
        background-color: #000;
        color: #fff;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .container {
        background-color: #111;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0px 0px 10px yellow;
        width: 350px;
        text-align: center;
    }
    h2 {
        color: yellow;
    }
    input {
        width: 90%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid yellow;
        border-radius: 5px;
        background-color: #222;
        color: #fff;
    }
    button {
        background-color: yellow;
        color: black;
        padding: 10px;
        border: none;
        width: 100%;
        cursor: pointer;
        font-weight: bold;
        border-radius: 5px;
    }
    button:hover {
        background-color: #ffaa00;
    }
</style>


<div class="container">
    <h2>Delete Content</h2>
    <form action="delete-content.php" method="get">
        <input type="number" name="content_id" placeholder="Content ID" required>
        <button type="submit">Delete</button>
    </form>
</div>


<!-- delete-content.php -->
<?php


session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "flexifit_db";
$conn = new mysqli($host, $user, $password, $dbname);


if (isset($_GET['content_id'])) {
    $content_id = $_GET['content_id'];
   
    // Delete content from database
    $stmt = $conn->prepare("DELETE FROM content WHERE content_id = ?");
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $stmt->close();
   
    header("Location: content.php");
    exit();
}
?>
