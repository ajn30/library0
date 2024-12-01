<?php
include 'db.php';  // Assuming this file connects to your database

// Start session and check if the user is logged in
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php");  // Redirect if not logged in or not a staff user
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch staff info from the database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch books from the database
$books_sql = "SELECT * FROM books";
$books_stmt = $pdo->query($books_sql);
$books = $books_stmt->fetchAll();

// Fetch transactions for the logged-in user (e.g., borrowed books)
$transactions_sql = "SELECT * FROM transactions WHERE user_id = ? AND action = 'borrow'";
$transactions_stmt = $pdo->prepare($transactions_sql);
$transactions_stmt->execute([$user_id]);
$transactions = $transactions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Library System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background-color: #1f3a64;
        }
        .navbar a {
            color: #ffffff !important;
        }
        .navbar a:hover {
            color: #f39c12 !important;
        }
        .container {
            background-color: #212121;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .btn-primary, .btn-secondary {
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.8;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #333;
        }
        .table th, .table td {
            color: #ddd;
            text-align: center;
        }
        .alert-info {
            font-size: 1.1rem;
            color: #007bff;
            background-color: #333;
        }
        .card {
            background-color: #3e444b;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background-color: #1f3a64;
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
        }
        .card-body {
            background-color: #212121;
        }
        .table th {
            background-color: #1f3a64;
        }
        .navbar-toggler {
            background-color: #007bff;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="staff_dashboard.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link active" href="staff_dashboard.php">Dashboard</a>
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
            <p>Role: <strong><?php echo ucfirst(htmlspecialchars($user['role'])); ?></strong></p> <!-- Display Role -->
        </div>
    </div>

    <!-- Display Message (if any) -->
    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-info' role='alert'>" . $_SESSION['message'] . "</div>";
        unset($_SESSION['message']);
    }
    ?>

    <!-- Back to Home -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="home.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <!-- Available Books List -->
    <div class="row">
        <div class="col-md-12">
            <h4>Available Books</h4>
            <div class="card">
                <div class="card-header">Books in the Library</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Status</th>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrowed Books Section -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h4>Your Borrowed Books</h4>
            <div class="card">
                <div class="card-header">Borrowed Books</div>
                <div class="card-body">
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
                                        // Fetch book title for the borrowed book
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
        </div>
    </div>

</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>
