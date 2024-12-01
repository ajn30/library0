<?php
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info to check if they are an admin
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] != 'admin') {
    header("Location: user_dashboard.php");
    exit();
}

// Check if the borrow request ID is set and valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $borrow_request_id = $_GET['id'];

    // Update the status of the borrow request to 'rejected'
    $sql = "UPDATE borrow_requests SET status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$borrow_request_id]);

    // Redirect back to the dashboard or pending requests page
    header("Location: admin_dashboard.php?message=Request rejected.");
    exit();
} else {
    // Invalid request ID
    header("Location: admin_dashboard.php?message=Invalid request.");
    exit();
}
?>