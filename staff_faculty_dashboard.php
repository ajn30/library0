
<?php
include 'db.php'; // Assuming this file connects to your database
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Check if the user is either staff or faculty
if ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'faculty') {
    header("Location: login.php"); // Redirect to login if user is not staff or faculty
    exit();
}

// Fetch user info from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($_SESSION['role']); ?> Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="staff_faculty_dashboard.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link active" href="staff_faculty_dashboard.php">Dashboard</a>
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

    <!-- Role-based Dashboard Content -->
    <div class="row">
        <div class="col-md-12">
            <?php if ($_SESSION['role'] == 'staff'): ?>
                <h4>Staff Dashboard</h4>
                <p>As a staff member, you can manage user transactions, view borrowed books, and more.</p>
                <!-- Staff-specific features here -->
                <ul>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="view_transactions.php">View Transactions</a></li>
                    <li><a href="borrow_book.php">Borrow Book</a></li>
                </ul>

            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                <h4>Faculty Dashboard</h4>
                <p>As a faculty member, you can request books, view your borrowed books, and manage your academic resources.</p>
                <!-- Faculty-specific features here -->
                <ul>
                    <li><a href="view_faculty_books.php">View Borrowed Books</a></li>
                    <li><a href="request_book.php">Request Books</a></li>
                </ul>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>
