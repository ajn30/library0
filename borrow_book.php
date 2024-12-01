<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info from the database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch all available books
$books_sql = "SELECT * FROM books WHERE status = 'available'";
$books_stmt = $pdo->prepare($books_sql);
$books_stmt->execute();
$books = $books_stmt->fetchAll();

// Handle the book borrowing request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];

    // Check if the book is available
    $check_book_sql = "SELECT * FROM books WHERE id = ? AND status = 'available'";
    $check_book_stmt = $pdo->prepare($check_book_sql);
    $check_book_stmt->execute([$book_id]);
    $book = $check_book_stmt->fetch();

    if ($book) {
        // Insert the borrow request as pending
        $sql = "INSERT INTO borrow_requests (user_id, book_id, status) VALUES (?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $book_id]);

        $message = "Your borrow request is pending approval from the admin.";
    } else {
        $message = "Sorry, the book is not available.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="user_dashboard.php">Library Dashboard</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="user_dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="borrow_book.php">Borrow Book</a></li>
        <li class="nav-item"><a class="nav-link" href="return_book.php">Return Book</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h2>Borrow a Book</h2>

    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="bookSelect" class="form-label">Select Book to Borrow</label>
            <select id="bookSelect" name="book_id" class="form-select" required>
                <option value="" disabled selected>Select a book</option>
                <?php foreach ($books as $book): ?>
                    <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Request to Borrow</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
