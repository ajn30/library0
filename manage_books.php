<?php
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not an admin
    exit;
}

// Fetch all books from the database
$books_sql = "SELECT id, title, author, quantity, isbn, genre, accession_number FROM books";
$books_stmt = $pdo->query($books_sql);
$books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    if (isset($_POST['add_book'])) {
        // Validate and sanitize input
        $title = htmlspecialchars(trim($_POST['title']));
        $author = htmlspecialchars(trim($_POST['author']));
        $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
        $isbn = htmlspecialchars(trim($_POST['isbn']));
        $genre = htmlspecialchars(trim($_POST['genre']));
        $accession_number = htmlspecialchars(trim($_POST['accession_number']));

        // Insert the new book into the database
        $add_book_sql = "INSERT INTO books (title, author, quantity, isbn, genre, accession_number) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($add_book_sql);
        $stmt->execute([$title, $author, $quantity, $isbn, $genre, $accession_number]);

        $_SESSION['message'] = "Book added successfully!";
        header("Location: manage_books.php");
        exit;
    } elseif (isset($_POST['update_book'])) {
        // Validate and sanitize input
        $book_id = filter_var($_POST['book_id'], FILTER_VALIDATE_INT);
        $title = htmlspecialchars(trim($_POST['title']));
        $author = htmlspecialchars(trim($_POST['author']));
        $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
        $isbn = htmlspecialchars(trim($_POST['isbn']));
        $genre = htmlspecialchars(trim($_POST['genre']));
        $accession_number = htmlspecialchars(trim($_POST['accession_number']));

        // Update the book details in the database
        $update_book_sql = "UPDATE books SET title = ?, author = ?, quantity = ?, isbn = ?, genre = ?, accession_number = ? WHERE id = ?";
        $stmt = $pdo->prepare($update_book_sql);
        $stmt->execute([$title, $author, $quantity, $isbn, $genre, $accession_number, $book_id]);

        $_SESSION['message'] = "Book updated successfully!";
        header("Location: manage_books.php");
        exit;
    }
} elseif (isset($_GET['delete_book_id'])) {
    $book_id = filter_var($_GET['delete_book_id'], FILTER_VALIDATE_INT);

    if ($book_id) {
        // Delete the book from the database
        $delete_book_sql = "DELETE FROM books WHERE id = ?";
        $stmt = $pdo->prepare($delete_book_sql);
        $stmt->execute([$book_id]);

        $_SESSION['message'] = "Book deleted successfully!";
        header("Location: manage_books.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Library Management</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h3>Manage Books</h3>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info" role="alert">
            <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <h4>Add New Book</h4>
        <form action="manage_books.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="author" class="form-label">Author</label>
                    <input type="text" class="form-control" id="author" name="author" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" class="form-control" id="isbn" name="isbn" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="genre" class="form-label">Genre</label>
                    <select class="form-control" id="genre" name="genre" required>
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science">Science</option>
                        <option value="History">History</option>
                        <option value="Biography">Biography</option>
                        <option value="Fantasy">Fantasy</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="accession_number" class="form-label">Accession Number</label>
                <input type="text" class="form-control" id="accession_number" name="accession_number" required>
            </div>
            <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
        </form>
    </div>

    <h4>Available Books</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Author</th>
                <th>Quantity</th>
                <th>ISBN</th>
                <th>Genre</th>
                <th>Accession Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?= htmlspecialchars($book['id']); ?></td>
                    <td><?= htmlspecialchars($book['title']); ?></td>
                    <td><?= htmlspecialchars($book['author']); ?></td>
                    <td><?= htmlspecialchars($book['quantity']); ?></td>
                    <td><?= htmlspecialchars($book['isbn']); ?></td>
                    <td><?= htmlspecialchars($book['genre']); ?></td>
                    <td><?= htmlspecialchars($book['accession_number']); ?></td>
                    <td>
                        <!-- Edit Modal Button -->
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editBookModal<?= $book['id']; ?>">Edit</button>
                        <a href="manage_books.php?delete_book_id=<?= $book['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <!-- Edit Modal -->
                <div class="modal fade" id="editBookModal<?= $book['id']; ?>" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="manage_books.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="book_id" value="<?= $book['id']; ?>">

                                    <div class="mb-3">
                                        <label for="title" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($book['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="author" class="form-label">Author</label>
                                        <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($book['author']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars($book['quantity']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?= htmlspecialchars($book['isbn']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <select class="form-control" id="genre" name="genre" required>
                                            <option value="Fiction" <?= $book['genre'] == 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                                            <option value="Non-Fiction" <?= $book['genre'] == 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                                            <option value="Science" <?= $book['genre'] == 'Science' ? 'selected' : ''; ?>>Science</option>
                                            <option value="History" <?= $book['genre'] == 'History' ? 'selected' : ''; ?>>History</option>
                                            <option value="Biography" <?= $book['genre'] == 'Biography' ? 'selected' : ''; ?>>Biography</option>
                                            <option value="Fantasy" <?= $book['genre'] == 'Fantasy' ? 'selected' : ''; ?>>Fantasy</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="accession_number" class="form-label">Accession Number</label>
                                        <input type="text" class="form-control" id="accession_number" name="accession_number" value="<?= htmlspecialchars($book['accession_number']); ?>" required>
                                    </div>

                                    <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
