<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all books that the user has borrowed
$sql = "SELECT b.id, b.title, b.author FROM books b 
        JOIN transactions t ON b.id = t.book_id 
        WHERE t.user_id = ? AND t.status = 'borrowed'"; 
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$borrowed_books = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the selected book ID
    $book_id = $_POST['book_id'];

    // Check if the book exists and is borrowed by the current user
    $sql = "SELECT t.*, b.title FROM transactions t 
            JOIN books b ON t.book_id = b.id
            WHERE t.book_id = ? AND t.user_id = ? AND t.status = 'borrowed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$book_id, $user_id]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        // Return the book by updating the quantity in the books table
        $sql = "UPDATE books SET quantity = quantity + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$book_id]);

        // Update the transaction status to 'returned'
        $sql = "UPDATE transactions SET status = 'returned' WHERE book_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$book_id, $user_id]);

        // Success message
        echo "<div class='alert alert-success' role='alert'>";
        echo "You have successfully returned the book: <strong>" . htmlspecialchars($transaction['title']) . "</strong>!";
        echo "</div>";
    } else {
        // If the book is not found in the transactions table, display an error
        echo "<div class='alert alert-danger' role='alert'>";
        echo "You have not borrowed this book or it's already returned.";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="user_dashboard.php">Library User Panel</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link" href="user_dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="borrow_book.php">Borrow Book</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="return_book.php">Return Book</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <h2>Return a Book</h2>

    <form action="return_book.php" method="POST">
        <div class="mb-3">
            <label for="book_id" class="form-label">Select a Book</label>
            <select class="form-select" id="book_id" name="book_id" required>
                <option value="">-- Select a Book --</option>
                <?php foreach ($borrowed_books as $book): ?>
                    <option value="<?php echo $book['id']; ?>">
                        <?php echo htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Return Book</button>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>