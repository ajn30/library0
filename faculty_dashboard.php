<?php
include 'db.php';  // Assuming this file connects to your database

// Start session and check if the user is logged in
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'faculty') {
    header("Location: login.php");  // Redirect if not logged in or not a faculty user
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch faculty info from the database
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

// Count how many books the faculty has borrowed
$borrowed_books_count = count($transactions);

// Fetch the total number of books borrowed from this faculty
$borrowed_books_from_faculty_sql = "
    SELECT COUNT(*) as total_borrowed_books 
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    WHERE t.user_id = ? AND t.status = 'borrowed' AND b.faculty_id = ?";
$borrowed_books_from_faculty_stmt = $pdo->prepare($borrowed_books_from_faculty_sql);
$borrowed_books_from_faculty_stmt->execute([$user_id, $user_id]);
$borrowed_books_from_faculty = $borrowed_books_from_faculty_stmt->fetch();

// Fetch total books borrowed by students
$borrowed_books_by_students_sql = "
    SELECT COUNT(*) as total_borrowed_books 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.action = 'borrow' AND t.status = 'borrowed' AND u.role = 'student'";
$borrowed_books_by_students_stmt = $pdo->query($borrowed_books_by_students_sql);
$borrowed_books_by_students = $borrowed_books_by_students_stmt->fetch();

// Handle borrowing a book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['borrow_book'])) {
    $book_id = $_POST['book_id'];
    $book_sql = "SELECT * FROM books WHERE id = ?";
    $book_stmt = $pdo->prepare($book_sql);
    $book_stmt->execute([$book_id]);
    $book = $book_stmt->fetch();

    if ($book && $book['quantity'] > 0) {
        // Update book quantity
        $new_quantity = $book['quantity'] - 1;
        $update_book_sql = "UPDATE books SET quantity = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_book_sql);
        $update_stmt->execute([$new_quantity, $book_id]);

        // Add transaction to log the borrow action
        $transaction_sql = "INSERT INTO transactions (user_id, book_id, action, status) VALUES (?, ?, 'borrow', 'borrowed')";
        $transaction_stmt = $pdo->prepare($transaction_sql);
        $transaction_stmt->execute([$user_id, $book_id]);

        // Set success message
        $_SESSION['message'] = 'Book borrowed successfully!';
        header('Location: faculty_dashboard.php'); // Refresh the page
        exit();
    } else {
        $_SESSION['message'] = 'Book is out of stock.';
    }
}

// Handle returning a book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $book_id = $_POST['book_id'];
    $transaction_sql = "SELECT * FROM transactions WHERE user_id = ? AND book_id = ? AND action = 'borrow' AND status = 'borrowed'";
    $transaction_stmt = $pdo->prepare($transaction_sql);
    $transaction_stmt->execute([$user_id, $book_id]);
    $transaction = $transaction_stmt->fetch();

    if ($transaction) {
        // Update transaction status to 'returned'
        $update_transaction_sql = "UPDATE transactions SET status = 'returned' WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
        $update_stmt = $pdo->prepare($update_transaction_sql);
        $update_stmt->execute([$user_id, $book_id]);

        // Update book quantity
        $book_sql = "SELECT * FROM books WHERE id = ?";
        $book_stmt = $pdo->prepare($book_sql);
        $book_stmt->execute([$book_id]);
        $book = $book_stmt->fetch();

        if ($book) {
            $new_quantity = $book['quantity'] + 1;
            $update_book_sql = "UPDATE books SET quantity = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_book_sql);
            $update_stmt->execute([$new_quantity, $book_id]);
        }

        $_SESSION['message'] = 'Book returned successfully!';
        header('Location: faculty_dashboard.php'); // Refresh the page
        exit();
    } else {
        $_SESSION['message'] = 'No borrow record found for this book.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Library System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #212529;
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar a {
            color: #f8f9fa !important;
        }
        .navbar a:hover {
            color: #f39c12 !important;
        }
        .container {
            background-color: #343a40;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
        }
        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.8;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #444;
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
            background-color: #454d55;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
        }
        .card-body {
            background-color: #343a40;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="faculty_dashboard.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link active" href="faculty_dashboard.php">Dashboard</a>
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
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-info' role='alert'>" . $_SESSION['message'] . "</div>";
        unset($_SESSION['message']);
    }
    ?>

    <!-- Borrowing Stats -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Borrowing Stats</div>
                <div class="card-body">
                    <p>Total Books Borrowed: <strong><?php echo $borrowed_books_count; ?></strong></p>
                    <p>Total Books Borrowed from You: <strong><?php echo $borrowed_books_from_faculty['total_borrowed_books']; ?></strong></p>
                    <p>Total Books Borrowed by Students: <strong><?php echo $borrowed_books_by_students['total_borrowed_books']; ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Books List -->
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
                            <form action="faculty_dashboard.php" method="post">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" name="borrow_book" class="btn btn-primary btn-sm">Borrow</button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>Unavailable</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
