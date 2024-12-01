<?php
include 'db.php';

// Check if admin is logged in and has access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$request_id = $_GET['id'];
$sql = "UPDATE borrow_requests SET status = 'accepted' WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$request_id]);

header("Location: admin_dashboard.php");
exit();
?>