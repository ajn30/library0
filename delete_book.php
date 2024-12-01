<?php
session_start();
include 'db.php';

// Check if the user is logged in and is an admin
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
    header("Location: dashboard.php");
    exit();
}

// Check if the 'id' parameter is set
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    // Check if the book exists in the database
    $check_sql = "SELECT * FROM books WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$book_id]);
    $book = $check_stmt->fetch();

    if ($book) {
        // Delete related transactions first
        $delete_transactions_sql = "DELETE FROM transactions WHERE book_id = ?";
        $delete_transactions_stmt = $pdo->prepare($delete_transactions_sql);
        $delete_transactions_stmt->execute([$book_id]);

        // Now delete the book from the books table
        $delete_sql = "DELETE FROM books WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$book_id]);

        // Redirect to the books management page with a success message
        header("Location: admin.php?message=Book deleted successfully.");
        exit();
    } else {
        // If the book doesn't exist, redirect with an error message
        header("Location: admin.php?error=Book not found.");
        exit();
    }
} else {
    // If no ID is provided, redirect with an error message
    header("Location: admin.php?error=No book ID provided.");
    exit();
}
?>