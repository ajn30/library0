
<?php
// Assuming user authentication, start session
session_start();
include 'db.php'; // Make sure to include your database connection file

// Check if the user is logged in (optional if required for specific actions)
if (isset($_SESSION['user_id'])) {
    // Fetch the user details if needed
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="home.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link active" href="home.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="books.php">Available Books</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="add_book.php">Add Book</a>
        </li>
        
        <!-- Check if the user is logged in -->
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Fetch user info to check if they are an admin -->
          <?php
          $user_id = $_SESSION['user_id'];
          // Assuming you have a function to get the user role (e.g., from a database)
          $sql = "SELECT role FROM users WHERE id = ?";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([$user_id]);
          $user = $stmt->fetch();

          // If the user is an admin, show the Admin link
          if ($user['role'] == 'admin'):
          ?>
            <li class="nav-item">
              <a class="nav-link" href="admin.php">Admin Panel</a>
            </li>
          <?php endif; ?>

          <!-- Logout link -->
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <!-- Login and Register links for guests -->
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>


<!-- Hero Section -->
<div class="container text-center my-5">
    <h1 class="display-4">Welcome to the Library Management System</h1>
    <p class="lead">Browse, Borrow, and Manage Books with Ease</p>
    <hr class="my-4">
    <p>Explore our collection of books or add new books to the library!</p>
</div>

<!-- Featured Section -->
<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="https://via.placeholder.com/150" class="card-img-top" alt="Available Books">
                <div class="card-body">
                    <h5 class="card-title">Available Books</h5>
                    <p class="card-text">Browse the list of available books and borrow your favorites.</p>
                    <a href="books.php" class="btn btn-primary">View Books</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="https://via.placeholder.com/150" class="card-img-top" alt="Add a Book">
                <div class="card-body">
                    <h5 class="card-title">Add a New Book</h5>
                    <p class="card-text">Add new books to the library's collection to make them available for others.</p>
                    <a href="add_book.php" class="btn btn-primary">Add Book</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="https://via.placeholder.com/150" class="card-img-top" alt="Manage Books">
                <div class="card-body">
                    <h5 class="card-title">Manage Your Books</h5>
                    <p class="card-text">Check out the books you've borrowed and keep track of your reading list.</p>
                    <a href="dashboard.php" class="btn btn-primary">Manage Books</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Panel (Only if the user is an admin) -->
<?php if (isset($user) && $user['role'] == 'admin'): ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h4>Admin Panel</h4>
                <ul>
                    <li><a href="manage_books.php" class="btn btn-warning">Manage Books</a></li>
                    <li><a href="manage_users.php" class="btn btn-warning">Manage Users</a></li>
                    <li><a href="view_transactions.php" class="btn btn-warning">View Transactions</a></li>
                    <!-- You can add more admin-related links here -->
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Footer Section -->
<footer class="bg-dark text-white text-center py-4">
    <p>&copy; 2024 Library Management System. All Rights Reserved.</p>
</footer>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>
