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

    // Fetch the book details from the database
    $book_sql = "SELECT * FROM books WHERE id = ?";
    $book_stmt = $pdo->prepare($book_sql);
    $book_stmt->execute([$book_id]);
    $book = $book_stmt->fetch();

    // If the book doesn't exist, redirect with an error message
    if (!$book) {
        header("Location: admin.php?error=Book not found.");
        exit();
    }
} else {
    // If no ID is provided, redirect with an error message
    header("Location: admin.php?error=No book ID provided.");
    exit();
}

// Handle the form submission for editing the book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $status = $_POST['status'];

    // Update the book in the database
    $update_sql = "UPDATE books SET title = ?, author = ?, status = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$title, $author, $status, $book_id]);

    // Redirect to the admin page with a success message
    header("Location: admin.php?message=Book updated successfully.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">Library Admin Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h3>Edit Book</h3>
    
    <a href="admin.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <!-- Edit Book Form -->
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Book Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="author" class="form-label">Author</label>
            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="available" <?php echo ($book['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="borrowed" <?php echo ($book['status'] == 'borrowed') ? 'selected' : ''; ?>>Borrowed</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Book</button>
    </form>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>