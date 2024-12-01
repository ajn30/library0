<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger' role='alert'>You must be logged in to borrow a book.</div>";
    exit;
}

if (isset($_GET['book_id'])) {
    // Get book_id from the URL
    $book_id = $_GET['book_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch the book details from the database
    $sql = "SELECT * FROM books WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    echo "<div class='container mt-4'>"; // Bootstrap container for centered content
    echo "<h2>Borrow Book</h2>"; // Page header

    // Check if the book exists
    if ($book) {
        // Check if the book is available (quantity > 0)
        if ($book['quantity'] > 0) {
            // Borrow the book by updating the quantity in the database
            $sql = "UPDATE books SET quantity = quantity - 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$book_id]);

            // Log the transaction into the transactions table
            $sql = "INSERT INTO transactions (user_id, book_id, action, status) VALUES (?, ?, 'borrow', 'borrowed')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $book_id]);

            // Success message
            echo "<div class='alert alert-success' role='alert'>";
            echo "You have successfully borrowed the book: <strong>" . htmlspecialchars($book['title']) . "</strong>!";
            echo "</div>";
        } else {
            // If the book is out of stock
            echo "<div class='alert alert-warning' role='alert'>";
            echo "Sorry, the book <strong>" . htmlspecialchars($book['title']) . "</strong> is out of stock.";
            echo "</div>";
        }
    } else {
        // If the book is not found in the database
        echo "<div class='alert alert-danger' role='alert'>";
        echo "Book not found.";
        echo "</div>";
    }

    // Link to go back to the dashboard
    echo "<a href='dashboard.php' class='btn btn-primary'>Go Back to Dashboard</a>";
    echo "</div>"; // Closing container
} else {
    // If 'book_id' is not provided in the URL, show an error message
    echo "<div class='alert alert-danger' role='alert'>";
    echo "Invalid request. No book ID provided.";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Optional: Bootstrap JS (for modals, tooltips, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>