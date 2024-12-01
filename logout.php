<?php
session_start(); // Start the session

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect the user to the login page or home page
    header("Location: index.php"); // Or you can redirect to login.php
    exit();
} else {
    // If the user isn't logged in, redirect them to the login page
    header("Location: login.php");
    exit();
}
?>