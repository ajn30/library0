
<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info from the database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch books from the database
$books_sql = "SELECT * FROM books";
$books_stmt = $pdo->query($books_sql);
$books = $books_stmt->fetchAll();

// Fetch transactions for the logged-in user
$transactions_sql = "SELECT * FROM transactions WHERE user_id = ?";
$transactions_stmt = $pdo->prepare($transactions_sql);
$transactions_stmt->execute([$user_id]);
$transactions = $transactions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Library Management</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link active" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h3>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h3>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <!-- Display Message (if any) -->
    <?php
    // Check if there is a message in the session and display it
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-info' role='alert'>" . $_SESSION['message'] . "</div>";
        // Clear the message after displaying
        unset($_SESSION['message']);
    }
    ?>

    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="home.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <!-- Conditionally Render User Dashboard or Admin Dashboard -->

    <?php if ($user['role'] == 'admin'): ?>
        <!-- Admin Dashboard Section -->
        <div class="row mb-5">
            <div class="col-md-12">
                <h4>Admin Panel</h4>
                <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
                <a href="manage_books.php" class="btn btn-primary">Manage Books</a>
                <a href="add_book.php" class="btn btn-primary">Add New Book</a>
            </div>
        </div>

    <?php else: ?>
        <!-- User Dashboard Section -->
        <div class="row">
            <div class="col-md-12">
                <h4>Available Books</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo $book['id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td>
                                <?php echo ($book['quantity'] > 0) ? 'Available' : 'Out of Stock'; ?>
                            </td>
                            <td>
                                <?php if ($book['quantity'] > 0): ?>
                                    <a href="borrow.php?book_id=<?php echo $book['id']; ?>" class="btn btn-success btn-sm">Borrow</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Borrowed</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Borrowed Books Section -->
        <div class="row mt-5">
            <div class="col-md-12">
                <h4>Your Borrowed Books</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Book Title</th>
                            <th>Action</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction['book_id']; ?></td>
                            <td>
                                <?php 
                                    // Fetch the book title for the borrowed book
                                    $book_sql = "SELECT * FROM books WHERE id = ?";
                                    $book_stmt = $pdo->prepare($book_sql);
                                    $book_stmt->execute([$transaction['book_id']]);
                                    $borrowed_book = $book_stmt->fetch();
                                    echo htmlspecialchars($borrowed_book['title']);
                                ?>
                            </td>
                            <td><?php echo ucfirst($transaction['action']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($transaction['status']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>
