<?php
include 'db.php';
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info from the database to check if they are an admin
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If the user is not an admin, redirect them
if ($user['role'] != 'admin') {
    header("Location: user_dashboard.php");
    exit();
}

// Check if the 'id' is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id_to_delete = $_GET['id'];

// Delete the user from the database
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id_to_delete]);

// Redirect back to manage users page
header("Location: manage_users.php");
exit();
?>