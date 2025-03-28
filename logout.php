<?php
ob_start(); // Turn on output buffering
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();// neo
?>
<?php ob_end_flush(); // At the end of file ?>
